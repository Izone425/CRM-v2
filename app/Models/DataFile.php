<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'filename',
        'category',
        'subcategory',
        'uploaded_by',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
