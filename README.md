<div class="filament-hidden">

![Filament Socialite](https://raw.githubusercontent.com/jeffersongoncalves/filament-socialite/3.x/art/jeffersongoncalves-filament-socialite.png)

</div>

# Filament Socialite

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/filament-socialite.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-socialite)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-socialite/tests.yml?branch=3.x&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/filament-socialite/actions?query=workflow%3Atests+branch%3A3.x)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-socialite/pint.yml?branch=3.x&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/filament-socialite/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3A3.x)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/filament-socialite.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-socialite)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/filament-socialite.svg?style=flat-square)](LICENSE.md)

OAuth login for Filament panels powered by [Laravel Socialite](https://laravel.com/docs/socialite): a fluent provider API, buttons injected into the login page, and optional linked social accounts.

## Compatibility

| Package Version | Filament Version |
|-----------------|------------------|
| [1.x](https://github.com/jeffersongoncalves/filament-socialite/tree/1.x) | 3.x |
| [2.x](https://github.com/jeffersongoncalves/filament-socialite/tree/2.x) | 4.x |
| [3.x](https://github.com/jeffersongoncalves/filament-socialite/tree/3.x) | 5.x |

## Installation

You can install the package via composer:

```bash
composer require jeffersongoncalves/filament-socialite:"^3.0"
```

User resolution, the `social_accounts` table and the base `Provider` come from [jeffersongoncalves/laravel-socialite](https://github.com/jeffersongoncalves/laravel-socialite), installed automatically.

Publish and run the migration (skip it if you only want to match users by email, see `socialAccounts(false)` below):

```bash
php artisan vendor:publish --tag="socialite-migrations"
php artisan migrate
```

Optionally publish the config and translations:

```bash
php artisan vendor:publish --tag="socialite-config"
php artisan vendor:publish --tag="filament-socialite-translations"
```

## Configuring providers

Add each provider's credentials to `config/services.php`. The `redirect` key is required by Socialite but its value is ignored: the plugin always uses its own callback route.

```php
'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => null,
],
```

Register the callback URL in the provider's dashboard: `https://your-app.test/{panel-path}/oauth/{provider}/callback`, e.g. `https://your-app.test/admin/oauth/github/callback`.

## Usage

```php
use JeffersonGoncalves\Filament\Socialite\Provider;
use JeffersonGoncalves\Filament\Socialite\SocialitePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->login()
        ->plugin(
            SocialitePlugin::make()
                ->providers([
                    Provider::make('github')
                        ->label('GitHub')
                        ->icon('heroicon-o-code-bracket')
                        ->color('gray')
                        ->scopes(['read:user', 'user:email']),
                    Provider::make('google')
                        ->label('Google')
                        ->color('danger')
                        ->with(['prompt' => 'select_account']),
                ])
                ->registrationEnabled(),
        );
}
```

### Provider options

| Method | Description |
|--------|-------------|
| `label(string)` | Button label (defaults to the headline of the driver name). |
| `icon(string)` | Any Blade Icons name installed in your app (e.g. `heroicon-o-user`, `fab-github` with a Font Awesome icon set). |
| `color(string)` | Filament color (`gray`, `primary`, `danger`, ...). |
| `scopes(array)` | Extra OAuth scopes. |
| `with(array)` | Extra query parameters for the authorization request. |
| `stateless(bool)` | Disable session state verification (APIs/SPAs only). |

Any Socialite driver works, including [Socialite Providers](https://socialiteproviders.com) once registered.

### Plugin options

| Method | Default | Description |
|--------|---------|-------------|
| `registrationEnabled(bool)` | `false` | Create a user when no account matches. |
| `createUserUsing(Closure)` | `null` | Custom user creation: `fn (SocialiteUser $user, Provider $provider): ?Model`. |
| `resolveUserUsing(Closure)` | `null` | Replace the whole lookup: `fn (SocialiteUser $user, Provider $provider): ?Model`. |
| `socialAccounts(bool)` | `true` | Store logins in the `social_accounts` table. With `false`, users are only matched by email. |
| `slug(string)` | `oauth` | Route prefix: `/{panel}/{slug}/{provider}/redirect\|callback`. |
| `renderHook(string)` | `PanelsRenderHook::AUTH_LOGIN_FORM_AFTER` | Where the buttons are rendered. |

### How users are resolved

1. `resolveUserUsing()`, if set, decides alone.
2. A linked row in `social_accounts` (provider + provider id).
3. A user with the same email.
4. If `registrationEnabled()`: `createUserUsing()` or a default `name`/`email`/random `password` user.

After login the social account is linked (tokens are stored encrypted). Users implementing `FilamentUser` must pass `canAccessPanel()`. Failures send the user back to the login page with a notification.

> **Security:** step 3 trusts the email returned by the provider. Only enable providers that verify emails, or use `resolveUserUsing()` to add your own checks.

### Outside Filament

Need the same login flow in a non-Filament app (Blade, Inertia, API)? Use [jeffersongoncalves/laravel-socialite](https://github.com/jeffersongoncalves/laravel-socialite) directly.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
