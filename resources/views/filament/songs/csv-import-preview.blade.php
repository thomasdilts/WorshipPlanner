@php
    /** @var array{valid: bool, error: ?string, created: int, updated: int, skipped: int, samples: list<array{name: string, action: string, author: ?string}>}|null $preview */
    $preview = $preview ?? null;
@endphp

@if ($preview === null)
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('Upload a CSV to see what will be created or updated.') }}
    </p>
@elseif (! $preview['valid'])
    <div class="rounded-lg border border-danger-300 bg-danger-50 px-3 py-2 text-sm text-danger-700 dark:border-danger-600 dark:bg-danger-950 dark:text-danger-300">
        {{ $preview['error'] ?: __('This file cannot be imported.') }}
    </div>
@else
    <div class="space-y-3 text-sm">
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex rounded-md bg-success-100 px-2 py-1 font-medium text-success-800 dark:bg-success-950 dark:text-success-200">
                {{ __('New: :count', ['count' => $preview['created']]) }}
            </span>
            <span class="inline-flex rounded-md bg-warning-100 px-2 py-1 font-medium text-warning-800 dark:bg-warning-950 dark:text-warning-200">
                {{ __('Update existing: :count', ['count' => $preview['updated']]) }}
            </span>
            <span class="inline-flex rounded-md bg-gray-100 px-2 py-1 font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                {{ __('Skipped rows: :count', ['count' => $preview['skipped']]) }}
            </span>
        </div>

        <p class="text-gray-600 dark:text-gray-300">
            {{ __('Matching songs are found by Name. Existing rows keep the same song and refresh lyrics/author.') }}
        </p>

        @if ($preview['samples'] !== [])
            <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                @foreach ($preview['samples'] as $sample)
                    <li class="flex items-start justify-between gap-3 px-3 py-2">
                        <div>
                            <div class="font-medium text-gray-950 dark:text-white">{{ $sample['name'] }}</div>
                            @if (filled($sample['author']))
                                <div class="text-gray-500 dark:text-gray-400">{{ $sample['author'] }}</div>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $sample['action'] === 'update' ? __('Update') : __('Create') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
