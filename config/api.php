<?php

return [
    /**
     * API Rate Limiting Configuration
     */

    'rate_limits' => [
        // Tier: [requests per minute, requests per second]
        'public' => [
            'requests' => 30,
            'per_second' => 5,
            'scope' => 'api:guest',
        ],
        'standard' => [
            'requests' => 60,
            'per_second' => 10,
            'scope' => 'api:customer',
        ],
        'premium' => [
            'requests' => 300,
            'per_second' => 50,
            'scope' => 'api:premium',
        ],
        'admin' => [
            'requests' => 1000,
            'per_second' => 100,
            'scope' => 'api:admin',
        ],
        'auth' => [
            'requests' => 10,
            'per_second' => 2,
            'scope' => 'api:auth',
        ],
    ],

    // Cache store for rate limiting
    'cache_store' => env('RATE_LIMIT_CACHE', 'cache'),

    // Cache key prefix
    'cache_prefix' => 'rate_limit:',

    // Enable/disable rate limiting globally
    'enabled' => env('RATE_LIMITING_ENABLED', true),

    /**
     * API Resource Optimization Configuration
     */

    // Enable snake_case to camelCase transformation
    'transform_keys' => env('API_TRANSFORM_KEYS', true),

    // Enable field filtering
    'field_filtering' => env('API_FIELD_FILTERING', true),

    // Enable cursor pagination
    'cursor_pagination' => env('API_CURSOR_PAGINATION', true),

    // Enable ETag caching
    'etag_caching' => env('API_ETAG_CACHING', true),

    // Default pagination size
    'pagination_size' => env('API_PAGINATION_SIZE', 20),

    // Maximum pagination size
    'max_pagination_size' => 100,

    // ETag cache duration (minutes)
    'etag_cache_duration' => 60,

    // API version
    'version' => 'v1',
];
