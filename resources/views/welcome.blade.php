@extends('layouts.base')

@section('content')
    <div class="max-w-2xl mx-auto py-12">
        <div class="space-y-8">
            <div class="space-y-6">
                <div class="flex justify-center mb-8">
                    <img src="{{ asset('images/avatar_400w.jpg') }}" alt="Andrew Krzynowek" class="w-32 h-32 rounded-full object-cover object-top border-4 border-gray-200 dark:border-gray-700 shadow-lg">
                </div>

                <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100">
                    Hi! I'm Andrew.
                </h1>

                <div class="space-y-4 text-md text-gray-600 dark:text-gray-300 leading-relaxed">
                    <p>
                        I’m a freelance developer and consulting tech lead based in Sugar Land, TX. I help companies build scalable Laravel applications.
                    </p>

                    <p>
                        Right now, I serve as a fractional Lead Developer for an <a href="https://www.tnmarketing.com/" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">e-commerce video streaming platform</a> with millions of users, where I’ve led the migration from WooCommerce to Laravel.
                    </p>

{{--                    <p>--}}
{{--                        My specialties include backend architecture, clean code, team mentorship, and fractional leadership for agencies and small businesses.--}}
{{--                    </p>--}}

                    <p>
                        I also make <a href="https://open.spotify.com/artist/1VOaizHACRU5vgCQr6RCid?si=xtP-l1uyT9qFE-ti3P1BfQ" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">music</a> under the name Egami Peaks.
                    </p>
                </div>
            </div>

            <section class="mt-16 px-12 py-10 border border-green-200 dark:border-gray-700 rounded-lg bg-green-50 dark:bg-gray-800">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Want to hire me?</h2>

                <p class="text-gray-700 dark:text-gray-300 text-md mb-6">
                    I'm available for freelance or part-time contract work. Here’s how I can help:
                </p>

                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 text-md">
                    <li><strong class="font-bold text-gray-900">WordPress to Laravel migrations</strong> — Move your legacy code into a modern framework.</li>
                    <li><strong class="font-bold text-gray-900">Architecture and backend design</strong> — Scalable, testable, maintainable systems.</li>
                    <li><strong class="font-bold text-gray-900">Refactoring legacy Laravel apps</strong> — Clean up codebases and improve performance.</li>
                    <li><strong class="font-bold text-gray-900">Fractional Lead Developer roles</strong> — Team mentorship, technical leadership, and delivery oversight.</li>
                    <li><strong class="font-bold text-gray-900">Package development & API integrations</strong> — Custom tools and Laravel service layers.</li>
                </ul>

                <p class="mt-6 text-lg text-gray-700 dark:text-gray-300">
                    💼 Reach out via <a href="mailto:andrew.krzynowek@gmail.com" class="text-blue-600 dark:text-blue-400 hover:underline">email</a> or message me on <a href="https://www.linkedin.com/in/andrewkrzynowek/" class="text-blue-600 dark:text-blue-400 hover:underline">LinkedIn</a>.
                </p>
            </section>

            <div class="space-y-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Recent Posts</h2>
                <div class="space-y-4">
                    <article class="group">
                        <a href="/blog/laravel-wordpress-migration" class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <h3 class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400">Running Laravel and WordPress Together</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">How I Migrated a Monolith One Route at a Time</p>
                            <time class="text-xs text-gray-500 dark:text-gray-500 mt-2 block">July 12, 2025</time>
                        </a>
                    </article>
                </div>
            </div>
        </div>
    </div>
@endsection

