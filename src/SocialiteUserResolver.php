<?php

namespace JeffersonGoncalves\Filament\Socialite;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use JeffersonGoncalves\Filament\Socialite\Models\SocialAccount;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Framework-agnostic (no Filament) user resolution, so it can move to a plain Laravel package later.
 */
class SocialiteUserResolver
{
    /**
     * @param  class-string<Model>  $userModel
     * @param  (Closure(SocialiteUser, Provider): ?Model)|null  $resolveUserUsing
     * @param  (Closure(SocialiteUser, Provider): ?Model)|null  $createUserUsing
     */
    public function __construct(
        protected string $userModel,
        protected bool $socialAccounts = true,
        protected bool $registrationEnabled = false,
        protected ?Closure $resolveUserUsing = null,
        protected ?Closure $createUserUsing = null,
    ) {}

    public function resolve(Provider $provider, SocialiteUser $oauthUser): ?Model
    {
        if ($this->resolveUserUsing) {
            return ($this->resolveUserUsing)($oauthUser, $provider);
        }

        if ($this->socialAccounts) {
            $account = static::socialAccountModel()::query()
                ->where('provider', $provider->getName())
                ->where('provider_id', (string) $oauthUser->getId())
                ->first();

            if ($account) {
                return $this->userModel::query()->find($account->getAttribute('user_id'));
            }
        }

        // ponytail: trusts the provider's email; use resolveUserUsing() for providers that don't verify emails
        if (filled($oauthUser->getEmail())) {
            $user = $this->userModel::query()->where('email', $oauthUser->getEmail())->first();

            if ($user) {
                return $user;
            }
        }

        if (! $this->registrationEnabled) {
            return null;
        }

        if ($this->createUserUsing) {
            return ($this->createUserUsing)($oauthUser, $provider);
        }

        if (blank($oauthUser->getEmail())) {
            return null;
        }

        $user = new $this->userModel;
        $user->forceFill([
            'name' => $oauthUser->getName() ?? $oauthUser->getNickname() ?? $oauthUser->getEmail(),
            'email' => $oauthUser->getEmail(),
            'password' => Hash::make(Str::random(64)),
        ])->save();

        return $user;
    }

    public function link(Model $user, Provider $provider, SocialiteUser $oauthUser): void
    {
        if (! $this->socialAccounts) {
            return;
        }

        static::socialAccountModel()::query()->updateOrCreate(
            ['provider' => $provider->getName(), 'provider_id' => (string) $oauthUser->getId()],
            [
                'user_id' => $user->getKey(),
                'token' => $oauthUser->token ?? null,
                'refresh_token' => $oauthUser->refreshToken ?? null,
            ],
        );
    }

    /**
     * @return class-string<Model>
     */
    public static function socialAccountModel(): string
    {
        return config('filament-socialite.social_account_model', SocialAccount::class);
    }
}
