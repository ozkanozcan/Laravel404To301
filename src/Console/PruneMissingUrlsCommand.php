<?php

namespace OzkanOzcan\Laravel404To301\Console;

use Illuminate\Console\Command;
use OzkanOzcan\Laravel404To301\Models\MissingUrl;

class PruneMissingUrlsCommand extends Command
{
    protected $signature = 'redirect:prune-missing
                            {--days=30 : Delete records not seen in this many days}
                            {--dry-run : Show how many records would be deleted without actually deleting}';

    protected $description = 'Delete old missing URL records that have not been seen recently';

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days);

        $query = MissingUrl::where('last_seen_at', '<', $cutoff)
            ->orWhereNull('last_seen_at');

        $count = $query->count();

        if ($count === 0) {
            $this->components->info("No missing URL records older than {$days} days found.");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->components->warn("[Dry run] Would delete {$count} missing URL record(s) older than {$days} days.");

            return self::SUCCESS;
        }

        $query->delete();

        $this->components->info("Deleted {$count} missing URL record(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
