<?php

namespace App\Filament\Resources\Unavailabilities\Pages;

use App\Filament\Resources\Unavailabilities\UnavailabilityResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\PermissionRegistrar;

class CreateUnavailability extends CreateRecord
{
    protected static string $resource = UnavailabilityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user !== null && $user->church_id !== null, 403);

        $data['church_id'] = $user->church_id;

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->church_id);

        if (! $user->can('unavailability.manage')) {
            $data['user_id'] = $user->id;
            $data['team_id'] = null;
        } elseif (! empty($data['team_id'])) {
            $data['user_id'] = null;
        } else {
            $data['team_id'] = null;
            $data['user_id'] ??= $user->id;
        }

        return $data;
    }
}
