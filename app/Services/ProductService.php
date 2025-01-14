<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

use App\Models\Product;

class ProductService
{
    public function getCode(Product $product): array
    {
        return $product->get()->pluck('code','code')->toArray();
    }

    public function filterByCode(Builder $query, array $data): Builder
    {
        return $query->where('code', 'like', '%' . $data['code'] . '%');
    }
}
