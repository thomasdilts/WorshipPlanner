<?php

namespace App\Filament\Resources\TeamTypes\Pages;

use App\Filament\Resources\TeamTypes\TeamTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeamTypes extends ListRecords
{
    protected static string $resource = TeamTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
