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

    public static function getUserLeavesByDate($userID,$date){
        return UserLeave::where("user_ID",$userID)->where("date",$date)->get()->toArray();
    }

    public static function getUserLeavesByDateRange($userID,$startDate,$endDate){
        $temp = UserLeave::where("user_ID","=",$userID)->whereBetween('date',[$startDate,$endDate])->get()->toArray();
        $newArray = [];
        foreach($temp as &$row){
            $newArray[$row['day_of_week']]=$row;
        }
        return $newArray;
    }

    public static function getWeeklyLeavesByDateRange($startDate,$endDate, array $selectedSalesPeople = null){
        $temp = UserLeave::with('user')
        ->whereBetween('date', [$startDate, $endDate])
        ->when(!empty($selectedSalesPeople), function ($query) use ($selectedSalesPeople) {
            return $query->whereIn('user_ID', $selectedSalesPeople);
        })
        ->get();

        foreach($temp as &$row){
            $row->salespersonAvatar = $row->user->getFilamentAvatarUrl();
            $row->salespersonName = $row->user->name;
        }
        return $temp->toArray();
    }

}
