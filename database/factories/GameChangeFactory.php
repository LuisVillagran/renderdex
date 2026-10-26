<?php

namespace Database\Factories;

use App\Enums\ChangeType;
use App\Enums\Technology;
use App\Models\Game;
use App\Models\GameChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameChange>
 */
class GameChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'technology' => Technology::Dlss,
            'type' => ChangeType::Added,
            'previous_version' => null,
            'version' => '4',
            'detected_at' => now(),
        ];
    }
}
