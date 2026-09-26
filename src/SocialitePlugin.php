<?php

namespace JeffersonGoncalves\Filament\Socialite;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\Filament\Socialite\Http\Controllers\SocialiteController;
use Laravel\Socialite\Contracts\User;

class SocialitePlugin implements Plugin
{
    /** @var array<string, Provider> */
    protected array $providers = [];

    protected ?Closure $createUserUsing = null;

    protected ?Closure $resolveUserUsing = null;

    protected bool $registrationEnabled = false;

    protected bool $socialAccounts = true;

    protected string $slug = 'oauth';

    protected string $renderHook = PanelsRenderHook::AUTH_LOGIN_FORM_AFTER;

    public function getId(): string
    {
        return 'filament-socialite';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->routes(function (): void {
                Route::name('socialite.')
                    ->prefix($this->slug)
                    ->group(function (): void {
                        Route::get('{provider}/redirect', [SocialiteController::class, 'redirect'])->name('redirect');
                        Route::get('{provider}/callback', [SocialiteController::class, 'callback'])->name('callback');
                    });
            })
            ->renderHook($this->renderHook, fn () => view('filament-socialite::social-buttons', [
                'providers' => $this->providers,
                'panel' => $panel,
            ]));
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * @param  array<int, Provider>  $providers
     */
    public function providers(array $providers): static
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }

        return $this;
    }

    /**
     * @param  Closure(User, Provider): Model  $callback
     */
    public function createUserUsing(?Closure $callback): static
    {
        $this->createUserUsing = $callback;

        return $this;
    }

    /**
     * @param  Closure(User, Provider): ?Model  $callback
     */
    public function resolveUserUsing(?Closure $callback): static
    {
        $this->resolveUserUsing = $callback;

        return $this;
    }

    public function registrationEnabled(bool $condition = true): static
    {
        $this->registrationEnabled = $condition;

        return $this;
    }

    /**
     * Store every login in the social accounts table. Disable to only match users by email.
     */
    public function socialAccounts(bool $condition = true): static
    {
        $this->socialAccounts = $condition;

        return $this;
    }

    public function slug(string $slug): static
    {
        $this->slug = trim($slug, '/');

        return $this;
    }

    public function renderHook(string $name): static
    {
        $this->renderHook = $name;

        return $this;
    }

    /**
     * @return array<string, Provider>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    public function getProvider(string $name): ?Provider
    {
        return $this->providers[$name] ?? null;
    }

    public function getUserResolver(Panel $panel): SocialiteUserResolver
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('auth.providers.'.config("auth.guards.{$panel->getAuthGuard()}.provider").'.model');

        return new SocialiteUserResolver(
            userModel: $userModel,
            socialAccounts: $this->socialAccounts,
            registrationEnabled: $this->registrationEnabled,
            resolveUserUsing: $this->resolveUserUsing,
            createUserUsing: $this->createUserUsing,
        );
    }

    public function getRedirectUrl(Provider $provider, Panel $panel): string
    {
        return route("filament.{$panel->getId()}.socialite.redirect", ['provider' => $provider->getName()]);
    }

    public function getCallbackUrl(Provider $provider, Panel $panel): string
    {
        return route("filament.{$panel->getId()}.socialite.callback", ['provider' => $provider->getName()]);
    }
}
