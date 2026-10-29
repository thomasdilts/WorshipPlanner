<?php

namespace App\Filament\Resources\Teams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->emptyStateHeading(__('No teams yet'))
            ->emptyStateDescription(__('Create a team (band, greeters, tech…) then add members on the team page.'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type.name')
                    ->label(__('Type'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('members_count')
                    ->counts('members')
                    ->label(__('Members')),
                TextColumn::make('leaders_count')
                    ->counts('leaders')
                    ->label(__('Leaders')),
                TextColumn::make('description')
                    ->limit(40)
                    ->toggleable()
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
