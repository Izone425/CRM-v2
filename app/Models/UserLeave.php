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
        'status',
        'start_time',
        'end_time',
        'session'
    ];

        public function user()
    {
        // Adjust 'user_ID' if your foreign key is named differently
        return $this->belongsTo(User::class, 'user_ID');
    }

    public static function getUserLeavesByDateRange($userID,$startDate,$endDate){
        $temp = UserLeave::where("user_ID","=",$userID)->whereBetween('date',[$startDate,$endDate])->get()->toArray();
        $newArray = [];
        foreach($temp as &$row){
            $newArray[$row['day_of_week']]=$row;
        }
        return $newArray;
    }

    public static function getWeeklyLeavesByDateRange($startDate,$endDate){
        $temp = UserLeave::with('user')
        ->whereBetween('date', [$startDate, $endDate])
        ->get();

        foreach($temp as &$row){
            $row['salespersonAvatar'] = $row->user->getFilamentAvatarUrl();
        }
        return $temp;
    }

}
