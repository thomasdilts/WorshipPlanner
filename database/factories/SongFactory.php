<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\Song;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Song>
 */
class SongFactory extends Factory
{
    protected $model = Song::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => fake()->unique()->sentence(3),
            'name2' => fake()->optional()->sentence(2),
            'author' => fake()->optional()->name(),
            'description' => fake()->optional()->paragraphs(2, true),
        ];
    }
}
