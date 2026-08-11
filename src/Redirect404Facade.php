<?php

namespace OzkanOzcan\Laravel404To301;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \OzkanOzcan\Laravel404To301\Models\Redirect|null findRedirect(string $url)
 * @method static void incrementHits(\OzkanOzcan\Laravel404To301\Models\Redirect $redirect)
 * @method static void recordMissing(string $url, \Illuminate\Http\Request $request)
 * @method static void flushCache()
 * @method static void forgetCache(string $url)
 *
 * @see \OzkanOzcan\Laravel404To301\Services\RedirectService
 */
class Redirect404Facade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \OzkanOzcan\Laravel404To301\Services\RedirectService::class;
    }
}
