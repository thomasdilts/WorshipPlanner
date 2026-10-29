<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\Team;
use App\Models\TeamType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => fake()->randomElement([
                'Worship band',
                'Choir',
                'Tech team',
                'Greeters',
                'Kids ministry',
            ]),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Team $team): void {
            if ($team->team_type_id || ! $team->church_id) {
                return;
            }

            $team->team_type_id = TeamType::query()
                ->where('church_id', $team->church_id)
                ->value('id')
                ?? TeamType::factory()->create(['church_id' => $team->church_id])->id;
        });
    }
}
