<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Filament\Resources\Events\Actions\EditEventTaskAction;
use App\Filament\Resources\Events\Actions\SendTaskMessageAction;
use App\Filament\Resources\Events\Actions\ViewEventTaskAction;
use App\Filament\Resources\Events\Concerns\HasEventMessageActions;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Schemas\EventTaskForm;
use App\Models\Event;
use App\Models\EventTask;
use App\Services\EventTaskRowSummary;
use App\Services\EventTaskTimeline;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Shared service-order table. Subclasses set {@see $globalOnly} for Order vs People.
 */
class TasksRelationManager extends RelationManager
{
    use HasEventMessageActions;

    protected static string $relationship = 'tasks';

    /**
     * null = all, true = whole-service (People), false = timed (Order).
     */
    protected static ?bool $globalOnly = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return match (static::$globalOnly) {
            true => __('People'),
            false => __('Order'),
            default => __('Service order'),
        };
    }

    public function form(Schema $schema): Schema
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();

        return EventTaskForm::configure($schema, $event);
    }

    public function table(Table $table): Table
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();
        $summary = app(EventTaskRowSummary::class);
        $canUpdate = auth()->user()?->can('update', $event) ?? false;
        $isOrder = static::$globalOnly === false;

        $table = $table
            ->recordTitleAttribute('name')
            ->header(fn () => view('filament.events.workspace-toolbar', ['isOrder' => $isOrder]))
            ->paginated(false)
            ->emptyStateHeading($isOrder ? __('Empty service order') : __('No whole-service responsibilities'))
            ->emptyStateDescription($isOrder
                ? __('Add timed activities to build the run-of-show.')
                : __('Whole-service roles (people and teams for the entire service) appear here.'))
            ->modifyQueryUsing(fn (Builder $query): Builder => $this->scopeTasksQuery($query))
            ->columns([
                ViewColumn::make('drag_handle')
                    ->label('')
                    ->disabledClick()
                    ->view('filament.tables.order-drag-handle')
                    ->visible($isOrder && $canUpdate && ! $this->isReadOnly()),
                TextColumn::make('starts_at')
                    ->label(__('Start'))
                    ->visible($isOrder)
                    ->formatStateUsing(function ($state, EventTask $record): string {
                        if ($record->isGlobal()) {
                            return '';
                        }

                        if (blank($state)) {
                            return '—';
                        }

                        return Str::of((string) $state)->substr(0, 5)->toString();
                    }),
                TextColumn::make('duration')
                    ->label(__('Mins'))
                    ->visible($isOrder)
                    ->formatStateUsing(function ($state, EventTask $record): string {
                        if ($record->isGlobal()) {
                            return '—';
                        }

                        return $state ? (string) $state : '—';
                    }),
                TextColumn::make('displayName')
                    ->label($isOrder ? __('Activity') : __('Responsibility'))
                    ->action($this->isReadOnly() ? ViewEventTaskAction::makeTableAction($event) : null)
                    ->state(fn (EventTask $record): string => $record->displayName())
                    ->description(function (EventTask $record) use ($isOrder): ?string {
                        if (! $isOrder) {
                            return $record->template?->name !== $record->displayName()
                                ? $record->template?->name
                                : null;
                        }

                        if ($record->template?->name !== $record->displayName()) {
                            return __('Activity type:').' '.$record->template?->name;
                        }

                        return null;
                    }),
                TextColumn::make('summary')
                    ->label(__('Details'))
                    ->disabledClick()
                    ->state(fn (EventTask $record): string => $summary->html($record, $event)->toHtml())
                    ->html()
                    ->wrap(),
            ])
            ->headerActions([
                Action::make('printPdf')
                    ->label(__('Print PDF'))
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (): string => route('events.pdf', $event))
                    ->openUrlInNewTab(),
                $this->eventMessagesAction($event),
                ActionGroup::make([
                    Action::make('editDetails')
                        ->label(__('Edit event details'))
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->url(fn (): string => EventResource::getUrl('edit-details', ['record' => $event])),
                    Action::make('viewReadonly')
                        ->label(__('View'))
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn (): string => EventResource::getUrl('view', ['record' => $event])),
                    DeleteAction::make('deleteEvent')
                        ->label(__('Delete event'))
                        ->model(Event::class)
                        ->record($event)
                        ->authorize(fn (): bool => auth()->user()?->can('delete', $event) ?? false)
                        ->successRedirectUrl(fn (): string => EventResource::getUrl('index')),
                ])
                    ->label(__('More'))
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->button()
                    ->color('gray')
                    ->visible($canUpdate && ! $this->isReadOnly()),
                Action::make('openWorkspace')
                    ->label(__('Edit'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->button()
                    ->color('gray')
                    ->url(fn (): string => EventResource::getUrl('edit', ['record' => $event]))
                    ->visible($canUpdate && $this->isReadOnly()),
                CreateAction::make()
                    ->label($isOrder ? __('Add activity') : __('Add responsibility'))
                    ->visible(fn (): bool => $canUpdate && ! $this->isReadOnly())
                    ->createAnother(false)
                    ->extraModalFooterActions(fn (CreateAction $action): array => [
                        EditEventTaskAction::saveAndMessageButton($action, $event),
                    ])
                    ->after(function (array $arguments, EventTask $record, HasActions $livewire): void {
                        if ($arguments['sendMessage'] ?? false) {
                            SendTaskMessageAction::openAfterSave($livewire, $record);
                        }
                    })
                    ->using(function (array $data): EventTask {
                        /** @var Event $event */
                        $event = $this->getOwnerRecord();

                        return EventTaskForm::create($event, $data);
                    }),
            ])
            ->recordActions([
                Action::make('moveUp')
                    ->label(__('Move up'))
                    ->icon(Heroicon::OutlinedArrowUp)
                    ->iconButton()
                    ->color('gray')
                    ->visible(fn (EventTask $record): bool => $canUpdate && $isOrder && ! $this->isReadOnly() && ! $record->isGlobal())
                    ->action(fn (EventTask $record) => $this->moveTask($record, -1)),
                Action::make('moveDown')
                    ->label(__('Move down'))
                    ->icon(Heroicon::OutlinedArrowDown)
                    ->iconButton()
                    ->color('gray')
                    ->visible(fn (EventTask $record): bool => $canUpdate && $isOrder && ! $this->isReadOnly() && ! $record->isGlobal())
                    ->action(fn (EventTask $record) => $this->moveTask($record, 1)),
                EditEventTaskAction::makeTableAction($event)
                    ->visible(fn (): bool => $canUpdate),
                DeleteAction::make()
                    ->visible(fn (): bool => $canUpdate)
                    ->after(function () use ($event): void {
                        app(EventTaskTimeline::class)->recalculate($event->fresh(['tasks.template']));
                    }),
            ]);

        if ($isOrder) {
            $table
                ->reorderable('sort_order', $canUpdate && ! $this->isReadOnly())
                ->reorderRecordsTriggerAction(fn (Action $action): Action => $action->extraAttributes(['class' => 'wp-order-native-reorder']))
                ->beforeReordering(function (array $order): void {
                    $expected = $this->getTable()->getQuery()->pluck('event_tasks.id')->map(fn (int $id): string => (string) $id)->sort()->values()->all();
                    $submitted = collect($order)->map(fn (mixed $id): string => (string) $id)->sort()->values()->all();
                    abort_unless($expected === $submitted, 422);
                })
                ->afterReordering(function () use ($event): void {
                    app(EventTaskTimeline::class)->recalculate($event->fresh(['tasks.template']));
                });
        }

        return $table;
    }

    protected function scopeTasksQuery(Builder $query): Builder
    {
        $query
            ->with([
                'template',
                'assignedUser',
                'assignedTeam',
                'sharedFiles',
                'pictures',
                'catalogSong',
                'responses',
            ]);

        if (static::$globalOnly === true) {
            $query
                ->whereHas('template', fn (Builder $q) => $q->where('use_globally', true))
                ->orderByRaw('(SELECT default_global_order FROM task_templates WHERE task_templates.id = event_tasks.task_template_id)')
                ->orderBy('event_tasks.id');
        } elseif (static::$globalOnly === false) {
            $query
                ->where(function (Builder $q): void {
                    $q->whereDoesntHave('template')
                        ->orWhereHas('template', fn (Builder $inner) => $inner->where('use_globally', false));
                })
                ->orderBy('event_tasks.sort_order')
                ->orderBy('event_tasks.id');
        } else {
            $query
                ->orderByRaw('(SELECT COALESCE(use_globally, 0) FROM task_templates WHERE task_templates.id = event_tasks.task_template_id) DESC')
                ->orderByRaw('CASE WHEN (SELECT COALESCE(use_globally, 0) FROM task_templates WHERE task_templates.id = event_tasks.task_template_id) = 1 THEN (SELECT default_global_order FROM task_templates WHERE task_templates.id = event_tasks.task_template_id) ELSE event_tasks.sort_order END')
                ->orderBy('event_tasks.id');
        }

        return $query;
    }

    protected function moveTask(EventTask $task, int $direction): void
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();
        abort_unless(auth()->user()?->can('update', $event) && ! $this->isReadOnly(), 403);

        $siblings = EventTask::query()
            ->where('event_id', $event->id)
            ->with('template')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (EventTask $row): bool => ! $row->isGlobal())
            ->values();

        $index = $siblings->search(fn (EventTask $row): bool => $row->id === $task->id);

        if ($index === false) {
            return;
        }

        $swapWith = $index + $direction;

        if ($swapWith < 0 || $swapWith >= $siblings->count()) {
            return;
        }

        /** @var EventTask $a */
        $a = $siblings[$index];
        /** @var EventTask $b */
        $b = $siblings[$swapWith];

        $aOrder = $a->sort_order;
        $a->forceFill(['sort_order' => $b->sort_order])->save();
        $b->forceFill(['sort_order' => $aOrder])->save();

        app(EventTaskTimeline::class)->recalculate($event->fresh(['tasks.template']));
    }

    public function sendTaskMessageAction(): Action
    {
        return SendTaskMessageAction::make();
    }
}
