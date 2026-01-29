<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerV2Commission extends Model
{
    use HasFactory;

    protected $table = 'reseller_v2_commissions';

    protected $fillable = [
        'reseller_v2_id',
        'commission_rate',
    ];

    protected $casts = [
        'commission_rate' => 'integer',
    ];

    public function resellerV2(): BelongsTo
    {
        return $this->belongsTo(ResellerV2::class, 'reseller_v2_id');
    }
}
