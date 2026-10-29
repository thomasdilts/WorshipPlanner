<?php

namespace App\Filament\Resources\TeamTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeamTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(100)
                    ->helperText(__('Examples: Music team, Prayer team, Greeter team')),
            ]);
    }
}
