<details
    {{ $attributes->class(['wp-mobile-disclosure']) }}
    wire:ignore.self
    x-data="{
        smallScreen: matchMedia('(max-width: 767px)').matches,
        syncViewport() {
            const smallScreen = matchMedia('(max-width: 767px)').matches;
            if (smallScreen !== this.smallScreen) {
                this.smallScreen = smallScreen;
                this.$el.open = !smallScreen;
            }
        }
    }"
    x-init="$el.open = !smallScreen"
    x-on:resize.window.debounce.100ms="syncViewport()"
    x-on:toggle="$dispatch('planner-layout-changed')"
>
    <summary class="wp-mobile-disclosure-summary">
        <span class="wp-mobile-disclosure-label">{{ $summary }}</span>
        <x-filament::icon icon="heroicon-m-chevron-down" class="wp-mobile-disclosure-chevron" aria-hidden="true" />
    </summary>

    {{ $slot }}
</details>
