<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\Team;
use App\Models\Unavailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unavailability>
 */
class UnavailabilityFactory extends Factory
{
    protected $model = Unavailability::class;

    public function definition(): array
    {
        $starts = now()->addDays(fake()->numberBetween(1, 30))->startOfDay();

        return [
            'church_id' => Church::factory(),
            'user_id' => User::factory(),
            'team_id' => null,
            'starts_on' => $starts->toDateString(),
            'ends_on' => $starts->copy()->addDays(fake()->numberBetween(0, 7))->toDateString(),
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function forTeam(?Team $team = null): static
    {
        return $this->state(function (array $attributes) use ($team): array {
            $team ??= Team::factory()->create([
                'church_id' => $attributes['church_id'] ?? Church::factory(),
            ]);

            return [
                'church_id' => $team->church_id,
                'user_id' => null,
                'team_id' => $team->id,
            ];
        });
    }
}
