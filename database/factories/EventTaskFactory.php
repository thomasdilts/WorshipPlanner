<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventTask;
use App\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTask>
 */
class EventTaskFactory extends Factory
{
    protected $model = EventTask::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'task_template_id' => TaskTemplate::factory(),
            'assigned_user_id' => null,
            'assigned_team_id' => null,
            'name' => null,
            'description' => null,
            'song' => null,
            'special_needs' => null,
            'duration' => 10,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
