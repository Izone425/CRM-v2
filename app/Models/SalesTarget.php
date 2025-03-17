<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'salesperson',
        'year',
        'month',
        'target_amount',
    ];

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'id');
    }
}
