<?php

use JeffersonGoncalves\Filament\Socialite\Models\SocialAccount;
use JeffersonGoncalves\Filament\Socialite\Provider;
use JeffersonGoncalves\Filament\Socialite\SocialiteUserResolver;
use JeffersonGoncalves\Filament\Socialite\Tests\Fixtures\User;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OAuthUser;

function fakeOAuthUser(array $attributes = []): OAuthUser
{
    return (new OAuthUser)
        ->map(array_merge(['id' => '123', 'name' => 'Jane', 'nickname' => 'jane', 'email' => 'jane@example.com'], $attributes))
        ->setToken('access-token');
}

function fakeSocialite(OAuthUser|Throwable $result): void
{
    $driver = Mockery::mock(SocialiteProvider::class);
    $result instanceof Throwable
        ? $driver->shouldReceive('user')->andThrow($result)
        : $driver->shouldReceive('user')->andReturn($result);

    Socialite::shouldReceive('driver')->with('github')->andReturn($driver);
}

it('renders provider buttons on the login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('GitHub')
        ->assertSee('/admin/oauth/github/redirect', escape: false);
});

it('redirects to the provider with the callback url and scopes', function () {
    $location = $this->get('/admin/oauth/github/redirect')->assertRedirect()->headers->get('Location');

    expect($location)
        ->toStartWith('https://github.com/login/oauth/authorize')
        ->toContain(urlencode('http://localhost/admin/oauth/github/callback'))
        ->toContain(urlencode('read:user'));
});

it('returns 404 for providers not registered on the panel', function () {
    $this->get('/admin/oauth/gitlab/redirect')->assertNotFound();
});

it('registers, links and logs in a new user', function () {
    fakeSocialite(fakeOAuthUser());

    $this->get('/admin/oauth/github/callback')->assertRedirect('http://localhost/admin');

    $user = User::query()->where('email', 'jane@example.com')->sole();
    $account = SocialAccount::query()->sole();

    expect(auth()->id())->toBe($user->id)
        ->and($account->user_id)->toBe($user->id)
        ->and($account->provider_id)->toBe('123')
        ->and($account->token)->toBe('access-token');
});

it('logs in an existing user through the linked account even if the email changed', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'old@example.com', 'password' => 'x']);
    SocialAccount::query()->create(['user_id' => $user->id, 'provider' => 'github', 'provider_id' => '123']);
    fakeSocialite(fakeOAuthUser(['email' => 'new@example.com']));

    $this->get('/admin/oauth/github/callback')->assertRedirect('http://localhost/admin');

    expect(auth()->id())->toBe($user->id)
        ->and(User::query()->count())->toBe(1);
});

it('refuses users that cannot access the panel', function () {
    fakeSocialite(fakeOAuthUser(['email' => 'jane@blocked.test']));

    $this->get('/admin/oauth/github/callback')->assertRedirect('http://localhost/admin/login');

    expect(auth()->check())->toBeFalse();
});

it('sends the user back to login when the provider fails', function () {
    fakeSocialite(new RuntimeException('invalid state'));

    $this->get('/admin/oauth/github/callback')->assertRedirect('http://localhost/admin/login');

    expect(auth()->check())->toBeFalse();
});

describe('SocialiteUserResolver', function () {
    it('does not create users when registration is disabled', function () {
        $resolver = new SocialiteUserResolver(User::class);

        expect($resolver->resolve(Provider::make('github'), fakeOAuthUser()))->toBeNull()
            ->and(User::query()->count())->toBe(0);
    });

    it('matches by email without the social accounts table', function () {
        $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'x']);
        $resolver = new SocialiteUserResolver(User::class, socialAccounts: false);

        $resolver->link($user, Provider::make('github'), fakeOAuthUser());

        expect($resolver->resolve(Provider::make('github'), fakeOAuthUser())->is($user))->toBeTrue()
            ->and(SocialAccount::query()->count())->toBe(0);
    });

    it('delegates to custom callbacks', function () {
        $resolver = new SocialiteUserResolver(
            User::class,
            registrationEnabled: true,
            createUserUsing: fn ($oauthUser) => User::query()->create(['name' => 'Custom', 'email' => $oauthUser->getEmail(), 'password' => 'x']),
        );

        expect($resolver->resolve(Provider::make('github'), fakeOAuthUser())->name)->toBe('Custom');
    });
});
