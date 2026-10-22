<?php

namespace Database\Factories;

use App\Enums\SyncStatus;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncRun>
 */
class SyncRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => SyncStatus::Completed,
            'last_offset' => 0,
            'pages_processed' => 0,
            'error' => null,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ];
    }

    /**
     * Una ejecución que se cortó a mitad de camino y puede reanudarse.
     */
    public function interrupted(int $lastOffset): static
    {
        return $this->state(fn () => [
            'status' => SyncStatus::Running,
            'last_offset' => $lastOffset,
            'pages_processed' => $lastOffset,
            'finished_at' => null,
        ]);
    }

    public function failed(string $error = 'Connection timed out'): static
    {
        return $this->state(fn () => [
            'status' => SyncStatus::Failed,
            'error' => $error,
        ]);
    }
}
