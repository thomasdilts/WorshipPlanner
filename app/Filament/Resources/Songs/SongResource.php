<?php

namespace App\Filament\Resources\Songs;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\Songs\Pages\CreateSong;
use App\Filament\Resources\Songs\Pages\EditSong;
use App\Filament\Resources\Songs\Pages\ListSongs;
use App\Filament\Resources\Songs\Schemas\SongForm;
use App\Filament\Resources\Songs\Tables\SongsTable;
use App\Models\Song;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SongResource extends Resource
{
    protected static ?string $model = Song::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Songs');
    }

    public static function getModelLabel(): string
    {
        return __('song');
    }

    public static function getPluralModelLabel(): string
    {
        return __('songs');
    }

    public static function form(Schema $schema): Schema
    {
        return SongForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SongsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSongs::route('/'),
            'create' => CreateSong::route('/create'),
            'edit' => EditSong::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withCount('eventTasks')
            ->withMax('eventTasks as last_used_at', 'updated_at');

        $churchId = app(CurrentChurch::class)->id() ?? auth()->user()?->church_id;

        if ($churchId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('songs.church_id', $churchId);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if ($user?->church === null) {
            return false;
        }

        if (! app(ChurchModuleManager::class)->isEnabled($user->church, 'song_list')) {
            return false;
        }

        return static::can('viewAny');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
