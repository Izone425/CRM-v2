<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePart extends Model
{
    use HasFactory;

    protected $table = 'spare_parts';

    protected $fillable = [
        'device_model',
        'name',
        'picture_url',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Generate the full URL for the part image
     */
    public function getPictureUrlAttribute($value)
    {
        if (empty($value)) {
            return url('images/no-image.jpg');
        }

        // If it's already a URL, return as is
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // If it starts with storage/, assume it's a public storage path
        if (str_starts_with($value, 'storage/')) {
            return url($value);
        }

        // Otherwise, assume it's in storage/app/public/
        return url('storage/' . $value);
    }

    /**
     * Get spare parts by device model
     */
    public static function getByDeviceModel(int $deviceModelId)
    {
        return self::where('device_model_id', $deviceModelId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
