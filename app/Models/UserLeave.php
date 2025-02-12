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

    public static function getUserLeavesByDateRange($userID,$startDate,$endDate){
        $temp = UserLeave::where("user_ID","=",$userID)->whereBetween('date',[$startDate,$endDate])->get()->toArray();
        $newArray = [];
        foreach($temp as &$row){
            $newArray[$row['day_of_week']]=$row;
        }
        return $newArray;
    }

    public static function getWeeklyLeavesByDateRange($startDate,$endDate){
        $temp = UserLeave::join('users','users.id','=','users_leave.user_ID')->select('users.name','users_leave.*')->whereBetween('date',[$startDate,$endDate])->get()->toArray();
        foreach($temp as &$row){
            $row['salespersonAvatar'] = "https://ui-avatars.com/api" . '?' .  http_build_query(["name" => $row['name'], "background" => "random"]);
        }
        return $temp;
    }

}
