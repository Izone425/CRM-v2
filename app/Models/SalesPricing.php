<?php
// filepath: /var/www/html/timeteccrm/app/Models/SalesPricing.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesPricing extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_pricings';

    protected $fillable = [
        'title',
        'summary',
        'effective_date',
        'expiry_date',
        'status',
        'access_right',
        'created_by',
        'last_updated_by'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'access_right' => 'array',
    ];

    /**
     * Get the pages for this sales pricing
     */
    public function pages()
    {
        return $this->hasMany(SalesPricingPage::class)->orderBy('order');
    }

    /**
     * Get the user who created the sales pricing
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the sales pricing
     */
    public function lastUpdatedByUser()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Check if a specific role has access to this sales pricing
     */
    public function hasRoleAccess(int $roleId): bool
    {
        if (empty($this->access_right)) {
            return true; // If no restrictions, grant access to all
        }

        return in_array($roleId, $this->access_right);
    }

    /**
     * Get the access_right attribute.
     */
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
     */
    public function setAccessRightAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['access_right'] = json_encode($value);
        } else {
            $this->attributes['access_right'] = $value;
        }
    }

    /**
     * Scope to filter by user role access
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where(function ($q) use ($roleId) {
            $q->whereNull('access_right')
              ->orWhere('access_right', '[]')
              ->orWhereJsonContains('access_right', (string)$roleId);
        });
    }
}
