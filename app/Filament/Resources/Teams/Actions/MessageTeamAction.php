<?php

namespace App\Filament\Resources\Teams\Actions;

use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Modules\Teams\Services\TeamMessageService;

/**
 * A team member writes a plain-text message to other members of the team.
 */
class MessageTeamAction
{
    public static function make(Team $team): Action
    {
        return Action::make('messageTeam')
            ->label(__('Create message'))
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->visible(fn (): bool => auth()->user()?->can('message', $team) ?? false)
            ->modalHeading(__('Message team members'))
            ->modalWidth('2xl')
            ->modalSubmitActionLabel(__('Send'))
            ->schema(function () use ($team): array {
                /** @var User $actor */
                $actor = auth()->user();
                $options = $team->members()->get()
                    ->reject(fn (User $user): bool => $user->is($actor))
                    ->mapWithKeys(fn (User $user): array => [
                        $user->id => $user->displayName().($user->pivot->is_leader ? ' ('.__('leader').')' : ''),
                    ])
                    ->all();
                $isLeader = $team->isLeader($actor) || $actor->can('teams.manage');

                return [
                    CheckboxList::make('recipient_ids')
                        ->label(__('To'))
                        ->options($options)
                        ->default($isLeader ? array_keys($options) : [])
                        ->required()
                        ->columns(2)
                        ->bulkToggleable(),
                    TextInput::make('subject')
                        ->label(__('Subject'))
                        ->maxLength(255),
                    Textarea::make('body')
                        ->label(__('Message'))
                        ->required()
                        ->rows(5)
                        ->maxLength(2000),
                ];
            })
            ->action(function (array $data) use ($team): void {
                $result = app(TeamMessageService::class)->send(
                    team: $team,
                    author: auth()->user(),
                    body: $data['body'],
                    subject: $data['subject'] ?? null,
                    recipientUserIds: array_map('intval', $data['recipient_ids'] ?? []),
                );

                Notification::make()
                    ->title($result['failed'] === 0
                        ? __('Sent to :count member(s)', ['count' => $result['sent']])
                        : __('Sent :sent, failed :failed', ['sent' => $result['sent'], 'failed' => $result['failed']]))
                    ->{$result['failed'] === 0 ? 'success' : 'warning'}()
                    ->send();
            });
    }
}
