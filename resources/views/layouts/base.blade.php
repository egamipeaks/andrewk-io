<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Andrew K - Developer & Consultant</title>
    <meta name="theme-color" content="#ffffff" id="theme-color">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
    <div class="min-h-screen">
        <nav class="py-6 px-4">
            <div class="max-w-2xl mx-auto flex justify-between items-center">
                <div class="flex space-x-6">
                    <a href="/" class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Home</a>
                    <a href="/blog" class="font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Blog</a>
                    <a href="/links" class="font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Links</a>
                </div>
                <button
                    id="theme-toggle"
                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                    aria-label="Toggle theme"
                >
                    <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2L13.09 8.26L20 9L14 14.74L15.18 21.02L10 17.77L4.82 21.02L6 14.74L0 9L6.91 8.26L10 2Z"/>
                    </svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"/>
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
