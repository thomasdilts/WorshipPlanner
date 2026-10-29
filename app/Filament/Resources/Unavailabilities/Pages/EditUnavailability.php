<?php

namespace App\Filament\Resources\Unavailabilities\Pages;

use App\Filament\Resources\Unavailabilities\UnavailabilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditUnavailability extends EditRecord
{
    protected static string $resource = UnavailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        abort_unless($user !== null && $user->church_id !== null, 403);

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->church_id);

        if (! $user->can('unavailability.manage')) {
            $data['user_id'] = $user->id;
            $data['team_id'] = null;
        } elseif (! empty($data['team_id'])) {
            $data['user_id'] = null;
        } else {
            $data['team_id'] = null;
        }

        return $data;
    }
}
