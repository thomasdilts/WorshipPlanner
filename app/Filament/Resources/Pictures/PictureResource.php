<?php

namespace App\Filament\Resources\Pictures;

use App\Core\Modules\ChurchModuleManager;
use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\Pictures\Pages\CreatePicture;
use App\Filament\Resources\Pictures\Pages\EditPicture;
use App\Filament\Resources\Pictures\Pages\ListPictures;
use App\Filament\Resources\Pictures\Schemas\PictureForm;
use App\Filament\Resources\Pictures\Tables\PicturesTable;
use App\Models\Picture;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PictureResource extends Resource
{
    protected static ?string $model = Picture::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Admin');
    }

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Pictures');
    }

    public static function getModelLabel(): string
    {
        return __('picture');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pictures');
    }

    public static function form(Schema $schema): Schema
    {
        return PictureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PicturesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPictures::route('/'),
            'create' => CreatePicture::route('/create'),
            'edit' => EditPicture::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $churchId = app(CurrentChurch::class)->id() ?? auth()->user()?->church_id;

        if ($churchId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('pictures.church_id', $churchId);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if ($user?->church === null) {
            return false;
        }

        if (! app(ChurchModuleManager::class)->isEnabled($user->church, 'pictures')) {
            return false;
        }

        return static::can('viewAny');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
