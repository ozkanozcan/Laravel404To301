<?php

namespace OzkanOzcan\Laravel404To301\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use OzkanOzcan\Laravel404To301\Models\Redirect;
use OzkanOzcan\Laravel404To301\Redirect404ServiceProvider;

class RedirectModelTest extends TestCase
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
        $app['config']->set('redirect404.cache_ttl', 0);
    }

    public function test_find_by_from_url_returns_active_redirect(): void
    {
        Redirect::create([
            'from_url'      => '/old-page',
            'to_url'        => '/new-page',
            'redirect_code' => 301,
            'is_active'     => true,
        ]);

        $found = Redirect::findByFromUrl('/old-page');

        $this->assertNotNull($found);
        $this->assertSame('/new-page', $found->to_url);
        $this->assertSame(301, $found->redirect_code);
    }

    public function test_find_by_from_url_returns_null_for_inactive_redirect(): void
    {
        Redirect::create([
            'from_url'      => '/inactive-page',
            'to_url'        => '/somewhere',
            'redirect_code' => 301,
            'is_active'     => false,
        ]);

        $found = Redirect::findByFromUrl('/inactive-page');

        $this->assertNull($found);
    }

    public function test_find_by_from_url_returns_null_when_no_match(): void
    {
        $found = Redirect::findByFromUrl('/non-existent');

        $this->assertNull($found);
    }

    public function test_increment_hits_increases_counter(): void
    {
        $redirect = Redirect::create([
            'from_url'      => '/old-page',
            'to_url'        => '/new-page',
            'redirect_code' => 301,
            'is_active'     => true,
            'hits'          => 5,
        ]);

        $redirect->incrementHits();

        $this->assertSame(6, $redirect->fresh()->hits);
    }

    public function test_redirect_uses_configured_table_name(): void
    {
        $redirect = new Redirect();

        $this->assertSame('redirects', $redirect->getTable());
    }

    public function test_redirect_code_is_cast_to_integer(): void
    {
        $redirect = Redirect::create([
            'from_url'      => '/cast-test',
            'to_url'        => '/target',
            'redirect_code' => '302',
            'is_active'     => true,
        ]);

        $this->assertIsInt($redirect->redirect_code);
        $this->assertSame(302, $redirect->redirect_code);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $redirect = Redirect::create([
            'from_url'      => '/bool-test',
            'to_url'        => '/target',
            'redirect_code' => 301,
            'is_active'     => 1,
        ]);

        $this->assertIsBool($redirect->is_active);
        $this->assertTrue($redirect->is_active);
    }
}
