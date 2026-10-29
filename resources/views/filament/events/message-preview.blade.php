@php
    use App\Support\HtmlMessage;

    $failedRecipients = $message->recipients->filter(
        fn ($recipient): bool => ($recipient->pivot->delivery_status ?? null) === 'failed'
    );
@endphp

<div class="space-y-4 text-sm">
    <div>
        <div class="font-medium text-gray-500 dark:text-gray-400">{{ __('Recipients') }}</div>
        <div class="mt-1">
            @forelse ($message->recipients as $recipient)
                @php
                    $delivery = match ((string) ($recipient->pivot->delivery_status ?? '')) {
                        'sent' => __('Sent'),
                        'failed' => __('Failed'),
                        'pending' => __('Pending'),
                        default => (string) ($recipient->pivot->delivery_status ?? ''),
                    };
                @endphp
                <span class="me-2 inline-flex items-center gap-1">
                    {{ $recipient->display_name ?: $recipient->name }}
                    <span class="text-xs text-gray-500">({{ $delivery }})</span>
                </span>
            @empty
                —
            @endforelse
        </div>
    </div>

    @if ($failedRecipients->isNotEmpty())
        <div class="rounded-lg border border-danger-300 bg-danger-50 p-3 text-danger-700 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-300">
            <div class="font-medium">{{ __('Delivery errors') }}</div>
            <ul class="mt-2 space-y-2">
                @foreach ($failedRecipients as $recipient)
                    <li>
                        <span class="font-medium">{{ $recipient->display_name ?: $recipient->name }}:</span>
                        <span class="break-words">{{ $recipient->pivot->delivery_error ?: __('Unknown error') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach ($previews as $preview)
        <div class="space-y-2">
            @if (count($previews) > 1)
                <div class="font-medium text-gray-950 dark:text-white">
                    {{ $preview['user']->display_name ?: $preview['user']->name }}@if ($preview['task']) — {{ $preview['task']->displayName() }}@endif
                </div>
            @endif

            @include('filament.messaging.message-preview', [
                'subject' => $preview['message']->subject,
                'html' => $preview['message']->body,
                'text' => $preview['message']->textBody,
            ])
        </div>
    @endforeach
</div>
