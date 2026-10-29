<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class Role extends SpatieRole
{
    public function scopeForCurrentChurch(Builder $query): Builder
    {
        $registrar = app(PermissionRegistrar::class);
        $churchId = $registrar->getPermissionsTeamId()
            ?? auth()->user()?->church_id;

        if ($churchId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(
            $query->getModel()->qualifyColumn($registrar->teamsKey),
            $churchId
        );
    }

    public function churchId(): ?int
    {
        $key = app(PermissionRegistrar::class)->teamsKey;
        $value = $this->getAttribute($key);

        return $value !== null ? (int) $value : null;
    }
}
