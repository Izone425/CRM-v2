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
    ];

    public function scopeActive(Builder $query, bool $value=true): Builder
    {
        return $query->where('is_active', $value);
    }
}
