<?php

namespace App\Core\Tenancy;

use App\Models\Church;
use Spatie\Permission\PermissionRegistrar;

class CurrentChurch
{
    protected ?Church $church = null;

    public function set(?Church $church): void
    {
        $this->church = $church;

        app(PermissionRegistrar::class)->setPermissionsTeamId(
            $church?->id
        );
    }

    public function get(): ?Church
    {
        return $this->church;
    }

    public function id(): ?int
    {
        return $this->church?->id;
    }

    public function check(): bool
    {
        return $this->church !== null;
    }

    public function forget(): void
    {
        $this->church = null;
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
