<?php

namespace App\Policies;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Permissions\EnsuresChurchPermissionTeam;
use App\Models\TeamType;
use App\Models\User;

class TeamTypePolicy
{
    use EnsuresChurchPermissionTeam;

    public function viewAny(User $user): bool
    {
        return $this->teamsAllowed($user)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('teams.view') || $user->can('teams.manage')
            );
    }

    public function view(User $user, TeamType $type): bool
    {
        return $this->sameChurch($user, $type)
            && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->teamsAllowed($user)
            && $this->withChurchPermissions($user, fn () => $user->can('teams.manage'));
    }

    public function update(User $user, TeamType $type): bool
    {
        return $this->sameChurch($user, $type)
            && $this->create($user);
    }

    public function delete(User $user, TeamType $type): bool
    {
        if (! $this->update($user, $type)) {
            return false;
        }

        return ! $type->teams()->exists();
    }

    protected function teamsAllowed(User $user): bool
    {
        if ($user->church_id === null || $user->church === null) {
            return false;
        }

        return app(ChurchModuleManager::class)->isEnabled($user->church, 'teams');
    }

    protected function sameChurch(User $user, TeamType $type): bool
    {
        return $user->church_id !== null
            && (int) $user->church_id === (int) $type->church_id;
    }
}
