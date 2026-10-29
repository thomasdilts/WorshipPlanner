<?php

namespace Database\Factories;

use App\Enums\AssignmentResponseStatus;
use App\Models\EventTask;
use App\Models\EventTaskResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTaskResponse>
 */
class EventTaskResponseFactory extends Factory
{
    protected $model = EventTaskResponse::class;

    public function definition(): array
    {
        return [
            'event_task_id' => EventTask::factory(),
            'status' => AssignmentResponseStatus::Invited,
            'occurred_at' => now(),
        ];
    }
}
