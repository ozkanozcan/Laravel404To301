<?php

namespace OzkanOzcan\Laravel404To301\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use OzkanOzcan\Laravel404To301\Models\MissingUrl;
use OzkanOzcan\Laravel404To301\Models\Redirect;
use OzkanOzcan\Laravel404To301\Redirect404ServiceProvider;

class ConsoleCommandsTest extends TestCase
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
    }

    // ── redirect:missing ──────────────────────────────────────────────────────

    public function test_list_missing_urls_command_shows_empty_message_when_no_records(): void
    {
        $this->artisan('redirect:missing')
            ->expectsOutputToContain('No missing URLs recorded yet.')
            ->assertExitCode(0);
    }

    public function test_list_missing_urls_command_displays_table(): void
    {
        MissingUrl::create([
            'url'          => '/missing-test-url',
            'referer'      => 'https://google.com',
            'user_agent'   => 'TestAgent',
            'ip_address'   => '127.0.0.1',
            'hit_count'    => 5,
            'last_seen_at' => now(),
        ]);

        $this->artisan('redirect:missing')
            ->expectsOutputToContain('Missing URLs')
            ->expectsOutputToContain('/missing-test-url')
            ->assertExitCode(0);
    }

    // ── redirect:prune-missing ───────────────────────────────────────────────

    public function test_prune_missing_command_deletes_old_records(): void
    {
        MissingUrl::create([
            'url'          => '/old-404',
            'hit_count'    => 1,
            'last_seen_at' => now()->subDays(31),
        ]);

        MissingUrl::create([
            'url'          => '/recent-404',
            'hit_count'    => 1,
            'last_seen_at' => now()->subDays(5),
        ]);

        $this->artisan('redirect:prune-missing', ['--days' => 30])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('missing_urls', ['url' => '/old-404']);
        $this->assertDatabaseHas('missing_urls', ['url' => '/recent-404']);
    }

    public function test_prune_missing_command_dry_run(): void
    {
        MissingUrl::create([
            'url'          => '/old-404',
            'hit_count'    => 1,
            'last_seen_at' => now()->subDays(31),
        ]);

        $this->artisan('redirect:prune-missing', ['--days' => 30, '--dry-run' => true])
            ->expectsOutputToContain('[Dry run]')
            ->assertExitCode(0);

        $this->assertDatabaseHas('missing_urls', ['url' => '/old-404']);
    }

    // ── redirect:sync ────────────────────────────────────────────────────────

    public function test_sync_redirects_command_imports_json_file(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'redirects_') . '.json';
        $json = json_encode([
            ['from_url' => '/old-json-1', 'to_url' => '/new-json-1', 'redirect_code' => 301],
            ['from_url' => '/old-json-2', 'to_url' => '/new-json-2', 'redirect_code' => 302],
        ]);
        file_put_contents($filePath, $json);

        $this->artisan('redirect:sync', ['file' => $filePath])
            ->assertExitCode(0);

        $this->assertDatabaseHas('redirects', ['from_url' => '/old-json-1', 'to_url' => '/new-json-1', 'redirect_code' => 301]);
        $this->assertDatabaseHas('redirects', ['from_url' => '/old-json-2', 'to_url' => '/new-json-2', 'redirect_code' => 302]);

        @unlink($filePath);
    }

    public function test_sync_redirects_command_imports_csv_file(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'redirects_') . '.csv';
        $csv = "from_url,to_url,redirect_code\n/old-csv-1,/new-csv-1,301\n/old-csv-2,/new-csv-2,301";
        file_put_contents($filePath, $csv);

        $this->artisan('redirect:sync', ['file' => $filePath])
            ->assertExitCode(0);

        $this->assertDatabaseHas('redirects', ['from_url' => '/old-csv-1', 'to_url' => '/new-csv-1']);
        $this->assertDatabaseHas('redirects', ['from_url' => '/old-csv-2', 'to_url' => '/new-csv-2']);

        @unlink($filePath);
    }
}
