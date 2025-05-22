<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'route_permissions',
    ];

    protected $casts = [
        'route_permissions' => 'array',
    ];

    /**
     * Get the users associated with this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Determine if this role has access to a specific route
     *
     * @param string $routeName
     * @return bool
     */
    public function hasRouteAccess(string $routeName): bool
    {
        $permissions = $this->route_permissions ?? [];
        return isset($permissions[$routeName]) && $permissions[$routeName] === true;
    }
}
