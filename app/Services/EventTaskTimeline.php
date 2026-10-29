<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventTaskTimeline
{
    /**
     * Rebuild starts_at / ends_at for all timed (non-global) tasks on the event.
     * First timed task starts at the event’s start time; each next task follows the previous end.
     */
    public function recalculate(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $event->refresh();

            $cursor = Carbon::parse($event->getRawOriginal('starts_at') ?? $event->starts_at)
                ->timezone(config('app.timezone'));

            // Use wall-clock time of the event start in the app timezone.
            $cursor = $cursor->copy()->setSeconds(0);

            foreach ($this->timedTasks($event) as $task) {
                $duration = max(1, (int) ($task->duration ?? 1));
                $start = $cursor->copy();
                $end = $cursor->copy()->addMinutes($duration);

                $task->forceFill([
                    'starts_at' => $start->format('H:i:s'),
                    'ends_at' => $end->format('H:i:s'),
                ])->saveQuietly();

                $cursor = $end;
            }
        });
    }

    /**
     * @return Collection<int, EventTask>
     */
    public function timedTasks(Event $event): Collection
    {
        return EventTask::query()
            ->where('event_id', $event->id)
            ->with('template')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (EventTask $task): bool => ! $task->isGlobal())
            ->values();
    }
}
