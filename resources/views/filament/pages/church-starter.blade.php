<x-filament-panels::page>
    <div class="wp-church-starter">
        <ol class="wp-church-starter-list">
            @foreach ($this->steps() as $step)
                <li @class([
                    'wp-church-starter-step',
                    'wp-church-starter-step-done' => $step['done'],
                ])>
                    <span class="wp-church-starter-mark" aria-hidden="true">
                        @if ($step['done'])
                            ✓
                        @else
                            {{ $loop->iteration }}
                        @endif
                    </span>
                    <div class="wp-church-starter-body">
                        <div class="wp-church-starter-title-row">
                            <h2 class="wp-church-starter-title">{{ $step['label'] }}</h2>
                            @if ($step['done'])
                                <span class="wp-church-starter-badge">{{ __('Done') }}</span>
                            @elseif (filled($step['url']))
                                <a href="{{ $step['url'] }}" class="wp-church-starter-action">
                                    {{ __('Continue') }}
                                </a>
                            @endif
                        </div>
                        <p class="wp-church-starter-hint">{{ $step['hint'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</x-filament-panels::page>
