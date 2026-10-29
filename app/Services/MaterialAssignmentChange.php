<?php

namespace App\Services;

use App\Enums\AssignmentResponseStatus;
use App\Enums\AssignmentState;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\EventTaskResponse;
use Illuminate\Support\Collection;

/**
 * When a confirmed position's required time (or role label) changes, ask again.
 *
 * Does not clear the assignee. Appends an Invited history row so state becomes
 * "Waiting for reply" with an audit trail — never a silent wipe of Confirmed.
 */
class MaterialAssignmentChange
{
    public function __construct(
        protected AssignmentStateResolver $states,
        protected EventPlanStateResolver $planStates,
    ) {}

    /**
     * @return array<int, array{starts_at: ?string, ends_at: ?string, duration: ?int, name: ?string, task_template_id: mixed}>
     */
    public function snapshot(Event $event): array
    {
        $event->loadMissing(['tasks.template', 'tasks.responses']);

        $out = [];

        foreach ($event->tasks as $task) {
            $out[(int) $task->id] = [
                'starts_at' => $task->starts_at,
                'ends_at' => $task->ends_at,
                'duration' => $task->duration,
                'name' => $task->name,
                'task_template_id' => $task->task_template_id,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array{starts_at: ?string, ends_at: ?string, duration: ?int, name: ?string, task_template_id: mixed}>  $before
     */
    public function applyAfterRecalculate(Event $event, array $before): int
    {
        $event->loadMissing(['tasks.template', 'tasks.responses', 'tasks.event']);

        $flagged = 0;
        $materialEdit = false;

        foreach ($event->tasks as $task) {
            $prior = $before[(int) $task->id] ?? null;

            if ($prior === null) {
                continue;
            }

            if (! $this->materialFieldsChanged($prior, $task)) {
                continue;
            }

            $materialEdit = true;

            if ($this->states->for($task) !== AssignmentState::Confirmed) {
                continue;
            }

            $this->requestReconfirmation($task);
            $flagged++;
        }

        if ($materialEdit) {
            $this->planStates->markChanged($event);
        }

        return $flagged;
    }

    /**
     * @param  array{starts_at: ?string, ends_at: ?string, duration: ?int, name: ?string, task_template_id: mixed}  $prior
     */
    protected function materialFieldsChanged(array $prior, EventTask $task): bool
    {
        return (string) ($prior['starts_at'] ?? '') !== (string) ($task->starts_at ?? '')
            || (string) ($prior['ends_at'] ?? '') !== (string) ($task->ends_at ?? '')
            || (int) ($prior['duration'] ?? 0) !== (int) ($task->duration ?? 0)
            || (string) ($prior['name'] ?? '') !== (string) ($task->name ?? '')
            || (int) ($prior['task_template_id'] ?? 0) !== (int) ($task->task_template_id ?? 0);
    }

    protected function requestReconfirmation(EventTask $task): EventTaskResponse
    {
        $task->loadMissing('event');

        return EventTaskResponse::query()->create([
            'church_id' => (int) $task->event->church_id,
            'event_task_id' => $task->id,
            'assignee_user_id' => $task->assigned_user_id,
            'assignee_team_id' => $task->assigned_team_id,
            'responder_user_id' => null,
            'recorded_by_user_id' => null,
            'status' => AssignmentResponseStatus::ReconfirmRequested,
            'method' => null,
            'requested_at' => now(),
            'occurred_at' => now(),
            'note' => __('Schedule or role changed — please confirm again'),
        ]);
    }

    /**
     * @return Collection<int, EventTask>
     */
    public function needingReconfirmation(Event $event): Collection
    {
        $event->loadMissing(['tasks.responses', 'tasks.template']);

        return $event->tasks->filter(function (EventTask $task): bool {
            $latest = $task->responses
                ->filter(fn (EventTaskResponse $r): bool => $r->status->changesResponse())
                ->last();

            return $latest?->status === AssignmentResponseStatus::ReconfirmRequested;
        })->values();
    }
}
