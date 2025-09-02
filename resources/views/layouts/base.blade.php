<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Andrew Krzynowek - Laravel Developer & Musician</title>
    <meta name="theme-color" content="#ffffff" id="theme-color">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono&display=swap" rel="stylesheet">

    <script>
        // Apply theme ASAP to avoid flash of incorrect theme
        (function () {
            try {
                const stored = localStorage.getItem('theme') || 'system';
                const mql = window.matchMedia('(prefers-color-scheme: dark)');
                const isDark = stored === 'dark' || (stored === 'system' && mql.matches);
                const html = document.documentElement;
                html.classList.toggle('dark', isDark);
                html.classList.toggle('light', !isDark);
                const themeColor = document.getElementById('theme-color');
                if (themeColor) themeColor.content = isDark ? '#111827' : '#ffffff';
            } catch (e) {}
        })();
    </script>


    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
    <div class="min-h-screen">
        <nav class="py-6 px-4">
            <div class="max-w-2xl mx-auto flex justify-between items-center">
                <div class="flex space-x-6">
                    <a href="/" class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Home</a>
                    <a href="/work" class="font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Work</a>
                    <a href="/blog/laravel-wordpress-migration" class="font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Blog</a>
                    @auth
                        <a href="/admin" class="font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Admin</a>
                    @endauth
                </div>
                <div class="relative">
                    <button
                        id="theme-toggle"
                        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                        aria-label="Toggle theme"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        title="Select theme"
                    >
                        <!-- Light icon -->
                        <svg id="icon-light" class="w-5 h-5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m8-8h2M2 12H4m13.657 6.343l1.414 1.414M4.929 4.929L6.343 6.343m9.9-1.414l-1.414 1.414M6.343 17.657l-1.414 1.414M12 8a4 4 0 100 8 4 4 0 000-8z" />
                        </svg>
                        <!-- Dark icon -->
                        <svg id="icon-dark" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                        </svg>
                        <!-- System icon -->
                        <svg id="icon-system" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h7v2H7a1 1 0 100 2h10a1 1 0 100-2h-3v-2h7a1 1 0 001-1V5a1 1 0 00-1-1H3zm1 2h16v8H4V6z" />
                        </svg>
                    </button>

                    <div id="theme-menu" class="hidden absolute right-0 mt-2 w-40 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg py-1 z-50">
                        <button type="button" data-mode="light" class="flex items-center w-full px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <span>Light</span>
                            <svg id="check-light" class="w-4 h-4 ml-auto hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.5 7.5a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414L8.5 12.086l6.793-6.793a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button type="button" data-mode="dark" class="flex items-center w-full px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <span>Dark</span>
                            <svg id="check-dark" class="w-4 h-4 ml-auto hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.5 7.5a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414L8.5 12.086l6.793-6.793a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button type="button" data-mode="system" class="flex items-center w-full px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <span>System</span>
                            <svg id="check-system" class="w-4 h-4 ml-auto hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.5 7.5a1 1 0 01-1.414 0l-3-3a1 1 0 111.414-1.414L8.5 12.086l6.793-6.793a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="px-4">
            @yield('content')
        </main>

        <div class="flex justify-center space-x-6 py-8">
            <a href="https://github.com/egamipeaks" target="_blank" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
            </a>
            <a href="https://www.linkedin.com/in/andrewkrzynowek/" target="_blank" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
            </a>
            <a href="https://x.com/a_krzynowek" target="_blank" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
            </a>
            <a href="https://www.youtube.com/@andrewkrzynowek1789/videos" target="_blank" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
            </a>
        </div>
    </div>

    <script>
        (function () {
            const KEY = 'theme';
            const html = document.documentElement;
            const themeColor = document.getElementById('theme-color');
            const toggleBtn = document.getElementById('theme-toggle');
            const menu = document.getElementById('theme-menu');
            const iconLight = document.getElementById('icon-light');
            const iconDark = document.getElementById('icon-dark');
            const iconSystem = document.getElementById('icon-system');
            const mql = window.matchMedia('(prefers-color-scheme: dark)');

            let mode = localStorage.getItem(KEY) || 'system';

            function resolveIsDark(m) {
                return m === 'dark' || (m === 'system' && mql.matches);
            }

            function apply(modeToApply) {
                const isDark = resolveIsDark(modeToApply);
                html.classList.toggle('dark', isDark);
                html.classList.toggle('light', !isDark);
                if (themeColor) themeColor.content = isDark ? '#111827' : '#ffffff';

                // Update icons to reflect current mode selection
                iconLight.classList.toggle('hidden', modeToApply !== 'light');
                iconDark.classList.toggle('hidden', modeToApply !== 'dark');
                iconSystem.classList.toggle('hidden', modeToApply !== 'system');

                // Update checks in dropdown
                const checks = {
                    light: document.getElementById('check-light'),
                    dark: document.getElementById('check-dark'),
                    system: document.getElementById('check-system'),
                };
                Object.entries(checks).forEach(([k, el]) => el && el.classList.toggle('hidden', k !== modeToApply));

                // Update ARIA label for accessibility
                if (toggleBtn) toggleBtn.setAttribute('aria-label', `Theme: ${modeToApply}`);
            }

            // React to OS changes when in system mode
            mql.addEventListener?.('change', () => {
                if ((localStorage.getItem(KEY) || 'system') === 'system') {
                    apply('system');
                }
            });

            // Initial paint
            apply(mode);

            function openMenu() {
                if (!menu) return;
                menu.classList.remove('hidden');
                toggleBtn?.setAttribute('aria-expanded', 'true');
            }

            function closeMenu() {
                if (!menu) return;
                menu.classList.add('hidden');
                toggleBtn?.setAttribute('aria-expanded', 'false');
            }

            toggleBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (menu?.classList.contains('hidden')) openMenu(); else closeMenu();
            });

            // Handle selection
            menu?.querySelectorAll('[data-mode]')?.forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const selected = btn.getAttribute('data-mode');
                    if (selected) {
                        mode = selected;
                        localStorage.setItem(KEY, mode);
                        apply(mode);
                    }
                    closeMenu();
                });
            });

            // Click outside to close
            document.addEventListener('click', (e) => {
                if (!menu || menu.classList.contains('hidden')) return;
                const target = e.target;
                if (!menu.contains(target) && target !== toggleBtn) {
                    closeMenu();
                }
            });
        })();
    </script>
</body>
</html>
