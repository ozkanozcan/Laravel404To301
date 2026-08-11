<?php

namespace OzkanOzcan\Laravel404To301\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use OzkanOzcan\Laravel404To301\Exceptions\InvalidRedirectException;
use OzkanOzcan\Laravel404To301\Models\Redirect;
use OzkanOzcan\Laravel404To301\Services\RedirectService;

class SyncRedirectsCommand extends Command
{
    protected $signature = 'redirect:sync
                            {file : Path to CSV or JSON file containing redirect rules}
                            {--dry-run : Validate the file without writing to the database}
                            {--skip-errors : Skip invalid rows instead of aborting}';

    protected $description = 'Import redirect rules from a CSV or JSON file';

    public function __construct(protected RedirectService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $file   = $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');

        if (! file_exists($file)) {
            $this->components->error("File not found: {$file}");

            return self::FAILURE;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $rows = match ($extension) {
            'json' => $this->parseJson($file),
            'csv'  => $this->parseCsv($file),
            default => null,
        };

        if ($rows === null) {
            $this->components->error("Unsupported file type [{$extension}]. Use .csv or .json");

            return self::FAILURE;
        }

        $this->info("Parsed " . count($rows) . " row(s) from [{$file}]");
        $this->line('');

        $imported = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($rows as $index => $row) {
            $lineNum = $index + 1;

            // Validate required fields
            $validator = Validator::make($row, [
                'from_url' => 'required|string|starts_with:/',
                'to_url'   => 'required|string',
                'redirect_code' => 'sometimes|integer|in:301,302',
            ]);

            if ($validator->fails()) {
                $this->components->warn("Row {$lineNum} skipped — " . $validator->errors()->first());
                $skipped++;

                continue;
            }

            try {
                $fromUrl = $row['from_url'];
                $toUrl   = $row['to_url'];
                $code    = (int) ($row['redirect_code'] ?? config('redirect404.default_code', 301));

                if ($fromUrl === $toUrl) {
                    throw InvalidRedirectException::circular($fromUrl);
                }

                if (! in_array($code, [301, 302], true)) {
                    throw InvalidRedirectException::invalidCode($code);
                }

                if (! $dryRun) {
                    $isActive = $row['is_active'] ?? true;
                    // Handle string values from CSV ('true'/'false'/'1'/'0')
                    if (is_string($isActive)) {
                        $isActive = ! in_array(strtolower(trim($isActive)), ['false', '0', 'no', ''], true);
                    }

                    Redirect::updateOrCreate(
                        ['from_url' => $fromUrl],
                        [
                            'to_url'        => $toUrl,
                            'redirect_code' => $code,
                            'is_active'     => (bool) $isActive,
                            'note'          => $row['note'] ?? null,
                        ]
                    );

                    $this->service->forgetCache($fromUrl);
                }

                $this->line("  <fg=green>✓</> {$fromUrl} → {$toUrl} [{$code}]");
                $imported++;
            } catch (\Throwable $e) {
                $this->components->error("Row {$lineNum}: " . $e->getMessage());
                $errors++;

                if (! $this->option('skip-errors')) {
                    return self::FAILURE;
                }
            }
        }

        $this->line('');

        if ($dryRun) {
            $this->components->warn("[Dry run] Would import {$imported} rule(s). Skipped: {$skipped}. Errors: {$errors}.");
        } else {
            $this->components->info("Import complete. Imported: {$imported}. Skipped: {$skipped}. Errors: {$errors}.");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function parseJson(string $file): array
    {
        $content = file_get_contents($file);
        $data    = json_decode($content, true);

        if (! is_array($data)) {
            $this->components->error('Invalid JSON structure. Expected an array of objects.');

            return [];
        }

        return $data;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function parseCsv(string $file): array
    {
        $rows   = [];
        $handle = fopen($file, 'r');

        if (! $handle) {
            return [];
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map('trim', $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, array_map('trim', $row));
            }
        }

        fclose($handle);

        return $rows;
    }
}
