@php
    use App\Support\HtmlMessage;
    $subject = $subject ?? null;
    $html = HtmlMessage::sanitize($html ?? '');
@endphp

<div class="space-y-3">
    @if (filled($subject))
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Subject') }}</div>
            <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $subject }}</div>
        </div>
    @endif

    <div>
        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Preview') }}</div>
        <div
            class="prose prose-sm dark:prose-invert mt-2 max-w-none rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
        >
            {!! $html !== '' ? $html : '<p class="text-gray-500">'.e(__('No content yet.')).'</p>' !!}
        </div>
    </div>
</div>
