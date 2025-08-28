<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearlyTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'salesperson',
        'target_amount',
    ];
}
