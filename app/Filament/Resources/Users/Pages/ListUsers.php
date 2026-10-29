<?php

namespace App\Filament\Resources\Users\Pages;

use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /** @var array<string, string> */
    protected array $extraBodyAttributes = [
        'class' => 'wp-users-list',
    ];

    public function getSubheading(): string|Htmlable|null
    {
        return __('People in this church and the permissions they hold.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTableFiltersSessionKey(): string
    {
        $churchId = app(CurrentChurch::class)->id() ?? auth()->user()?->church_id;

        return parent::getTableFiltersSessionKey().':'.$churchId.':'.auth()->id();
    }
}
