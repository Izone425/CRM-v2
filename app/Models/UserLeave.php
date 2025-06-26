<?php

namespace App\Models;

use Carbon\Carbon;
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

    public static function getWeeklyLeavesByDateRange($startDate, $endDate, $userIds = [])
    {
        $query = self::whereBetween('date', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        // Join with users table to only get technicians
        $query->join('users', 'users_leave.user_id', '=', 'users.id')
              ->where('users.role_id', 9);

        $leaves = $query->select('users_leave.*')->get();

        $result = [];
        foreach ($leaves as $leave) {
            // Get user information including avatar
            $user = User::find($leave->user_id);
            if ($user && $user->role_id == 9) {
                // Format the date to get the day of the week (1 = Monday, 5 = Friday)
                $date = Carbon::parse($leave->date);
                $dayOfWeek = $date->dayOfWeekIso;

                if ($dayOfWeek <= 5) { // Only include weekdays (1-5)
                    $result[] = [
                        'id' => $leave->id,
                        'user_id' => $leave->user_id,
                        'technicianName' => $user->name,
                        'technicianAvatar' => app()->make('App\Livewire\TechnicianCalendar')->getAvatarUrl($user->avatar_path),
                        'date' => $leave->date,
                        'day_of_week' => $dayOfWeek,
                        'leave_type' => $leave->leave_type,
                        'session' => $leave->session,
                        'status' => $leave->status
                    ];
                }
            }
        }

        return $result;
    }
}
