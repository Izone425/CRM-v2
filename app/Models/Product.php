<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'solution',
        'unit_price',
        'subscription_period',
        'package_group',
        'package_sort_order',
        'taxable',
        'editable',
        'minimum_price',
        'is_active',
        'sort_order',
        'is_commission',
        'push_to_autocount',
        'convert_pi',
        'push_so',
        'push_sw',
        'gl_posting',
        'tariff_code',
        'amount_editable',
    ];

    // ✅ Add this to cast package_group as array
    protected $casts = [
        'package_group' => 'array',
        'is_active' => 'boolean',
        'taxable' => 'boolean',
        'editable' => 'boolean',
        'amount_editable' => 'boolean',
        'minimum_price' => 'boolean',
        'convert_pi' => 'boolean',
        'push_to_autocount' => 'boolean',
        'push_so' => 'boolean',
        'push_sw' => 'boolean',
    ];

    public function scopeActive(Builder $query, bool $value=true): Builder
    {
        return $query->where('is_active', $value);
    }
}
