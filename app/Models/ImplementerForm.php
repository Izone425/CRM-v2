<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImplementerForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'filepath',
        'notes',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
