<?php

namespace App\Filament\Resources\Songs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SongForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Song details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('name2')
                            ->label(__('Alternate name'))
                            ->maxLength(255)
                            ->helperText(__('Another title people search for (translation, nickname).'))
                            ->columnSpanFull(),
                        TextInput::make('author')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Lyrics'))
                    ->schema([
                        Textarea::make('description')
                            ->label(__('Lyrics / text'))
                            ->rows(18)
                            ->columnSpanFull()
                            ->helperText(__('Shown when planning and printing. Keep the wording the church uses.')),
                    ]),
            ]);
    }
}
