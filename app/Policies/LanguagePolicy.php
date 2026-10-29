<?php

namespace App\Policies;

use App\Core\Permissions\EnsuresChurchPermissionTeam;
use App\Models\Language;
use App\Models\User;

class LanguagePolicy
{
    use EnsuresChurchPermissionTeam;

    public function viewAny(User $user): bool
    {
        return $this->canManageLanguages($user);
    }

    public function view(User $user, Language $language): bool
    {
        return $this->canManageLanguages($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageLanguages($user);
    }

    public function update(User $user, Language $language): bool
    {
        return $this->canManageLanguages($user);
    }

    public function delete(User $user, Language $language): bool
    {
        return $this->canManageLanguages($user);
    }

    protected function canManageLanguages(User $user): bool
    {
        if ($user->church_id === null) {
            return false;
        }

        return $this->withChurchPermissions(
            $user,
            fn () => $user->can('church.update') || $user->can('modules.manage')
        );
    }
}
