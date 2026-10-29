<?php

namespace App\Filament\Resources\EventMessages\Pages;

use App\Filament\Resources\EventMessages\EventMessageResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageEventMessages extends ManageRecords
{
    protected static string $resource = EventMessageResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('Messages');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
