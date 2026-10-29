@props([
    'taskId' => null,
    'taskIds' => [],
    'html' => '',
    'vacant' => false,
    'conflict' => false,
    'state' => null,
    'focusCell' => null,
])

@php
    use App\Enums\AssignmentState;

    $state = $state instanceof AssignmentState ? $state : null;
    $taskIds = collect($taskIds)->map(fn ($id) => (int) $id)->filter()->values()->all();
    if ($taskIds === [] && $taskId) {
        $taskIds = [(int) $taskId];
    }
    $multiple = count($taskIds) > 1;
    $focus = $focusCell ? e($focusCell) : '';
@endphp

@if ($taskIds === [])
    <span class="wp-ss-empty">{{ __('Not used') }}</span>
@else
    <div class="wp-ss-cell{{ $vacant ? ' wp-ss-vacant' : '' }}{{ $multiple ? ' wp-ss-multiple' : '' }}">
    <button
        type="button"
        @if ($focus !== '') data-wp-cell="{{ $focus }}" @endif
        @if ($multiple)
            wire:click="mountAction('chooseEventTask', { taskIds: @js($taskIds), focusCell: @js($focusCell) })"
            aria-label="{{ __('Edit activities') }}"
        @else
            wire:click="mountAction('editEventTask', { taskId: {{ (int) $taskIds[0] }}, focusCell: @js($focusCell) })"
            aria-label="{{ $vacant ? __('Assign person or team') : __('Edit assignment') }}"
        @endif
        @if (! $vacant) title="{{ trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')) }}" @endif
        class="wp-ss-cell-edit"
    >
        <span class="sr-only">{{ __('Edit assignment') }}</span>
    </button>
        <span class="wp-ss-cell-main">
            @if ($vacant)
                {{ __('Not assigned') }}
            @else
                {!! $html !!}
            @endif
        </span>
        @if ($multiple)
            <span class="wp-status wp-status-neutral">
                {{ __(':count activities', ['count' => count($taskIds)]) }}
            </span>
        @endif
        @if ($conflict)
            <span class="wp-status wp-status-conflict">
                <x-filament::icon icon="heroicon-m-exclamation-triangle" aria-hidden="true" />
                {{ __('Unavailable') }}
            </span>
        @endif
        @if ($state?->showsOnGrid())
            <x-assignment-status :state="$state" />
        @endif
    </div>
@endif
