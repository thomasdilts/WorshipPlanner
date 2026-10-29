<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Models\Event;
use App\Models\EventMessage;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\InvitationDeliveryPreview;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Modules\Messaging\Services\EventMessageService;
use Spatie\Permission\PermissionRegistrar;

/**
 * The "add message" form for an event — used on the messages page and after editing a task.
 */
class EventMessageForm
{
    /**
     * @return array<int, Component>
     */
    public static function components(Event $event): array
    {
        $participants = $event->personTaskOptions();
        $teams = $event->teamTaskOptions();

        return [
            Select::make('message_template_id')
                ->label(__('Message template'))
                ->placeholder(__('Write my own message'))
                ->options(
                    MessageTemplate::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()
                )
                ->searchable()
                ->nullable()
                ->live()
                ->columnSpanFull(),
            ViewField::make('template_preview')
                ->hiddenLabel()
                ->view('filament.messaging.message-preview')
                ->viewData(function (Get $get): array {
                    $template = MessageTemplate::query()->find($get('message_template_id'));

                    return [
                        'subject' => null,
                        'html' => $template?->body ?? '',
                        'text' => $template?->text_body,
                    ];
                })
                ->visible(fn (Get $get): bool => filled($get('message_template_id')))
                ->dehydrated(false)
                ->columnSpanFull(),
            TextInput::make('subject')
                ->label(__('Subject'))
                ->maxLength(255)
                ->visible(fn (Get $get): bool => blank($get('message_template_id')))
                ->columnSpanFull(),
            Textarea::make('text_body')
                ->label(__('Message'))
                ->rows(5)
                ->maxLength(2000)
                ->required(fn (Get $get): bool => blank($get('message_template_id')))
                ->visible(fn (Get $get): bool => blank($get('message_template_id')))
                ->helperText(__('A link for answering is added at the end automatically.'))
                ->columnSpanFull(),
            CheckboxList::make('recipient_task_ids')
                ->label(__('People'))
                ->options($participants->all())
                ->columns(2)
                ->live()
                ->helperText($participants->isEmpty()
                    ? __('No people assigned on this event yet.')
                    : __('One line per task. Someone with several tasks gets a separate message for each task you tick.'))
                ->visible($participants->isNotEmpty())
                ->bulkToggleable(),
            CheckboxList::make('team_task_ids')
                ->label(__('Teams'))
                ->options($teams->all())
                ->columns(2)
                ->live()
                ->helperText(__('Messages to a team are delivered to that team’s leader(s) only.'))
                ->visible($teams->isNotEmpty())
                ->bulkToggleable(),
            ViewField::make('recipient_preview')
                ->label(__('Who will receive this'))
                ->view('filament.messaging.recipient-preview')
                ->viewData(function (Get $get) use ($event): array {
                    return [
                        'rows' => app(InvitationDeliveryPreview::class)->resolveTasks(
                            $event,
                            static::selectedTaskIds($get('recipient_task_ids'), $get('team_task_ids')),
                        ),
                    ];
                })
                ->dehydrated(false)
                ->columnSpanFull(),
        ];
    }

    /**
     * Creates and immediately sends one message per selected person and task; returns the first for the create action.
     * A chosen template is used exactly as saved; otherwise the message is the typed subject and text.
     *
     * @param  array<string, mixed>  $data
     */
    public static function create(Event $event, array $data): EventMessage
    {
        $template = filled($data['message_template_id'] ?? null)
            ? MessageTemplate::query()->findOrFail($data['message_template_id'])
            : null;

        return app(EventMessageService::class)->create(
            event: $event,
            author: auth()->user(),
            body: $template?->body,
            subject: $template ? $template->subject : ($data['subject'] ?? null),
            templateId: $template?->id,
            sendNow: true,
            textBody: $template ? $template->text_body : ($data['text_body'] ?? null),
            recipientTaskIds: static::selectedTaskIds($data['recipient_task_ids'] ?? [], $data['team_task_ids'] ?? []),
        )->first();
    }

    /**
     * @param  array<int, int|string>|null  $personTaskIds
     * @param  array<int, int|string>|null  $teamTaskIds
     * @return list<int>
     */
    protected static function selectedTaskIds(?array $personTaskIds, ?array $teamTaskIds): array
    {
        return array_values(array_unique(array_map('intval', [...($personTaskIds ?? []), ...($teamTaskIds ?? [])])));
    }

    public static function canSend(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->church_id === null) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->church_id);

        return $user->can('messaging.send');
    }
}
