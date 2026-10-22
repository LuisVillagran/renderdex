<?php

namespace App\Console\Commands;

use App\Enums\SyncStatus;
use App\Models\SyncRun;
use App\Services\Pcgw\PcgwClient;
use App\Services\Pcgw\PcgwException;
use App\Services\Pcgw\PcgwGameImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pcgw:sync
    {--limit= : Maximum number of wiki pages to process (for development)}
    {--fresh : Start from the first page instead of resuming an unfinished run}')]
#[Description('Sync game render-technology data from PCGamingWiki')]
class SyncPcgw extends Command
{
    public function handle(PcgwClient $client, PcgwGameImporter $importer): int
    {
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $offset = $this->option('fresh') ? 0 : $this->resumeOffset();

        $run = SyncRun::create([
            'status' => SyncStatus::Running,
            'last_offset' => $offset,
            'started_at' => now(),
        ]);

        if ($offset > 0) {
            $this->components->info("Resuming from page {$offset}.");
        }

        try {
            $remaining = max(0, $client->countGames() - $offset);
            $progress = $this->output->createProgressBar($limit !== null ? min($limit, $remaining) : $remaining);
            $pageSize = config('pcgw.page_size');

            while (true) {
                $requested = $limit !== null ? min($pageSize, $limit - $run->pages_processed) : $pageSize;
                $games = array_slice($client->games($offset, $requested), 0, $requested);

                $importer->import($games, now());

                $offset += count($games);
                $run->update([
                    'last_offset' => $offset,
                    'pages_processed' => $run->pages_processed + count($games),
                ]);
                $progress->advance(count($games));

                $reachedEnd = count($games) < $requested;
                $reachedLimit = $limit !== null && $run->pages_processed >= $limit;

                if ($reachedEnd || $reachedLimit) {
                    break;
                }
            }
        } catch (PcgwException $exception) {
            $run->update([
                'status' => SyncStatus::Failed,
                'error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $this->newLine();
            $this->components->error("Sync failed after {$run->pages_processed} pages: {$exception->getMessage()}");
            $this->components->warn('Run the command again to resume from where it stopped.');

            return self::FAILURE;
        }

        $run->update([
            'status' => SyncStatus::Completed,
            'finished_at' => now(),
        ]);

        $progress->finish();
        $this->newLine(2);
        $this->components->info("Synced {$run->pages_processed} pages from PCGamingWiki.");

        return self::SUCCESS;
    }

    /**
     * Si la última ejecución no terminó, sigue desde donde quedó.
     */
    private function resumeOffset(): int
    {
        $lastRun = SyncRun::latest('id')->first();

        if ($lastRun === null || $lastRun->status === SyncStatus::Completed) {
            return 0;
        }

        // Una ejecución que sigue en "running" se cortó sin registrar el error (p. ej. el proceso murió).
        if ($lastRun->status === SyncStatus::Running) {
            $lastRun->update([
                'status' => SyncStatus::Failed,
                'error' => 'Interrupted before finishing.',
                'finished_at' => now(),
            ]);
        }

        return $lastRun->last_offset;
    }
}
