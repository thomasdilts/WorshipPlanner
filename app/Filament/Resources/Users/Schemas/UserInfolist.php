<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\MessagePreference;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Modules\Abilities\Support\AbilitiesForm;
use Modules\Messaging\Support\MessagingAvailability;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label(__('Name')),
            TextEntry::make('display_name')->label(__('Display name'))->placeholder('—'),
            TextEntry::make('email')->label(__('Email')),
            TextEntry::make('phone')->label(__('Mobile phone'))->placeholder('—'),
            TextEntry::make('roles.name')->label(__('Roles'))->badge(),
            TextEntry::make('abilityTemplates.name')
                ->label(__('Abilities'))
                ->badge()
                ->placeholder('—')
                ->visible(fn (User $record): bool => AbilitiesForm::adminSectionVisible($record)),
            TextEntry::make('message_preference')
                ->label(__('Preferred messaging'))
                ->state(fn (User $record): string => ($record->message_preference ?? MessagePreference::Email)->label())
                ->badge()
                ->visible(fn (User $record): bool => app(MessagingAvailability::class)->anyEnabled($record->church)),
        ])->columns(['default' => 1, 'sm' => 2]);
    }
}
