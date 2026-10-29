<?php

namespace App\Policies;

use App\Core\Permissions\EnsuresChurchPermissionTeam;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    use EnsuresChurchPermissionTeam;

    public function viewAny(User $user): bool
    {
        return $this->withChurchPermissions(
            $user,
            fn () => $user->can('events.view')
        );
    }

    public function view(User $user, Event $event): bool
    {
        return $this->sameChurch($user, $event)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('events.view')
            );
    }

    public function create(User $user): bool
    {
        return $this->withChurchPermissions(
            $user,
            fn () => $user->can('events.create')
        );
    }

    public function update(User $user, Event $event): bool
    {
        return $this->sameChurch($user, $event)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('events.update')
            );
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->sameChurch($user, $event)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('events.delete')
            );
    }

    protected function sameChurch(User $user, Event $event): bool
    {
        return $user->church_id !== null
            && (int) $user->church_id === (int) $event->church_id;
    }
}
