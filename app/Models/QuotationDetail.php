<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'description',
        'quantity',
        'subscription_period',
        'unit_price',
        'discount',
        'taxation',
        'total_before_tax',
        'total_after_tax',
        'sort_order',
        'tax_code',
        'convert_pi',
        'year',
        'tariff_code',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
