<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use App\Models\Role;
use Beta\Microsoft\Graph\TermStore\Model\Store;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Svg\Gradient\Stop;
use TomatoPHP\FilamentTwilio\Traits\InteractsWithTwilioWhatsapp;

class User extends Authenticatable implements FilamentUser, HasAvatar
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
        'mobile_number',
        'department',
        'position',
        'email',
        'password',
        'role_id',
        'route_permissions',
        'avatar_path',
        'signature_path',
        'msteam_link',
        'additional_role',
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
        'route_permissions' => 'array',
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

    public function getMailerConfig()
    {
        return [
            'transport' => 'smtp',
            'host' => $this->smtp_host ?? env('MAIL_HOST'),
            'port' => $this->smtp_port ?? env('MAIL_PORT'),
            'encryption' => $this->smtp_encryption ?? env('MAIL_ENCRYPTION'),
            'username' => $this->smtp_username ?? env('MAIL_USERNAME'),
            'password' => $this->smtp_password ?? env('MAIL_PASSWORD'),
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if($this->avatar_path && Storage::disk("public")->exists($this->avatar_path)){
            return Storage::url($this->avatar_path);
        }
        return "https://ui-avatars.com/api" . '?' .  http_build_query(["name" => $this->name, "background" => "random"]);
    }

    public function hasRouteAccess(string $routeName): bool
    {
        // Admin (role_id = 3) always has access
        if ($this->role_id == 3) {
            return true;
        }

        // Check permissions in the JSON column
        $permissions = $this->route_permissions ?? [];

        // If route not found in permissions, deny access
        if (!isset($permissions[$routeName])) {
            return false;
        }

        return (bool) $permissions[$routeName];
    }

    /**
     * Check if the user has access to any of the routes in a group
     *
     * @param array $routeNames
     * @return bool
     */
    public function hasAccessToAny(array $routeNames): bool
    {
        // Admin (role_id = 3) always has access
        if ($this->role_id == 3) {
            return true;
        }

        foreach ($routeNames as $routeName) {
            if ($this->hasRouteAccess($routeName)) {
                return true;
            }
        }

        return false;
    }
}
