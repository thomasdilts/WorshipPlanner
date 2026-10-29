<?php

namespace App\Models;

use App\Core\Tenancy\BelongsToChurch;
use App\Enums\AssignmentResponseMethod;
use App\Enums\AssignmentResponseStatus;
use Database\Factories\EventTaskResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * One entry in the invitation/response history of a task assignment.
 *
 * The task's assigned_user_id / assigned_team_id stay the single source of who holds the position;
 * this history only records what happened to invitations and replies for a given assignee.
 */
class EventTaskResponse extends Model
{
    /** @use HasFactory<EventTaskResponseFactory> */
    use BelongsToChurch, HasFactory;

    protected $fillable = [
        'church_id',
        'event_task_id',
        'assignee_user_id',
        'assignee_team_id',
        'responder_user_id',
        'recorded_by_user_id',
        'status',
        'method',
        'requested_at',
        'occurred_at',
        'note',
        'legacy_source',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssignmentResponseStatus::class,
            'method' => AssignmentResponseMethod::class,
            'requested_at' => 'datetime',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EventTaskResponse $response): void {
            $hasUser = filled($response->assignee_user_id);
            $hasTeam = filled($response->assignee_team_id);

            if ($hasUser === $hasTeam) {
                throw new InvalidArgumentException('A response must be about either a person or a team assignee.');
            }

            $taskChurchId = EventTask::query()
                ->whereKey($response->event_task_id)
                ->join('events', 'events.id', '=', 'event_tasks.event_id')
                ->value('events.church_id');

            if ((int) $taskChurchId !== (int) $response->church_id) {
                throw new InvalidArgumentException('The task does not belong to the response church.');
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(EventTask::class, 'event_task_id');
    }

    public function assigneeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function assigneeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assignee_team_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_user_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
