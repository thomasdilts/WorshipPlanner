<?php

namespace App\Filament\Resources\TeamTypes\Pages;

use App\Filament\Resources\TeamTypes\TeamTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeamType extends EditRecord
{
    protected static string $resource = TeamTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
