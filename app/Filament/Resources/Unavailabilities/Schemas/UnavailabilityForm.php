<?php

namespace App\Filament\Resources\Unavailabilities\Schemas;

use App\Filament\Resources\Unavailabilities\UnavailabilityResource;
use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UnavailabilityForm
{
    public static function configure(Schema $schema): Schema
    {
        $canManageOthers = UnavailabilityResource::canManageOthers();

        return $schema
            ->components([
                Select::make('scope')
                    ->label(__('Applies to'))
                    ->options([
                        'user' => 'Person',
                        'team' => 'Team',
                    ])
                    ->default('user')
                    ->required()
                    ->live()
                    ->dehydrated(false)
                    ->visible($canManageOthers)
                    ->afterStateHydrated(function (Select $component, $record): void {
                        if ($record === null) {
                            return;
                        }

                        $component->state($record->team_id ? 'team' : 'user');
                    }),
                Select::make('user_id')
                    ->label(__('Person'))
                    ->options(fn (): array => User::query()
                        ->where('church_id', auth()->user()?->church_id)
                        ->orderBy('display_name')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (User $user) => [
                            $user->id => $user->display_name ?: $user->name,
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('scope') !== 'team')
                    ->default(fn () => auth()->id())
                    ->visible(fn (Get $get): bool => $canManageOthers && $get('scope') !== 'team'),
                Select::make('team_id')
                    ->label(__('Team'))
                    ->options(fn (): array => Team::query()
                        ->where('church_id', auth()->user()?->church_id)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('scope') === 'team')
                    ->visible(fn (Get $get): bool => $canManageOthers && $get('scope') === 'team'),
                DatePicker::make('starts_on')
                    ->label(__('Starts'))
                    ->required()
                    ->native(false)
                    ->live(onBlur: true),
                DatePicker::make('ends_on')
                    ->label(__('Ends'))
                    ->required()
                    ->native(false)
                    ->afterOrEqual('starts_on'),
                TextInput::make('note')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
