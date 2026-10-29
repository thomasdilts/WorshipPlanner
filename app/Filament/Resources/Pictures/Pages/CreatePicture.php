<?php

namespace App\Filament\Resources\Pictures\Pages;

use App\Filament\Resources\Pictures\PictureResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePicture extends CreateRecord
{
    protected static string $resource = PictureResource::class;

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
