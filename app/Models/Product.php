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
        'taxable',
        'is_active',
        'sort_order'
    ];

    public function scopeActive(Builder $query, bool $value=true): Builder
    {
        return $query->where('is_active', $value);
    }
}
