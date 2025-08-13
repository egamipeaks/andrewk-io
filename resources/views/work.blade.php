@extends('layouts.base')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-4">Work</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">
            A collection of projects and packages I've built.
        </p>
    </div>

    <div class="space-y-8">
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
            <h2 class="text-2xl font-bold mb-3">
                <a href="https://craftsy.com"
                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    Craftsy
                </a>
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                The main website for TN Marketing, built with Laravel, Livewire, Alpine.JS, and more.
            </p>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                    Commercial Website
                </span>
                <a href="https://craftsy.com"
                   class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    Visit Site →
                </a>
            </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
            <h2 class="text-2xl font-bold mb-3">
                <a href="https://www.onyxsupplysolutions.com/"
                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    Onyx Supply Solutions
                </a>
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                A Laravel site I built for web to print company.
            </p>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                    Commercial Website
                </span>
                <a href="https://www.onyxsupplysolutions.com/"
                   class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    Visit Site →
                </a>
            </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
            <h2 class="text-2xl font-bold mb-3">
                <a href="https://github.com/egamipeaks/pizzazz"
                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    Pizzazz
                </a>
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                A Laravel package for efficient page caching with automatic cache invalidation and smart cache management.
            </p>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    Laravel Package
                </span>
                <a href="https://github.com/egamipeaks/pizzazz"
                   class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    View on GitHub →
                </a>
            </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
            <h2 class="text-2xl font-bold mb-3">
                <a href="https://github.com/egamipeaks/laravel-fedex-rate-estimator"
                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    Laravel FedEx Rate Estimator
                </a>
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                A Laravel package for integrating with FedEx shipping rate estimation API to calculate shipping costs for packages.
            </p>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    Shipping Integration
                </span>
                <a href="https://github.com/egamipeaks/laravel-fedex-rate-estimator"
                   class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    View on GitHub →
                </a>
            </div>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
            <h2 class="text-2xl font-bold mb-3">
                <a href="https://sttheklaorthodox.org/"
                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    St. Thekla Orthodox Church
                </a>
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Built with Statamic and Laravel for a church community website.
            </p>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                    Statamic/Laravel
                </span>
                <a href="https://sttheklaorthodox.org/"
                   class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                   target="_blank" rel="noopener noreferrer">
                    Visit Site →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
