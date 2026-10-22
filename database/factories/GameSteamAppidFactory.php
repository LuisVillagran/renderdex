<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameSteamAppid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSteamAppid>
 */
class GameSteamAppidFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'steam_appid' => fake()->unique()->numberBetween(10, 4_000_000),
            'game_id' => Game::factory(),
        ];
    }
}
