<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Core\Permissions\PermissionLabels;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $permissionSections = [];

        foreach (PermissionLabels::groupedOptions() as $module => $options) {
            if ($options === []) {
                continue;
            }

            $permissionSections[] = Section::make(PermissionLabels::moduleHeading($module))
                ->compact()
                ->schema([
                    CheckboxList::make("permissions_{$module}")
                        ->hiddenLabel()
                        ->options($options)
                        ->columns(2)
                        ->bulkToggleable(),
                ]);
        }

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Role name'))
                    ->required()
                    ->maxLength(64)
                    ->helperText(__('Example: Sound tech, Worship leader, Greeter')),
                Section::make(__('What this role can do'))
                    ->description(__('Permissions are defined by the app. You choose which ones this role gets.'))
                    ->schema($permissionSections),
            ]);
    }
}
