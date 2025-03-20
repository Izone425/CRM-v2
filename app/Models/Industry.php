<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Industry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active'
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(CompanyDetail::class);
    }

    public function scopeActive(Builder $query, bool $value=true): Builder
    {
        return $query->where('is_active', $value);
    }
}
