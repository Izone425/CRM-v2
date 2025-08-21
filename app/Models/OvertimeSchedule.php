<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'weekend_date',
        'user_id',
        'status',
        'notes'
    ];

    protected $casts = [
        'weekend_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper function to get the Sunday date
    public function getSundayDateAttribute()
    {
        return $this->weekend_date->copy()->addDay();
    }
}
