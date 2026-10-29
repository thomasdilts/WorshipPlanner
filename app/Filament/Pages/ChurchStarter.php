<?php

namespace App\Filament\Pages;

use App\Core\Tenancy\CurrentChurch;
use App\Models\User;
use App\Services\ChurchStarterChecklist;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

class ChurchStarter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected string $view = 'filament.pages.church-starter';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return null;
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Getting started');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Getting started');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('A short checklist to open your church for planning.');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->church_id === null || $user->church === null) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->church_id);

        return $user->can('church.update');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! static::canAccess()) {
            return false;
        }

        $church = app(CurrentChurch::class)->get() ?? auth()->user()?->church;

        if ($church === null) {
            return false;
        }

        return ! app(ChurchStarterChecklist::class)->isComplete($church);
    }

    /**
     * @return list<array{key: string, label: string, done: bool, url: ?string, hint: string}>
     */
    public function steps(): array
    {
        $church = app(CurrentChurch::class)->get() ?? auth()->user()?->church;

        abort_unless($church, 403);

        return app(ChurchStarterChecklist::class)->steps($church);
    }
}
