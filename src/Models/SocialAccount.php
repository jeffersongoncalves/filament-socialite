<?php

namespace JeffersonGoncalves\Filament\Socialite\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|string $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $token
 * @property string|null $refresh_token
 */
class SocialAccount extends Model
{
    protected $guarded = [];

    protected $hidden = ['token', 'refresh_token'];

    protected $casts = [
        'token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];
}
