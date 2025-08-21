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
        'autocount_code',
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

    /**
     * Convert single path to array or vice versa for Filament compatibility
     */
    public function setPictureUrlAttribute($value)
    {
        if (is_array($value) && !empty($value)) {
            $this->attributes['picture_url'] = $value[0];
        } else {
            $this->attributes['picture_url'] = $value;
        }
    }

    /**
     * Get raw picture URL for form handling
     */
    public function getFormPictureUrlAttribute()
    {
        $value = $this->attributes['picture_url'] ?? null;
        return $value;
    }

    /**
     * Set the name attribute to uppercase
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtoupper($value);
    }

    /**
     * Set the device model to uppercase
     */
    public function setDeviceModelAttribute($value)
    {
        $this->attributes['device_model'] = strtoupper($value);
    }

    public function deviceModel()
    {
        return $this->belongsTo(DeviceModel::class, 'device_model', 'name');
    }

    /**
     * Set the autocount code to uppercase
     */
    public function setAutocountCodeAttribute($value)
    {
        $this->attributes['autocount_code'] = strtoupper($value);
    }
}
