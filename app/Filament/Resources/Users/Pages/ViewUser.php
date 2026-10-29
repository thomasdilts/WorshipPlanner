<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->displayName();
    }

    public function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
