<?php

namespace OzkanOzcan\Laravel404To301\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OzkanOzcan\Laravel404To301\Models\MissingUrl;
use OzkanOzcan\Laravel404To301\Models\Redirect;

class RedirectService
{
    /**
     * Find an active redirect rule for the given URL path.
     *
     * Lookup order:
     *   1. Application cache (if cache_ttl > 0)
     *   2. Database
     *
     * @param  string  $url  The incoming request path (e.g. /old-page)
     */
    public function findRedirect(string $url): ?Redirect
    {
        $ttl = (int) config('redirect404.cache_ttl', 3600);

        if ($ttl > 0) {
            $cacheKey = $this->cacheKey($url);

            // Cache stores the Redirect model serialized, or false when no rule exists
            $cached = Cache::get($cacheKey, '__miss__');

            if ($cached !== '__miss__') {
                return $cached ?: null;
            }

            $redirect = Redirect::findByFromUrl($url);

            // Cache both hits (model) and misses (false) to prevent repeated DB queries
            Cache::put($cacheKey, $redirect ?: false, $ttl);

            return $redirect;
        }

        return Redirect::findByFromUrl($url);
    }

    /**
     * Increment the hit counter for a redirect and refresh its cache entry.
     */
    public function incrementHits(Redirect $redirect): void
    {
        $redirect->incrementHits();

        $ttl = (int) config('redirect404.cache_ttl', 3600);

        if ($ttl > 0) {
            // Refresh the cache with the updated model
            $redirect->refresh();
            Cache::put($this->cacheKey($redirect->from_url), $redirect, $ttl);
        }
    }

    /**
     * Record a 404 miss — write to DB and/or Laravel log depending on config.
     */
    public function recordMissing(string $url, Request $request): void
    {
        if (config('redirect404.db_log_missing', true)) {
            try {
                MissingUrl::recordHit($url, $request);
            } catch (\Throwable $e) {
                // Never let logging break the response
                Log::channel(config('redirect404.log_channel', 'daily'))
                    ->error('[Laravel404To301] Failed to record missing URL to DB: ' . $e->getMessage());
            }
        }

        if (config('redirect404.log_missing', true)) {
            Log::channel(config('redirect404.log_channel', 'daily'))
                ->warning('[Laravel404To301] 404 Not Found', [
                    'url'        => $url,
                    'referer'    => $request->header('referer'),
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
        }
    }

    /**
     * Flush all cached redirect rules.
     * Call this after adding or updating redirect rules in the database.
     */
    public function flushCache(): void
    {
        Cache::flush();
    }

    /**
     * Flush the cache entry for a single URL.
     */
    public function forgetCache(string $url): void
    {
        Cache::forget($this->cacheKey($url));
    }

    /**
     * Build the cache key for a given URL path.
     */
    protected function cacheKey(string $url): string
    {
        $prefix = config('redirect404.cache_prefix', 'redirect404');

        return $prefix . ':' . md5($url);
    }
}
