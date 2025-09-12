<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'access_right',
    ];

    protected $casts = [
        'access_right' => 'array',
    ];

    /**
     * Get the policies for the category
     */
    public function policies()
    {
        return $this->hasMany(Policy::class, 'category_id');
    }

    /**
     * Check if a specific role has access to this category
     */
    public function hasRoleAccess(int $roleId): bool
    {
        if (empty($this->access_right)) {
            return true; // If no restrictions, grant access to all
        }

        return in_array($roleId, $this->access_right);
    }

    public function getAccessRightAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }

        return $value;
    }

    /**
     * Set the access_right attribute.
     *
     * @param  array|string  $value
     * @return void
     */
    public function setAccessRightAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['access_right'] = json_encode($value);
        } else {
            $this->attributes['access_right'] = $value;
        }
    }
}
