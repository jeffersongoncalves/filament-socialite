<?php

namespace JeffersonGoncalves\Filament\Socialite\Http\Controllers;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\Filament\Socialite\Provider;
use JeffersonGoncalves\Filament\Socialite\SocialitePlugin;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use LogicException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialiteController
{
    public function redirect(string $provider): SymfonyRedirectResponse
    {
        [$panel, $plugin, $provider] = $this->resolve($provider);

        return $this->driver($panel, $plugin, $provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        [$panel, $plugin, $provider] = $this->resolve($provider);

        try {
            $oauthUser = $this->driver($panel, $plugin, $provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return $this->fail($panel, __('filament-socialite::messages.failed', ['provider' => $provider->getLabel()]));
        }

        $resolver = $plugin->getUserResolver($panel);
        $user = $resolver->resolve($provider, $oauthUser);

        if (! $user instanceof Authenticatable) {
            return $this->fail($panel, __('filament-socialite::messages.not_registered', ['provider' => $provider->getLabel()]));
        }

        if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
            return $this->fail($panel, __('filament-socialite::messages.forbidden'));
        }

        $resolver->link($user, $provider, $oauthUser);

        $guard = $panel->auth();

        if (! $guard instanceof StatefulGuard) {
            throw new LogicException("The [{$panel->getAuthGuard()}] guard must be session based to log in with Socialite.");
        }

        $guard->login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended($panel->getUrl());
    }

    /**
     * @return array{Panel, SocialitePlugin, Provider}
     */
    protected function resolve(string $name): array
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        /** @var SocialitePlugin $plugin */
        $plugin = $panel->getPlugin('filament-socialite');

        $provider = $plugin->getProvider($name);

        abort_if($provider === null, 404);

        return [$panel, $plugin, $provider];
    }

    protected function driver(Panel $panel, SocialitePlugin $plugin, Provider $provider): SocialiteProvider
    {
        $driver = Socialite::driver($provider->getName());

        // ponytail: scopes/params/stateless only exist on OAuth2 drivers; OAuth1 drivers use their services config as-is
        if ($driver instanceof AbstractProvider) {
            $driver
                ->redirectUrl($plugin->getCallbackUrl($provider, $panel))
                ->scopes($provider->getScopes())
                ->with($provider->getWith());

            if ($provider->isStateless()) {
                $driver->stateless();
            }
        }

        return $driver;
    }

    protected function fail(Panel $panel, string $message): RedirectResponse
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->persistent()
            ->send();

        return redirect()->to($panel->getLoginUrl() ?? $panel->getUrl());
    }
}
