<?php

namespace App\Services;

use App\Core\Modules\ChurchModuleManager;
use App\Enums\AssignmentResponseStatus;
use App\Enums\AssignmentState;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\EventTaskResponse;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Messaging\Data\OutboundMessageData;
use Modules\Messaging\Services\MessageSender;
use Modules\Messaging\Support\MessagingAvailability;

/**
 * Automatic “you’re on for this service” reminders (OUT-05).
 *
 * One-shot per assignment per target day; Notified history keeps Confirmations intact.
 */
class AssignmentReminderDispatcher
{
    public const LEGACY_SOURCE = 'assignment_reminder';

    public function __construct(
        protected ChurchModuleManager $modules,
        protected MessagingAvailability $messaging,
        protected AssignmentStateResolver $states,
        protected MessageSender $sender,
        protected AssignmentResponseLink $responseLinks,
    ) {}

    /**
     * @return array{sent: int, skipped: int, churches: int}
     */
    public function run(?Carbon $now = null): array
    {
        $now ??= now();
        $sent = 0;
        $skipped = 0;
        $churches = 0;

        foreach ($this->eligibleChurches() as $church) {
            $churches++;
            $result = $this->runForChurch($church, $now);
            $sent += $result['sent'];
            $skipped += $result['skipped'];
        }

        return compact('sent', 'skipped', 'churches');
    }

    /**
     * @return array{sent: int, skipped: int}
     */
    public function runForChurch(Church $church, ?Carbon $now = null): array
    {
        $now ??= now();
        $settings = $this->reminderSettings($church);

        if (! $settings['enabled'] || ! $this->messaging->anyEnabled($church)) {
            return ['sent' => 0, 'skipped' => 0];
        }

        $sent = 0;
        $skipped = 0;

        foreach ($this->dueTasks($church, $settings['days_before'], $now) as $task) {
            $user = $task->assignedUser;

            if ($user === null) {
                $skipped++;

                continue;
            }

            if ($this->alreadyReminded($task, $user)) {
                $skipped++;

                continue;
            }

            $state = $this->states->for($task);

            if (! in_array($state, [AssignmentState::Invited, AssignmentState::Confirmed, AssignmentState::ConfirmedVerbally, AssignmentState::Informed], true)) {
                $skipped++;

                continue;
            }

            if ($this->deliver($task, $user, $church)) {
                $sent++;
            } else {
                $skipped++;
            }
        }

        return compact('sent', 'skipped');
    }

    /**
     * @return array{enabled: bool, days_before: int}
     */
    public function reminderSettings(Church $church): array
    {
        $raw = $this->modules->settings($church, MessagingAvailability::MODULE_EMAIL)['reminders'] ?? [];

        return [
            'enabled' => (bool) ($raw['enabled'] ?? false),
            'days_before' => max(0, min(30, (int) ($raw['days_before'] ?? 3))),
        ];
    }

    /**
     * @return Collection<int, Church>
     */
    protected function eligibleChurches(): Collection
    {
        return Church::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(fn (Church $church): bool => $this->messaging->anyEnabled($church));
    }

    /**
     * @return Collection<int, EventTask>
     */
    protected function dueTasks(Church $church, int $daysBefore, Carbon $now): Collection
    {
        $tz = $church->timezone ?: config('app.timezone');
        $targetDate = $now->clone()->timezone($tz)->startOfDay()->addDays($daysBefore)->toDateString();

        $events = Event::query()
            ->where('church_id', $church->id)
            ->where('starts_at', '>=', $now->clone()->subDay())
            ->where('starts_at', '<=', $now->clone()->addDays($daysBefore + 2))
            ->with(['tasks.assignedUser', 'tasks.responses', 'tasks.template', 'tasks.event'])
            ->get()
            ->filter(function (Event $event) use ($tz, $targetDate): bool {
                if ($event->starts_at === null) {
                    return false;
                }

                return $event->starts_at->clone()->timezone($tz)->toDateString() === $targetDate;
            });

        return $events
            ->flatMap(fn (Event $event) => $event->tasks)
            ->filter(fn (EventTask $task): bool => filled($task->assigned_user_id))
            ->values();
    }

    protected function alreadyReminded(EventTask $task, User $user): bool
    {
        $task->loadMissing('responses');

        return $task->responses->contains(function (EventTaskResponse $response) use ($user): bool {
            return (int) $response->assignee_user_id === (int) $user->id
                && $response->status === AssignmentResponseStatus::Notified
                && $response->legacy_source === self::LEGACY_SOURCE;
        });
    }

    protected function deliver(EventTask $task, User $user, Church $church): bool
    {
        $event = $task->event;
        $when = $event?->starts_at?->timezone($church->timezone ?: config('app.timezone'))
            ?->translatedFormat('l j F Y H:i');

        $subject = __('Reminder: :task — :event', [
            'task' => $task->displayName(),
            'event' => $event?->name ?? '',
        ]);

        $body = '<p>'.e(__('This is a reminder that you are scheduled for “:task” at :event (:when).', [
            'task' => $task->displayName(),
            'event' => $event?->name ?? '',
            'when' => $when ?? '',
        ])).'</p>';

        $body .= $this->responseLinks->footerHtml($task, $user);

        $result = $this->sender->sendToUser($user, new OutboundMessageData(
            subject: $subject,
            body: $body,
            templateKey: 'assignment_reminder',
            meta: [
                'event_id' => $event?->id,
                'event_task_id' => $task->id,
                'kind' => self::LEGACY_SOURCE,
            ],
        ));

        if (! $result->success) {
            return false;
        }

        EventTaskResponse::query()->create([
            'church_id' => $church->id,
            'event_task_id' => $task->id,
            'assignee_user_id' => $user->id,
            'status' => AssignmentResponseStatus::Notified,
            'occurred_at' => now(),
            'note' => __('Automatic reminder'),
            'legacy_source' => self::LEGACY_SOURCE,
        ]);

        return true;
    }
}
