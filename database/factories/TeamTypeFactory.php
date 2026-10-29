<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\TeamType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamType>
 */
class TeamTypeFactory extends Factory
{
    protected $model = TeamType::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => fake()->unique()->randomElement([
                'Music team',
                'Prayer team',
                'Greeter team',
                'Tech team',
                'Kids team',
                'Hospitality team',
            ]).' '.fake()->numerify('##'),
        ];
    }
}
