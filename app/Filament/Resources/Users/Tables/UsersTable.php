<?php

namespace App\Filament\Resources\Users\Tables;

use App\Core\Tenancy\CurrentChurch;
use App\Enums\FieldUsage;
use App\Models\User;
use App\Support\UserAvatar;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Abilities\Support\AbilitiesForm;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $churchId = app(CurrentChurch::class)->id() ?? auth()->user()?->church_id;
        $showAbilities = AbilitiesForm::adminSectionVisible(null);

        $filters = [
            SelectFilter::make('roles')
                ->label(__('Roles'))
                ->relationship('roles', 'name', fn (Builder $query): Builder => $query->forCurrentChurch())
                ->multiple()
                ->searchable()
                ->preload(),
        ];

        if ($showAbilities) {
            $filters[] = SelectFilter::make('abilities')
                ->label(__('Abilities'))
                ->relationship('abilityTemplates', 'name', fn (Builder $query): Builder => $query
                    ->where('church_id', $churchId)
                    ->whereIn('person_usage', [FieldUsage::Optional->value, FieldUsage::Required->value]))
                ->multiple()
                ->searchable()
                ->preload();
        }

        return $table
            ->defaultSort('name')
            ->persistFiltersInSession()
            ->filters($filters, layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->deferFilters(false)
            ->emptyStateHeading(__('No people found'))
            ->emptyStateDescription(__('Try changing the filters or search.'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->disabledClick()
                    ->html()
                    ->formatStateUsing(fn ($state, User $record) => UserAvatar::html($record))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label(__('Display name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label(__('Roles'))
                    ->badge()
                    ->separator(','),
                TextColumn::make('abilityTemplates.name')
                    ->label(__('Abilities'))
                    ->badge()
                    ->wrap()
                    ->placeholder('—')
                    ->visible($showAbilities),
                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
