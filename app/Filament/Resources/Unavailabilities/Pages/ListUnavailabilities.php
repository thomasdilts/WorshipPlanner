<?php

namespace App\Filament\Resources\Unavailabilities\Pages;

use App\Filament\Resources\Unavailabilities\UnavailabilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnavailabilities extends ListRecords
{
    protected static string $resource = UnavailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
