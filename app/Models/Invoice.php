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
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'amount',
        'invoice_no',
        'invoice_date',
        'salesperson',
        'sales_admin',
        'doc_status',
        'doc_key',
        'doc_type',
        'currency_code',
        'exchange_rate',
        'invoice_status',
        'last_modified_at',
        'invoice_status',
    ];

    protected $guarded = ['id'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(InvoiceDetail::class, 'doc_key', 'doc_key');
    }
}
