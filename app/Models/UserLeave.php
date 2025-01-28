<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLeave extends Model
{
    use HasFactory;

     // Disable timestamps
    public $timestamps = false;
    protected $table = 'users_leave';
    protected $fillable = [
        'user_ID',
        'leave_type',
        'date',
        'day_of_week',
    ];

    public function getUserLeavesByDateRange($userID,$startDate,$endDate){
        $temp = UserLeave::where("user_ID","=",$userID)->whereBetween('date',[$startDate,$endDate])->get()->toArray();
        foreach($temp as &$row){
            $temp[$row['day_of_week']]=$row;
        }

        return $temp;
    }
}
