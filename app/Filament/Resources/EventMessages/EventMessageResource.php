<?php

namespace App\Filament\Resources\EventMessages;

use App\Core\Tenancy\CurrentChurch;
use App\Filament\Resources\EventMessages\Pages\ManageEventMessages;
use App\Filament\Resources\Events\EventResource;
use App\Models\EventMessage;
use App\Support\UserAvatar;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Messaging\Support\MessagingAvailability;
use UnitEnum;

class EventMessageResource extends Resource
{
    protected static ?string $model = EventMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Admin');
    }

    protected static ?int $navigationSort = 70;

    protected static ?string $recordTitleAttribute = 'subject';

    protected static ?string $slug = 'messages';

    public static function getNavigationLabel(): string
    {
        return __('Messages');
    }

    public static function getModelLabel(): string
    {
        return __('message');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event.name')
                    ->label(__('Event'))
                    ->searchable()
                    ->sortable()
                    ->url(fn (EventMessage $record): ?string => $record->event_id
                        ? EventResource::getUrl('view', ['record' => $record->event_id])
                        : null),
                TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->placeholder(__('(no subject)'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('recipients_count')
                    ->counts('recipients')
                    ->label(__('Recipients')),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EventMessage::STATUS_SENT => 'success',
                        EventMessage::STATUS_PARTIAL => 'warning',
                        EventMessage::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('createdBy.display_name')
                    ->disabledClick()
                    ->label(__('By'))
                    ->html()
                    ->formatStateUsing(fn ($state, EventMessage $record) => $record->createdBy
                        ? UserAvatar::html($record->createdBy)
                        : '—')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime('M j, H:i')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label(__('Sent'))
                    ->dateTime('M j, H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('openEventMessages')
                    ->label(__('Open'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (EventMessage $record): ?string => $record->event_id
                        ? EventResource::getUrl('messages', ['record' => $record->event_id])
                        : null),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEventMessages::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['event', 'createdBy', 'recipients']);

        $churchId = app(CurrentChurch::class)->id() ?? auth()->user()?->church_id;

        if ($churchId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('event_messages.church_id', $churchId);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if ($user?->church === null) {
            return false;
        }

        if (! app(MessagingAvailability::class)->anyEnabled($user->church)) {
            return false;
        }

        return static::can('viewAny');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
