@props(['reorderable' => true])

<x-filament::dropdown :menu="false" placement="bottom-end" width="sm" max-height="28rem" :teleport="true" class="wp-ss-columns">
    <x-slot name="trigger">
        <button type="button" class="wp-ss-control" aria-label="{{ __('Columns') }}">
            <x-filament::icon icon="heroicon-m-view-columns" aria-hidden="true" />
            {{ __('Columns') }}
        </button>
    </x-slot>
    <x-filament-tables::column-manager
        :apply-action="$this->getTable()->getColumnManagerApplyAction()"
        :columns="$this->getTable()->getColumnManagerColumns()"
        :has-reorderable-columns="$reorderable"
        :has-toggleable-columns="true"
        :reorder-animation-duration="0"
    />
</x-filament::dropdown>
