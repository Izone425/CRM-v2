<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'warranty_category',
        'serial_number_required',
        'is_active',
    ];

    protected $casts = [
        'serial_number_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function spareParts()
    {
        return $this->hasMany(SparePart::class, 'device_model', 'name');
    }
}
