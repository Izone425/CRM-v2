<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingDeviceModel extends Model
{
    use HasFactory;

    protected $fillable = ['model_name', 'description', 'is_active'];

    /**
     * Get the purchase items associated with this model.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(DevicePurchaseItem::class, 'model', 'model_name');
    }
}
