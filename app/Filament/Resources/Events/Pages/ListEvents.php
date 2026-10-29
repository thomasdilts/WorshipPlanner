<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    /** @var array<string, string>|null */
    protected ?array $eventNames = null;

    public bool $allEventTypes = true;

    /** @var array<string, string> */
    protected array $extraBodyAttributes = [
        'class' => 'wp-events-list',
    ];

    public function getTitle(): string|Htmlable
    {
        return __('Services');
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getBreadcrumb(): ?string
    {
        return __('List');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function mount(): void
    {
        // Ensure the from-date filter is active on first load (today onwards).
        $this->tableFilters ??= [];
        $this->tableFilters['from_date']['date'] ??= now()->toDateString();
        $this->tableFilters['events']['names'] ??= array_keys($this->eventNameOptions());
        $this->allEventTypes = array_diff(array_keys($this->eventNameOptions()), $this->tableFilters['events']['names']) === [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /** @return array<string, string> */
    public function eventNameOptions(): array
    {
        if ($this->eventNames !== null) {
            return $this->eventNames;
        }

        $query = EventResource::getEloquentQuery();
        $from = $this->tableFilters['from_date']['date'] ?? null;
        $until = $this->tableFilters['from_date']['until'] ?? null;

        if (filled($from)) {
            $query->whereDate('starts_at', '>=', $from);
        }

        if (filled($until)) {
            $query->whereDate('starts_at', '<=', $until);
        }

        return $this->eventNames = $query
            ->select('name')
            ->distinct()
            ->pluck('name')
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (string $name): array => [$name => $name])
            ->all();
    }

    public function selectEventType(?string $name = null): void
    {
        abort_unless($name === null || array_key_exists($name, $this->eventNameOptions()), 422);

        $this->allEventTypes = $name === null;
        $this->tableFilters['events']['names'] = $name === null ? array_keys($this->eventNameOptions()) : [$name];
        $this->updatedTableFilters();
    }

    public function selectEventTypes(bool $all): void
    {
        $this->allEventTypes = $all;
        $this->tableFilters['events']['names'] = $all ? array_keys($this->eventNameOptions()) : [];
        $this->updatedTableFilters();
    }

    public function showEventsFromToday(): void
    {
        $this->tableFilters['from_date']['date'] = now()->toDateString();
        $this->tableFilters['from_date']['until'] = null;
        $this->updatedTableFilters();
    }

    public function updatedTableFilters(mixed $value = null, ?string $key = null): void
    {
        if (str_starts_with($key ?? '', 'events.') || (is_array($value) && array_key_exists('events', $value))) {
            $this->allEventTypes = false;
        }

        $this->eventNames = null;

        if ($this->allEventTypes) {
            $this->tableFilters['events']['names'] = array_keys($this->eventNameOptions());
        }

        parent::updatedTableFilters();
    }
}
