<?php

namespace App\Core\Permissions;

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

trait EnsuresChurchPermissionTeam
{
    protected function withChurchPermissions(User $user, callable $callback): bool
    {
        if ($user->church_id === null) {
            return false;
        }

        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($user->church_id);

        try {
            return (bool) $callback();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
