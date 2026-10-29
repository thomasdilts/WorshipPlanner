<?php

namespace App\Policies;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Permissions\EnsuresChurchPermissionTeam;
use App\Models\Picture;
use App\Models\User;

class PicturePolicy
{
    use EnsuresChurchPermissionTeam;

    public function viewAny(User $user): bool
    {
        return $this->picturesAllowed($user)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('pictures.view') || $user->can('pictures.manage')
            );
    }

    public function view(User $user, Picture $picture): bool
    {
        return $this->sameChurch($user, $picture)
            && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->picturesAllowed($user)
            && $this->withChurchPermissions($user, fn () => $user->can('pictures.manage'));
    }

    public function update(User $user, Picture $picture): bool
    {
        return $this->sameChurch($user, $picture)
            && $this->create($user);
    }

    public function delete(User $user, Picture $picture): bool
    {
        return $this->update($user, $picture);
    }

    protected function picturesAllowed(User $user): bool
    {
        if ($user->church_id === null || $user->church === null) {
            return false;
        }

        return app(ChurchModuleManager::class)->isEnabled($user->church, 'pictures');
    }

    protected function sameChurch(User $user, Picture $picture): bool
    {
        return $user->church_id !== null
            && (int) $user->church_id === (int) $picture->church_id;
    }
}
