<?php

namespace App\Filament\Resources\Languages;

use App\Enums\TextDirection;
use App\Filament\Resources\Languages\Pages\ManageLanguages;
use App\Models\Language;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Admin');
    }

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function getNavigationLabel(): string
    {
        return __('Languages');
    }

    public static function getModelLabel(): string
    {
        return __('language');
    }

    public static function getPluralModelLabel(): string
    {
        return __('languages');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('iso')
                    ->label(__('ISO code'))
                    ->required()
                    ->maxLength(16),
                TextInput::make('name_en')
                    ->label(__('English name'))
                    ->required()
                    ->maxLength(120),
                TextInput::make('name_native')
                    ->label(__('Native name'))
                    ->required()
                    ->maxLength(120),
                TextInput::make('font')
                    ->label(__('Font'))
                    ->required()
                    ->maxLength(120),
                Select::make('direction')
                    ->label(__('Direction'))
                    ->options(TextDirection::options())
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
                TextInput::make('sort_order')
                    ->label(__('Sort order'))
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('iso')
                    ->label(__('ISO'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label(__('English name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_native')
                    ->label(__('Native name'))
                    ->searchable(),
                TextColumn::make('font')
                    ->label(__('Font'))
                    ->toggleable(),
                TextColumn::make('direction')
                    ->label(__('Direction'))
                    ->badge()
                    ->formatStateUsing(fn (TextDirection|string $state): string => $state instanceof TextDirection
                        ? $state->label()
                        : TextDirection::from($state)->label()),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('Sort order'))
                    ->numeric()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLanguages::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::can('viewAny');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
