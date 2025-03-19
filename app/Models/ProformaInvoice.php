<?php

namespace App\Models;

use App\Enums\QuotationStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use App\Observers\QuotationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([QuotationObserver::class])]
class ProformaInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'amount',
        'inv_type',
        'remark',
        'salesperson',
    ];

    protected $guarded = ['id'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'id');
    }
}
