<?php

namespace App\Filament\Resources\TeamTypes\Pages;

use App\Filament\Resources\TeamTypes\TeamTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamType extends CreateRecord
{
    protected static string $resource = TeamTypeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        abort_unless($user !== null && $user->church_id !== null, 403);

        $data['church_id'] = $user->church_id;

        return $data;
    }
}
