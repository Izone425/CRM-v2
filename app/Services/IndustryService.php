<?php

namespace App\Services;

use App\Models\Industry;
use Illuminate\Database\Eloquent\Builder;

class IndustryService
{
    public function getList(): array
    {
        return Industry::active()->get()->pluck('name','name')->toArray();
    }

    public function filterByName(Builder $query, array $data): Builder
    {
        return $query->where('name','like', '%' . $data['name'] . '%');
    }
}
