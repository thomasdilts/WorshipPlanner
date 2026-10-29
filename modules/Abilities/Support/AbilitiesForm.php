<?php

namespace Modules\Abilities\Support;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Modules\Abilities\Services\UserAbilities;
use Spatie\Permission\PermissionRegistrar;

class AbilitiesForm
{
    /**
     * Admin user create/edit section.
     */
    public static function adminSection(): Section
    {
        return Section::make(__('Abilities'))
            ->description(__('Which person-capable task templates this person may be assigned to when Abilities is enabled.'))
            ->visible(fn (?User $record): bool => static::adminSectionVisible($record))
            ->schema([
                Select::make('ability_template_ids')
                    ->label(__('Abilities'))
                    ->multiple()
                    ->options(function (): array {
                        $churchId = auth()->user()?->church_id;

                        if ($churchId === null) {
                            return [];
                        }

                        return app(UserAbilities::class)->templateOptionsForChurch((int) $churchId);
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->disabled(fn (?User $record): bool => ! static::canEditAbilities($record))
                    ->dehydrated(fn (?User $record): bool => static::canEditAbilities($record))
                    ->helperText(__('Only templates with Person set to Optional or Required are listed.')),
            ]);
    }

    /**
     * Profile self-service select (no section wrapper required by caller).
     */
    public static function profileSelect(): Select
    {
        return Select::make('ability_template_ids')
            ->label(__('Abilities'))
            ->multiple()
            ->options(function (): array {
                $churchId = auth()->user()?->church_id;

                if ($churchId === null) {
                    return [];
                }

                return app(UserAbilities::class)->templateOptionsForChurch((int) $churchId);
            })
            ->searchable()
            ->preload()
            ->native(false)
            ->disabled(fn (): bool => ! static::authCan('abilities.edit_own') && ! static::authCan('abilities.manage'))
            ->dehydrated(fn (): bool => static::authCan('abilities.edit_own') || static::authCan('abilities.manage'))
            ->visible(fn (): bool => static::profileVisible())
            ->helperText(__('Task types you are approved to be assigned to as a person.'));
    }

    public static function adminSectionVisible(?User $record): bool
    {
        $actor = auth()->user();

        if (! $actor?->church || ! app(UserAbilities::class)->isEnabled($actor->church)) {
            return false;
        }

        if (static::authCan('abilities.manage')) {
            return true;
        }

        // Editing yourself via admin Users resource
        if ($record && (int) $record->id === (int) $actor->id) {
            return static::authCan('abilities.view_own') || static::authCan('abilities.edit_own');
        }

        return false;
    }

    public static function canEditAbilities(?User $record): bool
    {
        $actor = auth()->user();

        if ($actor === null) {
            return false;
        }

        if (static::authCan('abilities.manage')) {
            return true;
        }

        if ($record && (int) $record->id === (int) $actor->id) {
            return static::authCan('abilities.edit_own');
        }

        // Creating a new user — only managers can set abilities
        if ($record === null) {
            return static::authCan('abilities.manage');
        }

        return false;
    }

    public static function profileVisible(): bool
    {
        $user = auth()->user();

        if (! $user?->church || ! app(UserAbilities::class)->isEnabled($user->church)) {
            return false;
        }

        return static::authCan('abilities.view_own')
            || static::authCan('abilities.edit_own')
            || static::authCan('abilities.manage');
    }

    protected static function authCan(string $permission): bool
    {
        $user = auth()->user();

        if ($user?->church_id === null) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->church_id);

        return $user->can($permission);
    }
}
