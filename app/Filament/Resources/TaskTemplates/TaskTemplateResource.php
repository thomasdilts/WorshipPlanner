<?php

namespace App\Filament\Resources\TaskTemplates;

use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\TaskTemplates\Pages\CreateTaskTemplate;
use App\Filament\Resources\TaskTemplates\Pages\EditTaskTemplate;
use App\Filament\Resources\TaskTemplates\Pages\ListTaskTemplates;
use App\Filament\Resources\TaskTemplates\Schemas\TaskTemplateForm;
use App\Filament\Resources\TaskTemplates\Tables\TaskTemplatesTable;
use App\Models\TaskTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaskTemplateResource extends Resource
{
    protected static ?string $model = TaskTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Admin');
    }

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Task templates');
    }

    public static function getModelLabel(): string
    {
        return __('task template');
    }

    public static function getPluralModelLabel(): string
    {
        return __('task templates');
    }

    public static function form(Schema $schema): Schema
    {
        return TaskTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaskTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaskTemplates::route('/'),
            'create' => CreateTaskTemplate::route('/create'),
            'edit' => EditTaskTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! app(CurrentChurch::class)->check()) {
            return $query->whereRaw('0 = 1');
        }

        return $query;
    }

    /**
     * Admin-only nav: planners may use templates on events, but not open this resource.
     */
    public static function canViewAny(): bool
    {
        return static::can('create');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
