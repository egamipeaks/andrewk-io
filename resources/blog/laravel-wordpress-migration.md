# Running Laravel and WordPress Together: How We Migrated a Monolith One Route at a Time

Migrating a WordPress monolith powering 20 sites and millions of paying 
customers to Laravel is no small feat. We wanted a seamless transition 
without disrupting the platform. Here’s how we made Laravel and 
WordPress coexist, moving one route at a time.

To do this, we built a system that lets Laravel and WordPress coexist 
in the same request lifecycle — with full access to Laravel's services 
from within WordPress, and Laravel routes that can opt in or out of 
WordPress behavior as needed.

Here's how we made it work.

## Step 1: Rename Laravel's __() Helper Function

Both Laravel and WordPress define a global `__()` function for localization, which can cause conflicts. To resolve this, we created a ComposerScripts::renameHelperFunctions hook to rename Laravel’s `__()` to `___()` immediately after Composer generates the autoloader:

```php
// Rename Laravel's __() helper to ___() to avoid conflict with WordPress
$content = file_get_contents($helpersPath);
$content = str_replace("function_exists('__')", "function_exists('___')", $content);
$content = str_replace('function __(', 'function ___(', $content);
file_put_contents($helpersPath, $content);
```

This way, WordPress can safely use `__()`, and Laravel's helper remains accessible as `___()`.

## Step 2: Bootstrap Laravel from WordPress

In `wp-config.php`, after including `vendor/autoload.php` (to load Composer dependencies), we initialize Laravel with a custom bootstrapper:

```php
// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel in WordPress
require_once __DIR__ . '/path-to/wp-laravel-bootstrapper.php';
```

Then, in `wp-laravel-bootstrapper.php`:

```php
// Initialize Laravel application
$app = require_once '/path-to/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Handle WP-Admin URLs to prevent Laravel from misinterpreting them as homepage requests
if (str_contains($request->getRequestUri(), '/wp/')) {
    $request = Illuminate\Http\Request::create('/wp-admin');
}

$app->instance('request', $request);
Facade::clearResolvedInstance('request');
$kernel->bootstrap($request);
```

## Step 3: Handling WordPress and Laravel Routes

We prioritize Laravel routes over WordPress to allow seamless integration of new functionality. First, we check if the request matches a Laravel route:

```php
// Check if the request matches a Laravel route; if not, WordPress handles it
try {
    $route = $app->make('router')->getRoutes()->match($request);
} catch (Exception) {
    // No Laravel route matched, so WordPress takes over
    return;
}
```

We then pass the route to a custom `LaravelRequestHandler` class that decides how to handle the request based on the matched route and the current WordPress context.

```php
app(\App\WordPress\LaravelRequestHandler::class)->init($kernel, $request, $route);
```

## Step 4: Controlling the Lifecycle with LaravelRequestHandler

We developed a LaravelRequestHandler class to manage Laravel’s behavior within a WordPress request, deciding whether Laravel runs early, defers to WordPress, or takes full control of the response.

Here's what it enables:

- Skipping WordPress entirely for full Laravel routes
- Allowing WordPress to initialize (and even run WP_Query) before handing control to Laravel
- Disabling plugins or REST API selectively

As the first MU plugin, we add a file `mu-plugins/00-laravel.php`, and run:

```php
app(\App\WordPress\LaravelRequestHandler::class)->executeRoute();
```

This executes the matched Laravel route — or lets WordPress do its thing depending on the context.

Here is a breakdown of the `LaravelRequestHandler` class:
```php
namespace App\WordPress;

use App\Http\Middleware\ShortInit;
use App\Http\Middleware\WordPressWithPlugins;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class LaravelRequestHandler
{
    public bool $executeRoute = false;

    public string $loadLaravelAction = 'plugins_loaded';

    public ?Kernel $kernel = null;

    public ?Request $request = null;

    public ?Route $route = null;

    private array $middlewareWithShortInit = [
        ShortInit::class,
        'short-init',
    ];

    private array $middlewareWithPlugins = [
        WordPressWithPlugins::class,
        'wordpress-with-plugins',
        'wordpress-with-plugins:run-query',
    ];

    private array $middlewareWithWpQuery = [
        'wordpress-with-plugins:run-query',
    ];

    // Initialize LaravelRequestHandler with kernel, request, and route
    public function init(Kernel $kernel, Request $request, Route $route): void
    {
        $this->executeRoute = true;
        $this->kernel = $kernel;
        $this->request = $request;
        $this->route = $route;
    }

    public function loadLaravelAndExit(): void
    {
        if ($this->loadLaravelAction == 'wp_loaded') {
            $this->parseRequest();
        }

        $response = $this->kernel->handle($this->request);

        $body = $response->send();

        $this->kernel->terminate($this->request, $body);
        exit;
    }
    
    public function executeRoute(): void
    {
        if (! $this->executeRoute) {
            return;
        }

        if ($this->shouldExitEarly()) {
            $this->loadLaravelAndExit();

            return;
        }

        add_action($this->loadLaravelAction, $this->loadLaravelAndExit(...), 1, PHP_INT_MAX);
    }

    private function parseRequest(): void
    {
        global $wp;

        $wp->init();

        $parsed = $wp->parse_request();

        if ($this->shouldRunWpQuery()) {
            $this->runWpQuery($parsed);
        }

        _wp_admin_bar_init();
    }

    private function runWpQuery($parsed): void
    {
        global $wp, $wp_query, $wp_the_query, $post;

        if ($parsed) {
            $wp->query_posts();
            $wp->register_globals();
        }

        do_action_ref_array('wp', [&$wp]);

        if (! isset($wp_the_query)) {
            $wp_the_query = $wp_query;
        }

        if ($post) {
            setup_postdata($post);
        }
    }

    private function shouldExitEarly(): bool
    {
        return $this->hasMiddleware($this->middlewareWithShortInit);
    }
    
    private function hasMiddleware(array $middlewareToCheck): bool
    {
        $middleware = $this->route?->gatherMiddleware();

        if (! $middleware) {
            return false;
        }

        foreach ($middleware as $m) {
            if (in_array($m, $middlewareToCheck)) {
                return true;
            }
        }

        return false;
    }

    private function shouldRunWpQuery(): bool
    {
        return $this->hasMiddleware($this->middlewareWithWpQuery);
    }
}
```

## 🧪 Controlling Timing with loadLaravelAction

The `LaravelRequestHandler` uses the `loadLaravelAction` property to control when Laravel processes the request, ensuring compatibility with the WordPress lifecycle.

For example:

- `plugins_loaded` → Laravel runs before WordPress really starts
- `wp_loaded` → Laravel runs after WP_Query has done its work

By default, routes using the `short-init` middleware skip WordPress entirely, while others (like `wordpress-with-plugins`) allow a hybrid response.

This flexibility let us move piece by piece from WordPress to Laravel without breaking the rest of the system.

## ✅ The Result

The result is a robust hybrid application where:

- Laravel boots seamlessly on every request.
- Laravel routes override WordPress behavior as needed.
- Existing WordPress pages function without interruption. 

This approach enabled a gradual migration, improving scalability and maintainability.

