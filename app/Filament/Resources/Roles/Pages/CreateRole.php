<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\Concerns\SyncsRolePermissions;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\PermissionRegistrar;

class CreateRole extends CreateRecord
{
    use SyncsRolePermissions;

    protected static string $resource = RoleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->extractPermissionsFromFormData($data);

        $data['guard_name'] = 'web';
        $data['church_id'] = auth()->user()?->church_id;

        app(PermissionRegistrar::class)->setPermissionsTeamId($data['church_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncPendingPermissions();
    }
}
