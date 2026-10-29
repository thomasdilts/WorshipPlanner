<button type="button" class="wp-order-drag-handle" x-sortable-handle data-order-record-key="{{ $getRecord()->getKey() }}" data-table-reorder-label="{{ $getRecord()->displayName() }}" aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">
    <x-filament::icon icon="heroicon-m-bars-3" aria-hidden="true" />
</button>
