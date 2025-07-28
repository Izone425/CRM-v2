<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_code',
        'accessible_by_lead_owners',
        'accessible_by_timetec_hr_salespeople',
        'accessible_by_non_timetec_hr_salespeople',
    ];

    public function lead(): HasMany
    {
        return $this->hasMany(Lead::class, 'lead_code', 'lead_code');
    }

    public function isAccessibleByUser(User $user): bool
    {
        // Managers can access all lead sources
        if ($user->role_id === 3) {
            return true;
        }

        // Lead Owner
        if ($user->role_id === 1) {
            return $this->accessible_by_lead_owners;
        }

        // Salesperson
        if ($user->role_id === 2) {
            // Check if user is TimeTec HR or Non-TimeTec HR
            // You'll need to add a field to identify TimeTec HR salespeople
            if ($user->is_timetec_hr) {
                return $this->accessible_by_timetec_hr_salespeople;
            } else {
                return $this->accessible_by_non_timetec_hr_salespeople;
            }
        }

        return false;
    }
}
