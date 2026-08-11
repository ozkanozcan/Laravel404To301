<?php

namespace OzkanOzcan\Laravel404To301\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use OzkanOzcan\Laravel404To301\Models\MissingUrl;
use OzkanOzcan\Laravel404To301\Models\Redirect;
use OzkanOzcan\Laravel404To301\Redirect404ServiceProvider;
use OzkanOzcan\Laravel404To301\Services\RedirectService;

class RedirectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [Redirect404ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('redirect404.cache_ttl', 0);
        $app['config']->set('redirect404.log_missing', false);
        $app['config']->set('redirect404.db_log_missing', false);
    }

    private function service(): RedirectService
    {
        return $this->app->make(RedirectService::class);
    }

    // ── findRedirect ──────────────────────────────────────────────────────────

    public function test_find_redirect_returns_active_rule(): void
    {
        Redirect::create([
            'from_url' => '/old', 'to_url' => '/new',
            'redirect_code' => 301, 'is_active' => true,
        ]);

        $result = $this->service()->findRedirect('/old');

        $this->assertNotNull($result);
        $this->assertSame('/new', $result->to_url);
    }

    public function test_find_redirect_returns_null_when_no_rule(): void
    {
        $result = $this->service()->findRedirect('/nowhere');

        $this->assertNull($result);
    }

    public function test_find_redirect_returns_null_for_inactive_rule(): void
    {
        Redirect::create([
            'from_url' => '/inactive', 'to_url' => '/target',
            'redirect_code' => 301, 'is_active' => false,
        ]);

        $result = $this->service()->findRedirect('/inactive');

        $this->assertNull($result);
    }

    // ── Cache ─────────────────────────────────────────────────────────────────

    public function test_find_redirect_caches_result(): void
    {
        $this->app['config']->set('redirect404.cache_ttl', 60);

        Redirect::create([
            'from_url' => '/cached', 'to_url' => '/target',
            'redirect_code' => 301, 'is_active' => true,
        ]);

        // First call — cache miss, reads from DB
        $first = $this->service()->findRedirect('/cached');
        $this->assertNotNull($first);

        // Delete the DB record to prove second call uses cache
        Redirect::where('from_url', '/cached')->delete();

        // Second call — should return cached result despite DB deletion
        $second = $this->service()->findRedirect('/cached');
        $this->assertNotNull($second);
        $this->assertSame('/target', $second->to_url);
    }

    public function test_find_redirect_caches_misses(): void
    {
        $this->app['config']->set('redirect404.cache_ttl', 60);

        // First call — cache miss
        $first = $this->service()->findRedirect('/will-miss');
        $this->assertNull($first);

        // Now create a rule in DB
        Redirect::create([
            'from_url' => '/will-miss', 'to_url' => '/now-exists',
            'redirect_code' => 301, 'is_active' => true,
        ]);

        // Second call — should still return null (cached miss)
        $second = $this->service()->findRedirect('/will-miss');
        $this->assertNull($second);
    }

    public function test_forget_cache_clears_single_url(): void
    {
        $this->app['config']->set('redirect404.cache_ttl', 60);

        Redirect::create([
            'from_url' => '/forget-me', 'to_url' => '/target',
            'redirect_code' => 301, 'is_active' => true,
        ]);

        // Cache it
        $this->service()->findRedirect('/forget-me');

        // Clear from DB
        Redirect::where('from_url', '/forget-me')->delete();

        // Forget cache
        $this->service()->forgetCache('/forget-me');

        // Should now return null (reads fresh from DB)
        $result = $this->service()->findRedirect('/forget-me');
        $this->assertNull($result);
    }

    // ── recordMissing ─────────────────────────────────────────────────────────

    public function test_record_missing_writes_to_db_when_enabled(): void
    {
        $this->app['config']->set('redirect404.db_log_missing', true);

        $request = Request::create('/missing-page', 'GET');
        $this->service()->recordMissing('/missing-page', $request);

        $this->assertDatabaseHas('missing_urls', ['url' => '/missing-page', 'hit_count' => 1]);
    }

    public function test_record_missing_increments_hit_count_on_repeat(): void
    {
        $this->app['config']->set('redirect404.db_log_missing', true);

        $request = Request::create('/repeat-404', 'GET');
        $this->service()->recordMissing('/repeat-404', $request);
        $this->service()->recordMissing('/repeat-404', $request);
        $this->service()->recordMissing('/repeat-404', $request);

        $record = MissingUrl::where('url', '/repeat-404')->first();
        $this->assertNotNull($record);
        $this->assertSame(3, $record->hit_count);
    }

    public function test_record_missing_does_not_write_db_when_disabled(): void
    {
        $this->app['config']->set('redirect404.db_log_missing', false);

        $request = Request::create('/no-db', 'GET');
        $this->service()->recordMissing('/no-db', $request);

        $this->assertDatabaseMissing('missing_urls', ['url' => '/no-db']);
    }

    public function test_record_missing_writes_to_log_when_enabled(): void
    {
        $this->app['config']->set('redirect404.log_missing', true);
        $this->app['config']->set('redirect404.log_channel', 'daily');

        Log::shouldReceive('channel')->with('daily')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $request = Request::create('/log-me', 'GET');
        $this->service()->recordMissing('/log-me', $request);
    }

    // ── incrementHits ─────────────────────────────────────────────────────────

    public function test_increment_hits_updates_db_record(): void
    {
        $redirect = Redirect::create([
            'from_url' => '/hit-test', 'to_url' => '/target',
            'redirect_code' => 301, 'is_active' => true, 'hits' => 0,
        ]);

        $this->service()->incrementHits($redirect);

        $this->assertSame(1, $redirect->fresh()->hits);
    }
}
