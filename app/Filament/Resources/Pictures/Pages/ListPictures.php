<?php

namespace App\Filament\Resources\Pictures\Pages;

use App\Filament\Resources\Pictures\PictureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPictures extends ListRecords
{
    protected static string $resource = PictureResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return __('Images you can attach when planning a service.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Add picture')),
        ];
    }
}
