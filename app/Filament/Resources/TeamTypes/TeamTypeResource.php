<?php

namespace App\Filament\Resources\TeamTypes;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\TeamTypes\Pages\CreateTeamType;
use App\Filament\Resources\TeamTypes\Pages\EditTeamType;
use App\Filament\Resources\TeamTypes\Pages\ListTeamTypes;
use App\Filament\Resources\TeamTypes\Schemas\TeamTypeForm;
use App\Filament\Resources\TeamTypes\Tables\TeamTypesTable;
use App\Models\TeamType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TeamTypeResource extends Resource
{
    protected static ?string $model = TeamType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Admin');
    }

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Team types');
    }

    public static function getModelLabel(): string
    {
        return __('team type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('team types');
    }

    public static function form(Schema $schema): Schema
    {
        return TeamTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamTypes::route('/'),
            'create' => CreateTeamType::route('/create'),
            'edit' => EditTeamType::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $churchId = app(CurrentChurch::class)->id() ?? auth()->user()?->church_id;

        if ($churchId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('team_types.church_id', $churchId);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if ($user?->church === null) {
            return false;
        }

        if (! app(ChurchModuleManager::class)->isEnabled($user->church, 'teams')) {
            return false;
        }

        return static::can('create');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
