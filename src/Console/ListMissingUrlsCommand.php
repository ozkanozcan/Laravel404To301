<?php

namespace OzkanOzcan\Laravel404To301\Console;

use Illuminate\Console\Command;
use OzkanOzcan\Laravel404To301\Models\MissingUrl;

class ListMissingUrlsCommand extends Command
{
    protected $signature = 'redirect:missing
                            {--limit=20 : Maximum number of records to display}
                            {--min-hits=1 : Only show URLs with at least this many hits}
                            {--sort=hits : Sort by: hits, last_seen_at, created_at}';

    protected $description = 'List the URLs that returned 404 and have not yet been redirected';

    public function handle(): int
    {
        $limit   = (int) $this->option('limit');
        $minHits = (int) $this->option('min-hits');
        $sort    = $this->option('sort');

        $allowedSorts = ['hits' => 'hit_count', 'last_seen_at' => 'last_seen_at', 'created_at' => 'created_at'];
        $orderBy      = $allowedSorts[$sort] ?? 'hit_count';

        $records = MissingUrl::where('hit_count', '>=', $minHits)
            ->orderByDesc($orderBy)
            ->limit($limit)
            ->get(['url', 'hit_count', 'referer', 'last_seen_at']);

        if ($records->isEmpty()) {
            $this->components->info('No missing URLs recorded yet.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->components->twoColumnDetail('<fg=yellow>Missing URLs</>', '<fg=yellow>Hits</>');
        $this->line('');

        $this->table(
            ['URL', 'Hits', 'Last Referer', 'Last Seen'],
            $records->map(fn ($r) => [
                $r->url,
                $r->hit_count,
                $r->referer ? (strlen($r->referer) > 60 ? substr($r->referer, 0, 57) . '...' : $r->referer) : '—',
                $r->last_seen_at?->diffForHumans() ?? '—',
            ])
        );

        $this->line('');
        $this->components->info("Showing {$records->count()} record(s). Use --limit to show more.");

        return self::SUCCESS;
    }
}
