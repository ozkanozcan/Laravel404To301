<?php

namespace OzkanOzcan\Laravel404To301;

use Illuminate\Support\ServiceProvider;
use OzkanOzcan\Laravel404To301\Console\ListMissingUrlsCommand;
use OzkanOzcan\Laravel404To301\Console\PruneMissingUrlsCommand;
use OzkanOzcan\Laravel404To301\Console\SyncRedirectsCommand;
use OzkanOzcan\Laravel404To301\Http\Middleware\Handle404Redirect;
use OzkanOzcan\Laravel404To301\Services\RedirectService;

class Redirect404ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/redirect404.php',
            'redirect404'
        );

        $this->app->singleton(RedirectService::class);

        // Allow resolving via alias
        $this->app->alias(RedirectService::class, 'redirect404');
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/redirect404.php' => config_path('redirect404.php'),
        ], 'redirect404-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'redirect404-migrations');

        // Publish language files
        $this->publishes([
            __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/redirect404'),
        ], 'redirect404-lang');

        // Load package translations (vendor override supported)
        $this->loadTranslationsFrom(
            __DIR__ . '/../resources/lang',
            'redirect404'
        );

        // Load migrations automatically (can be disabled if published)
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register middleware alias
        $this->app['router']->aliasMiddleware('redirect404', Handle404Redirect::class);

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListMissingUrlsCommand::class,
                PruneMissingUrlsCommand::class,
                SyncRedirectsCommand::class,
            ]);
        }
    }
}
