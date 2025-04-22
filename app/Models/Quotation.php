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
class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'headcount',
        'quotation_date',
        'quotation_reference_no',
        'pi_reference_no',
        'quotation_type',
        'sales_person_id',
        'currency',
        'sales_type',
        'hrdf_status',
        'subscription_period',
        'status',
        'tax_rate',
        'email_sent_at',
        'confirmation_order_document',
    ];

    protected $casts = [
        'quotation_date' => 'datetime:j M Y',
        'status' => QuotationStatusEnum::class,
    ];

    protected $guarded = ['id'];

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logOnly([
    //             'lead_id',
    //             'headcount',
    //             'quotation_date',
    //             'quotation_reference_no',
    //             'pi_reference_no',
    //             'quotation_type',
    //             'sales_person_id',
    //             'currency',
    //             'subscription_period',
    //             'status',
    //             'tax_rate',
    //             'email_sent_at',
    //             'confirmation_order_document',
    //         ]);
    // }
    // public function company(): BelongsTo
    // {
    //     return $this->belongsTo(Company::class);
    // }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'id');
    }

    public function sales_person(): BelongsTo
    {
        return $this->belongsTo(User::class,'sales_person_id','id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationDetail::class);
    }
}
