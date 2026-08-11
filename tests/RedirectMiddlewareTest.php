<?php

namespace OzkanOzcan\Laravel404To301\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use OzkanOzcan\Laravel404To301\Http\Middleware\Handle404Redirect;
use OzkanOzcan\Laravel404To301\Models\Redirect;
use OzkanOzcan\Laravel404To301\Redirect404ServiceProvider;
use OzkanOzcan\Laravel404To301\Services\RedirectService;

class RedirectMiddlewareTest extends TestCase
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
        $app['config']->set('redirect404.enabled', true);
        $app['config']->set('redirect404.ignore_patterns', [
            '*.css', '*.js', '*.ico', '/favicon*',
        ]);
    }

    private function middleware(): Handle404Redirect
    {
        return $this->app->make(Handle404Redirect::class);
    }

    private function make404(string $path = '/missing'): \Illuminate\Http\Request
    {
        return \Illuminate\Http\Request::create($path, 'GET');
    }

    private function makeNext(int $status = 404): \Closure
    {
        return fn ($request) => response('Not Found', $status);
    }

    // ── 301 Redirect ─────────────────────────────────────────────────────────

    public function test_middleware_redirects_on_matching_rule(): void
    {
        Redirect::create([
            'from_url' => '/old-page', 'to_url' => '/new-page',
            'redirect_code' => 301, 'is_active' => true,
        ]);

        $request  = $this->make404('/old-page');
        $response = $this->middleware()->handle($request, $this->makeNext(404));

        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringContainsString('/new-page', $response->headers->get('Location'));
    }

    public function test_middleware_uses_redirect_code_from_rule(): void
    {
        Redirect::create([
            'from_url' => '/temp-page', 'to_url' => '/target',
            'redirect_code' => 302, 'is_active' => true,
        ]);

        $response = $this->middleware()->handle($this->make404('/temp-page'), $this->makeNext(404));

        $this->assertSame(302, $response->getStatusCode());
    }

    // ── Pass-through cases ────────────────────────────────────────────────────

    public function test_middleware_passes_non_404_responses_unchanged(): void
    {
        Redirect::create([
            'from_url' => '/some-page', 'to_url' => '/target',
            'redirect_code' => 301, 'is_active' => true,
        ]);

        // Simulate a 200 response — middleware should NOT redirect
        $response = $this->middleware()->handle($this->make404('/some-page'), $this->makeNext(200));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_middleware_passes_through_when_disabled(): void
    {
        $this->app['config']->set('redirect404.enabled', false);

        Redirect::create([
            'from_url' => '/old-page', 'to_url' => '/new-page',
            'redirect_code' => 301, 'is_active' => true,
        ]);

        $response = $this->middleware()->handle($this->make404('/old-page'), $this->makeNext(404));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_middleware_passes_through_inactive_redirect(): void
    {
        Redirect::create([
            'from_url' => '/inactive', 'to_url' => '/target',
            'redirect_code' => 301, 'is_active' => false,
        ]);

        $response = $this->middleware()->handle($this->make404('/inactive'), $this->makeNext(404));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_middleware_passes_through_when_no_rule(): void
    {
        $response = $this->middleware()->handle($this->make404('/no-rule'), $this->makeNext(404));

        $this->assertSame(404, $response->getStatusCode());
    }

    // ── Ignore patterns ───────────────────────────────────────────────────────

    public function test_middleware_ignores_css_files(): void
    {
        Redirect::create([
            'from_url' => '/style.css', 'to_url' => '/new.css',
            'redirect_code' => 301, 'is_active' => true,
        ]);

        $response = $this->middleware()->handle($this->make404('/style.css'), $this->makeNext(404));

        // Should pass through — CSS files are ignored
        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_middleware_ignores_js_files(): void
    {
        $response = $this->middleware()->handle($this->make404('/app.js'), $this->makeNext(404));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_middleware_ignores_favicon(): void
    {
        $response = $this->middleware()->handle($this->make404('/favicon.ico'), $this->makeNext(404));

        $this->assertSame(404, $response->getStatusCode());
    }

    // ── Missing URL logging ───────────────────────────────────────────────────

    public function test_middleware_records_missing_url_to_db(): void
    {
        $this->app['config']->set('redirect404.db_log_missing', true);

        $response = $this->middleware()->handle($this->make404('/unmatched-path'), $this->makeNext(404));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertDatabaseHas('missing_urls', ['url' => '/unmatched-path']);
    }

    public function test_middleware_does_not_record_missing_url_when_db_disabled(): void
    {
        $this->app['config']->set('redirect404.db_log_missing', false);

        $this->middleware()->handle($this->make404('/no-record'), $this->makeNext(404));

        $this->assertDatabaseMissing('missing_urls', ['url' => '/no-record']);
    }

    // ── Hit counter ───────────────────────────────────────────────────────────

    public function test_middleware_increments_hits_on_each_redirect(): void
    {
        Redirect::create([
            'from_url' => '/count-me', 'to_url' => '/target',
            'redirect_code' => 301, 'is_active' => true, 'hits' => 0,
        ]);

        $this->middleware()->handle($this->make404('/count-me'), $this->makeNext(404));
        $this->middleware()->handle($this->make404('/count-me'), $this->makeNext(404));
        $this->middleware()->handle($this->make404('/count-me'), $this->makeNext(404));

        $this->assertSame(3, Redirect::where('from_url', '/count-me')->first()->hits);
    }

    // ── Service Provider ─────────────────────────────────────────────────────

    public function test_service_is_resolved_from_container(): void
    {
        $service = $this->app->make(RedirectService::class);

        $this->assertInstanceOf(RedirectService::class, $service);
    }

    public function test_service_is_singleton(): void
    {
        $s1 = $this->app->make(RedirectService::class);
        $s2 = $this->app->make(RedirectService::class);

        $this->assertSame($s1, $s2);
    }

    public function test_middleware_alias_is_registered(): void
    {
        $middleware = $this->app['router']->getMiddleware();

        $this->assertArrayHasKey('redirect404', $middleware);
        $this->assertSame(Handle404Redirect::class, $middleware['redirect404']);
    }
}
