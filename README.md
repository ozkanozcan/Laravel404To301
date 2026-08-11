# Laravel 404 To 301 Redirect & Missing URL Logger

[![Tests](https://github.com/ozkanozcan/Laravel404To301/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/ozkanozcan/Laravel404To301/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/ozkanozcan/laravel-404-to-301.svg)](https://packagist.org/packages/ozkanozcan/laravel-404-to-301)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-10--13-red)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

An automated 404 URL redirect manager and missing URL logger package for **Laravel 10, 11, 12, and 13**.  
Manage 301/302 redirects dynamically via database rules, cache lookups for high performance, and automatically record unhandled 404 requests to database or log channels.

---

## Table of Contents

- [Requirements](#requirements)
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Middleware Setup](#middleware-setup)
- [Database Structure](#database-structure)
- [Artisan Commands](#artisan-commands)
- [Programmatic Usage & Facade](#programmatic-usage--facade)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [License](#license)

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.2 |
| Laravel | 10.x / 11.x / 12.x / 13.x |

---

## Features

- ⚡ **High Performance Caching**: Cache lookup results to avoid unnecessary database hits on every request.
- 🔀 **Dynamic 301 / 302 Redirects**: Easily manage source-to-destination redirect rules.
- 📊 **Hit Counter**: Keep track of how many times each redirect rule has been triggered.
- 📝 **Missing URL Tracker**: Automatically log unhandled 404 requests into a dedicated database table (`missing_urls`) with visitor IP, referer, user agent, and hit frequency.
- 📁 **Laravel Log Channel Support**: Optionally log 404 events to Laravel log channels (`daily`, `single`, `slack`, etc.).
- 🛡️ **Ignored File Patterns**: Skip static assets (`*.css`, `*.js`, `*.png`, `favicon.ico`, `robots.txt`) from triggering log/database records.
- 💻 **Artisan Tools**: Bulk import CSV/JSON rules, inspect missing URLs, and prune old missing URL logs.

---

## Installation

Install the package via Composer:

```bash
composer require ozkanozcan/laravel-404-to-301
```

Publish the configuration file and database migrations:

```bash
php artisan vendor:publish --tag=redirect404-config
php artisan vendor:publish --tag=redirect404-migrations
```

Run the database migrations:

```bash
php artisan migrate
```

---

## Configuration

The configuration file is published to `config/redirect404.php`. Here is an overview of the key options:

```php
return [
    // Enable or disable the redirect middleware globally
    'enabled' => env('REDIRECT_404_ENABLED', true),

    // Log missing 404 URLs to Laravel Log files
    'log_missing' => env('REDIRECT_404_LOG_MISSING', true),
    'log_channel' => env('REDIRECT_404_LOG_CHANNEL', 'daily'),

    // Save missing 404 URLs to the `missing_urls` database table
    'db_log_missing' => env('REDIRECT_404_DB_LOG', true),

    // Default status code (301 for permanent, 302 for temporary)
    'default_code' => env('REDIRECT_404_DEFAULT_CODE', 301),

    // Ignore patterns to exclude static assets
    'ignore_patterns' => [
        '*.css', '*.js', '*.ico', '*.png', '*.jpg', '*.jpeg',
        '*.gif', '*.svg', '*.webp', '*.woff2', '/favicon*', '/robots.txt',
    ],

    // Cache TTL in seconds (0 = disabled)
    'cache_ttl' => env('REDIRECT_404_CACHE_TTL', 3600),
];
```

---

## Middleware Setup

### Laravel 11 & 12 (`bootstrap/app.php`)

Add `redirect404` to the web middleware stack:

```php
use OzkanOzcan\Laravel404To301\Http\Middleware\Handle404Redirect;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            Handle404Redirect::class,
        ]);
    })
    ->create();
```

### Laravel 10 (`app/Http/Kernel.php`)

Add the middleware alias or append it to your `web` middleware group:

```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \OzkanOzcan\Laravel404To301\Http\Middleware\Handle404Redirect::class,
    ],
];
```

---

## Artisan Commands

### 1. Inspect Missing 404 URLs

List the top unhandled 404 paths recorded in the database:

```bash
php artisan redirect:missing --limit=20 --sort=hits
```

### 2. Bulk Import Redirect Rules (CSV or JSON)

Import redirect rules from a file:

```bash
php artisan redirect:sync path/to/redirects.csv
php artisan redirect:sync path/to/redirects.json --dry-run
```

Sample `redirects.csv`:
```csv
from_url,to_url,redirect_code
/old-about-us,/about,301
/legacy-contact,/contact,301
```

Sample `redirects.json`:
```json
[
  { "from_url": "/old-blog", "to_url": "/blog", "redirect_code": 301 },
  { "from_url": "/promo-2023", "to_url": "/offers", "redirect_code": 302 }
]
```

### 3. Prune Old Missing URL Logs

Clean up old missing URL records from the database:

```bash
php artisan redirect:prune-missing --days=30
```

---

## Programmatic Usage & Facade

You can use the `Redirect404` facade or Eloquent models directly in your application or admin panel:

```php
use OzkanOzcan\Laravel404To301\Models\Redirect;
use OzkanOzcan\Laravel404To301\Redirect404Facade as Redirect404;

// Add a new redirect rule
Redirect::create([
    'from_url'      => '/old-services',
    'to_url'        => '/services',
    'redirect_code' => 301,
    'is_active'     => true,
]);

// Flush cache after updating rules manually
Redirect404::flushCache();
```

---

## Testing

Run the test suite using PHPUnit:

```bash
composer install
vendor/bin/phpunit
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full version history.

---

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit changes using [Conventional Commits](https://www.conventionalcommits.org/)
4. Push to origin and open a Pull Request against `development`

---

## License

MIT © [Özkan Özcan — Özcan Teknoloji](https://ozcanyazilim.com.tr)  
See [LICENSE](LICENSE) for details.
