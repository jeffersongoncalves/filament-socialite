@php
    /** @var array<string, \JeffersonGoncalves\Filament\Socialite\Provider> $providers */
    $plugin = $panel->getPlugin('filament-socialite');
@endphp

@if (count($providers))
    <div class="fi-socialite" style="display: grid; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="flex: 1; border-top: 1px solid currentColor; opacity: 0.15;"></span>
            <span style="font-size: 0.875rem; opacity: 0.7;">
                {{ __('filament-socialite::messages.divider') }}
            </span>
            <span style="flex: 1; border-top: 1px solid currentColor; opacity: 0.15;"></span>
        </div>

        <div style="display: grid; gap: 0.75rem;">
            @foreach ($providers as $provider)
                <x-filament::button
                    tag="a"
                    :href="$plugin->getRedirectUrl($provider, $panel)"
                    :color="$provider->getColor()"
                    :icon="$provider->getIcon()"
                    outlined
                >
                    {{ $provider->getLabel() }}
                </x-filament::button>
            @endforeach
        </div>
    </div>
@endif
