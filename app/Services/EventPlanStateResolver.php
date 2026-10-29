<?php

namespace App\Services;

use App\Enums\EventPlanState;
use App\Models\Event;

/**
 * Lightweight Draft / Shared / Changed-since-shared markers on a service (OUT-06).
 */
class EventPlanStateResolver
{
    public function for(Event $event): EventPlanState
    {
        if ($event->shared_at === null) {
            return EventPlanState::Draft;
        }

        // plan_changed_at is cleared on share; any non-null value means dirty
        // (including same-second timestamps after a quick edit).
        if ($event->plan_changed_at !== null) {
            return EventPlanState::ChangedSinceShared;
        }

        return EventPlanState::Shared;
    }

    public function markShared(Event $event): void
    {
        $event->forceFill([
            'shared_at' => now(),
            'plan_changed_at' => null,
        ])->save();
    }

    public function markChanged(Event $event): void
    {
        if ($event->shared_at === null) {
            return;
        }

        $event->forceFill([
            'plan_changed_at' => now(),
        ])->save();
    }
}
