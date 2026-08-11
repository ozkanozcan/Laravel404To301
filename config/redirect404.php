<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable / Disable the Redirect Middleware
    |--------------------------------------------------------------------------
    |
    | When set to false, the middleware will pass all requests through
    | without any redirect lookup or logging.
    |
    */
    'enabled' => env('REDIRECT_404_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Missing URLs to Laravel Log
    |--------------------------------------------------------------------------
    |
    | When true, every 404 that has no matching redirect rule will be written
    | to the Laravel log (using the channel defined in 'log_channel').
    |
    */
    'log_missing' => env('REDIRECT_404_LOG_MISSING', true),

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | The Laravel log channel to use when writing missing URL entries.
    | Must match a channel defined in config/logging.php.
    | Example: 'daily', 'stack', 'slack'
    |
    */
    'log_channel' => env('REDIRECT_404_LOG_CHANNEL', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Save Missing URLs to Database
    |--------------------------------------------------------------------------
    |
    | When true, every 404 that has no matching redirect rule will be recorded
    | in the `missing_urls` table (with upsert — hit_count increments on repeat).
    | You can use `php artisan redirect:missing` to inspect these records.
    |
    */
    'db_log_missing' => env('REDIRECT_404_DB_LOG', true),

    /*
    |--------------------------------------------------------------------------
    | Default Redirect Code
    |--------------------------------------------------------------------------
    |
    | The HTTP status code used for redirects when a rule does not specify one.
    | Recommended: 301 (Permanent) for SEO, 302 (Temporary) for testing.
    |
    */
    'default_code' => env('REDIRECT_404_DEFAULT_CODE', 301),

    /*
    |--------------------------------------------------------------------------
    | Ignore Patterns
    |--------------------------------------------------------------------------
    |
    | Requests matching these patterns will be silently ignored — no redirect
    | lookup and no logging. Use fnmatch() glob patterns.
    |
    | This prevents noise from browser auto-requests for static assets.
    |
    */
    'ignore_patterns' => [
        '*.css',
        '*.js',
        '*.ico',
        '*.png',
        '*.jpg',
        '*.jpeg',
        '*.gif',
        '*.svg',
        '*.webp',
        '*.avif',
        '*.woff',
        '*.woff2',
        '*.ttf',
        '*.map',
        '/favicon*',
        '/robots.txt',
        '/sitemap*',
        '/.well-known/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | How long to cache redirect rules in the application cache.
    | Set to 0 to disable caching (always read from database).
    | Recommended: 3600 (1 hour) for production.
    |
    */
    'cache_ttl' => env('REDIRECT_404_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for redirect cache keys. Change this if you have multiple
    | applications sharing the same cache store.
    |
    */
    'cache_prefix' => env('REDIRECT_404_CACHE_PREFIX', 'redirect404'),

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the database table names used by this package.
    |
    */
    'tables' => [
        'redirects'    => 'redirects',
        'missing_urls' => 'missing_urls',
    ],
];
