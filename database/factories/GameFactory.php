<?php

namespace Database\Factories;

use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unknownSupport = collect(Technology::cases())
            ->mapWithKeys(fn (Technology $technology) => [$technology->value => Support::Unknown])
            ->all();

        return [
            'pcgw_page_id' => fake()->unique()->numberBetween(1, 9_999_999),
            'title' => fake()->unique()->words(3, true),
            ...$unknownSupport,
            'upscaling_tech' => null,
            'frame_gen_tech' => null,
            'synced_at' => now(),
        ];
    }
}
