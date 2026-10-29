@php
    $locale = str_replace('_', '-', app()->getLocale());
    $dir = in_array(app()->getLocale(), ['ar'], true) ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Invitation unavailable') }} — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: system-ui, sans-serif; margin: 0; background: #f6f8fa; color: #172b2c; }
        .wrap { box-sizing: border-box; width: calc(100% - 2rem); max-width: 28rem; margin: 2rem auto; padding: 1.25rem; overflow-wrap: anywhere; background: #fff; border: 1px solid #dce3e8; border-radius: 12px; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        p { color: #52636b; line-height: 1.45; }
        @media (prefers-color-scheme: dark) {
            body { background: #0e1415; color: #e6eeee; }
            .wrap { background: #151d1e; border-color: #263234; }
            p { color: #9fb0b3; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <h1>{{ __('Invitation unavailable') }}</h1>
        <p>{{ $reason }}</p>
    </main>
</body>
</html>
