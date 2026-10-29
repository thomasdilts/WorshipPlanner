<?php

namespace App\Filament\Resources\SharedFiles;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\SharedFiles\Pages\CreateSharedFile;
use App\Filament\Resources\SharedFiles\Pages\EditSharedFile;
use App\Filament\Resources\SharedFiles\Pages\ListSharedFiles;
use App\Filament\Resources\SharedFiles\Schemas\SharedFileForm;
use App\Filament\Resources\SharedFiles\Tables\SharedFilesTable;
use App\Models\SharedFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SharedFileResource extends Resource
{
    protected static ?string $model = SharedFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Files');
    }

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Shared files');
    }

    public static function getModelLabel(): string
    {
        return __('shared file');
    }

    public static function getPluralModelLabel(): string
    {
        return __('shared files');
    }

    protected static ?string $slug = 'shared-files';

    public static function form(Schema $schema): Schema
    {
        return SharedFileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SharedFilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSharedFiles::route('/'),
            'create' => CreateSharedFile::route('/create'),
            'edit' => EditSharedFile::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['uploadedBy', 'teams']);

        $churchId = app(CurrentChurch::class)->id() ?? auth()->user()?->church_id;

        if ($churchId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('shared_files.church_id', $churchId);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if ($user?->church === null) {
            return false;
        }

        if (! app(ChurchModuleManager::class)->isEnabled($user->church, 'file_sharing')) {
            return false;
        }

        return static::can('viewAny');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
