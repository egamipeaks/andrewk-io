<?php

return [

    /*
     * Whether to enable Pizzazz.
     */
    'enabled' => env('PIZZAZZ_ENABLED', true),

    /*
     * Whether to enable debug mode.
     */
    'debug' => env('PIZZAZZ_DEBUG', false),

    /*
     * Add query vars that stop caching.
     */
    'disallowed_query_vars' => [],

    /*
     * Whether to cache authenticated requests.
     */
    'cache_authenticated_requests' => env('PIZZAZZ_CACHE_AUTHENTICATED_REQUESTS', false),

    /*
     * Minimum content length required to cache a page.
     */
    'min_content_length' => env('PIZZAZZ_MIN_CONTENT_LENGTH', 255),

    /*
     * Cache length in seconds. Default: 86400 seconds (1 day).
     */
    'cache_length_in_seconds' => env('PIZZAZZ_CACHE_LENGTH', 86400),

    /*
     * When caching a URL all query arguments are removed to avoid
     * caching the same page multiple times with different query arguments.
     *
     * If you want to cache a page with specific query arguments, add them here.
     */
    'required_query_args' => [],
];
