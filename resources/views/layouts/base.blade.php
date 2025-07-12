<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Andrew Krzynowek - Laravel Developer & Musician</title>
    <meta name="theme-color" content="#ffffff" id="theme-color">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono&display=swap" rel="stylesheet">


    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
    <div class="min-h-screen">
        <nav class="py-6 px-4">
            <div class="max-w-2xl mx-auto flex justify-between items-center">
                <div class="flex space-x-6">
{{--                    <a href="/" class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Home</a>--}}
{{--                    <a href="/blog" class="font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Blog</a>--}}
{{--                    <a href="/links" class="font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Links</a>--}}
                </div>
                <button
                    id="theme-toggle"
                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                    aria-label="Toggle theme"
                >
                    <svg class="w-5 h-5 hidden dark:block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h1M4 12H3m15.364 6.364l.707.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M12 7a5 5 0 100 10 5 5 0 000-10z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                    </svg>
                </button>
            </div>
        </nav>

        <main class="px-4">
            @yield('content')
        </main>
    </div>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const themeColor = document.getElementById('theme-color');

        const savedTheme = localStorage.getItem('theme') || 'light';
        html.className = savedTheme;
        updateThemeColor();

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.className;
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';

            html.className = newTheme;
            localStorage.setItem('theme', newTheme);
            updateThemeColor();
        });

        function updateThemeColor() {
            const isDark = html.className === 'dark';
            themeColor.content = isDark ? '#111827' : '#ffffff';
        }
    </script>
</body>
</html>
