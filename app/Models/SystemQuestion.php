<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class SystemQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'modules',
        'existing_system',
        'usage_duration',
        'expired_date',
        'reason_for_change',
        'staff_count',
        'subsidiaries',
        'branches',
        'industry',
        'hrdf_contribution',
        'causer_name',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'id');
    }
}
