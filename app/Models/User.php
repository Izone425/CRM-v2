<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use App\Models\Role;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use TomatoPHP\FilamentTwilio\Traits\InteractsWithTwilioWhatsapp;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;
    use InteractsWithTwilioWhatsapp;

    public const IS_SUPERADMIN = 'superadmin';
    public const IS_ADMIN = 'admin';
    public const IS_MANAGER = 'manager';
    public const IS_USER = 'user';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'department',
        'position',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // @todo Change this to check for access level
        // return $this->role->name === 'Admin';
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function sAdmin(): bool
    {
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role && $this->role->name === 'Admin';
    }
}
