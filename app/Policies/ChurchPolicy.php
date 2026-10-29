<?php

namespace App\Policies;

use App\Models\Church;
use App\Models\User;

class ChurchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, Church $church): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, Church $church): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, Church $church): bool
    {
        return $user->isPlatformAdmin();
    }
}
