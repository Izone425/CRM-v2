<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'public_holiday';
    protected $fillable = [
        'day_of_week',
        'date',
        'name' 
    ];

    public static function getPublicHoliday($startDate,$endDate){
        return self::whereBetween('date',[$startDate,$endDate])->get();
    }
}
