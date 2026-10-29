<?php

namespace App\Policies;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Permissions\EnsuresChurchPermissionTeam;
use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    use EnsuresChurchPermissionTeam;

    public function viewAny(User $user): bool
    {
        return $this->songListAllowed($user)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('song_list.view') || $user->can('song_list.manage')
            );
    }

    public function view(User $user, Song $song): bool
    {
        return $this->sameChurch($user, $song)
            && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->songListAllowed($user)
            && $this->withChurchPermissions($user, fn () => $user->can('song_list.manage'));
    }

    public function update(User $user, Song $song): bool
    {
        return $this->sameChurch($user, $song)
            && $this->create($user);
    }

    public function delete(User $user, Song $song): bool
    {
        return $this->update($user, $song);
    }

    protected function songListAllowed(User $user): bool
    {
        if ($user->church_id === null || $user->church === null) {
            return false;
        }

        return app(ChurchModuleManager::class)->isEnabled($user->church, 'song_list');
    }

    protected function sameChurch(User $user, Song $song): bool
    {
        return $user->church_id !== null
            && (int) $user->church_id === (int) $song->church_id;
    }
}
