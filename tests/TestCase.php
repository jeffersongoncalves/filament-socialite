<?php

namespace JeffersonGoncalves\Filament\Socialite\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\Filament\Socialite\Tests\Fixtures\TestPanelProvider;
use JeffersonGoncalves\Filament\Socialite\Tests\Fixtures\User;
use Laravel\Socialite\SocialiteServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            SocialiteServiceProvider::class,
            \JeffersonGoncalves\Socialite\SocialiteServiceProvider::class,
            \JeffersonGoncalves\Filament\Socialite\SocialiteServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', $this->databaseConnection());
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('auth.providers.users.model', User::class);
        config()->set('services.github', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'redirect' => null,
        ]);
    }

    /**
     * In-memory SQLite locally; CI sets SOCIALITE_TEST_DB_* to run against MySQL and PostgreSQL.
     * Not the plain DB_* names: Testbench sets DB_CONNECTION=testing itself.
     *
     * @return array<string, mixed>
     */
    protected function databaseConnection(): array
    {
        $driver = env('SOCIALITE_TEST_DB_DRIVER', 'sqlite');

        if ($driver === 'sqlite') {
            return ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        }

        return [
            'driver' => $driver,
            'host' => env('SOCIALITE_TEST_DB_HOST', '127.0.0.1'),
            'port' => env('SOCIALITE_TEST_DB_PORT'),
            'database' => env('SOCIALITE_TEST_DB_DATABASE', 'testing'),
            'username' => env('SOCIALITE_TEST_DB_USERNAME', 'root'),
            'password' => env('SOCIALITE_TEST_DB_PASSWORD', ''),
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'prefix' => '',
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        // laravel-socialite ships a .php.stub; copy it next to the users migration so the migrator runs both in order.
        $path = sys_get_temp_dir().'/filament-socialite-migrations';

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        copy(__DIR__.'/database/migrations/0000_00_00_000000_create_users_table.php', $path.'/0000_00_00_000000_create_users_table.php');
        copy(__DIR__.'/../vendor/jeffersongoncalves/laravel-socialite/database/migrations/create_social_accounts_table.php.stub', $path.'/0000_00_00_000001_create_social_accounts_table.php');

        $this->loadMigrationsFrom($path);
    }
}
