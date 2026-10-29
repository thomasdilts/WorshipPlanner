<?php

namespace App\Policies;

use App\Core\Permissions\EnsuresChurchPermissionTeam;
use App\Models\MessageTemplate;
use App\Models\User;
use Modules\Messaging\Support\MessagingAvailability;

class MessageTemplatePolicy
{
    use EnsuresChurchPermissionTeam;

    public function viewAny(User $user): bool
    {
        return $this->messagingAllowed($user)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('messaging.view') || $user->can('messaging.templates.manage')
            );
    }

    public function view(User $user, MessageTemplate $template): bool
    {
        return $this->sameChurch($user, $template)
            && $this->messagingAllowed($user)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('messaging.view') || $user->can('messaging.templates.manage')
            );
    }

    public function create(User $user): bool
    {
        return $this->messagingAllowed($user)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('messaging.templates.manage')
            );
    }

    public function update(User $user, MessageTemplate $template): bool
    {
        return $this->sameChurch($user, $template)
            && $this->messagingAllowed($user)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('messaging.templates.manage')
            );
    }

    public function delete(User $user, MessageTemplate $template): bool
    {
        return $this->sameChurch($user, $template)
            && $this->messagingAllowed($user)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('messaging.templates.manage')
            );
    }

    protected function messagingAllowed(User $user): bool
    {
        if ($user->church_id === null || $user->church === null) {
            return false;
        }

        return app(MessagingAvailability::class)->anyEnabled($user->church);
    }

    protected function sameChurch(User $user, MessageTemplate $template): bool
    {
        return $user->church_id !== null
            && (int) $user->church_id === (int) $template->church_id;
    }
}
