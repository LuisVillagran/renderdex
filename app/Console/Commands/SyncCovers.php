<?php

namespace App\Console\Commands;

use App\Services\Artwork\ArtworkSync;
use App\Services\Artwork\SteamGridDbException;
use App\Services\Steam\Exceptions\SteamUnavailableException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('covers:sync
    {--limit= : Maximum number of games to process in each step (for development)}
    {--fresh : Ask again for every game, even if it was checked recently}')]
#[Description('Save Steam store artwork and SteamGridDB covers for games')]
class SyncCovers extends Command
{
    public function handle(ArtworkSync $sync): int
    {
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $fresh = (bool) $this->option('fresh');
        $succeeded = true;

        $this->components->info('Steam store artwork');
        $progress = $this->output->createProgressBar(min($limit ?? PHP_INT_MAX, $sync->pendingSteam($fresh)->count()));

        try {
            $processed = $sync->syncSteam($fresh, $limit, fn (int $count) => $progress->advance($count));
            $progress->finish();
            $this->newLine(2);
            $this->components->info("Checked Steam artwork for {$processed} games.");
        } catch (SteamUnavailableException $exception) {
            $this->newLine();
            $this->components->error("Steam artwork stopped: {$exception->getMessage()} Run the command again to continue.");
            $succeeded = false;
        }

        if (! $sync->gridsConfigured()) {
            $this->components->warn('SteamGridDB skipped: STEAMGRIDDB_API_KEY is not set.');

            return $succeeded ? self::SUCCESS : self::FAILURE;
        }

        $this->components->info('SteamGridDB covers');
        $gridLimit = $limit ?? config('services.steamgriddb.per_run');
        $progress = $this->output->createProgressBar(min($gridLimit, $sync->pendingGrids($fresh)->count()));

        try {
            $processed = $sync->syncGrids($fresh, $gridLimit, fn (int $count) => $progress->advance($count));
            $progress->finish();
            $this->newLine(2);
            $this->components->info("Checked SteamGridDB for {$processed} games.");
        } catch (SteamGridDbException $exception) {
            $this->newLine();
            $this->components->error("SteamGridDB stopped: {$exception->getMessage()} Run the command again to continue.");
            $succeeded = false;
        }

        return $succeeded ? self::SUCCESS : self::FAILURE;
    }
}
