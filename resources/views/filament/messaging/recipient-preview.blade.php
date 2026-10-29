@php
    /** @var list<array{user_id: int, name: string, channel: string, channel_label: string, via_team: ?string, can_deliver: bool, detail: ?string}> $rows */
@endphp

<div class="wp-recipient-preview">
    @if ($rows === [])
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No recipients selected yet.') }}</p>
    @else
        <ul class="space-y-2 text-sm">
            @foreach ($rows as $row)
                <li class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="font-medium">{{ $row['name'] }}</span>
                    @if ($row['task'] ?? null)
                        <span class="text-gray-700 dark:text-gray-300">— {{ $row['task'] }}</span>
                    @endif
                    <span class="text-gray-500 dark:text-gray-400">· {{ $row['channel_label'] }}</span>
                    @if ($row['via_team'])
                        <span class="text-gray-500 dark:text-gray-400">· {{ __('Team leader for :team', ['team' => $row['via_team']]) }}</span>
                    @endif
                    @if (! $row['can_deliver'])
                        <span class="text-danger-600 dark:text-danger-400">· {{ $row['detail'] ?? __('Cannot deliver') }}</span>
                    @elseif ($row['detail'])
                        <span class="text-gray-500 dark:text-gray-400">· {{ $row['detail'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ __(':count message(s) will be sent, one per person and task.', ['count' => count($rows)]) }}
        </p>
    @endif
</div>
