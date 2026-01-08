<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        return UserLeave::where("user_ID",$userID)->where("date",$date)->where('status', 'Approved')->get()->toArray();
    }

    public static function getUserLeavesByDateRange($userId, $startDate, $endDate)
    {
        $leaves = [];
        $leaveRecords = self::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['Approved', 'Pending'])
            ->get();

        // Group leaves by date
        $leavesByDate = $leaveRecords->groupBy('date');

        foreach ($leavesByDate as $date => $dayLeaves) {
            $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeekIso; // 1 = Monday, 5 = Friday

            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Only weekdays
                $hasAmLeave = $dayLeaves->where('session', 'am')->count() > 0;
                $hasPmLeave = $dayLeaves->where('session', 'pm')->count() > 0;
                $hasFullLeave = $dayLeaves->where('session', 'full')->count() > 0;

                // Determine the effective leave type
                if ($hasFullLeave || ($hasAmLeave && $hasPmLeave)) {
                    $effectiveSession = 'full';
                    $leaveType = $hasFullLeave ?
                        $dayLeaves->where('session', 'full')->first()->leave_type :
                        'Multiple (' . $dayLeaves->where('session', 'am')->first()->leave_type . ' AM, ' .
                        $dayLeaves->where('session', 'pm')->first()->leave_type . ' PM)';
                    $status = $hasFullLeave ?
                        $dayLeaves->where('session', 'full')->first()->status :
                        'Mixed';
                } elseif ($hasAmLeave) {
                    $effectiveSession = 'am';
                    $amLeave = $dayLeaves->where('session', 'am')->first();
                    $leaveType = $amLeave->leave_type;
                    $status = $amLeave->status;
                } elseif ($hasPmLeave) {
                    $effectiveSession = 'pm';
                    $pmLeave = $dayLeaves->where('session', 'pm')->first();
                    $leaveType = $pmLeave->leave_type;
                    $status = $pmLeave->status;
                }

                $leaves[$dayOfWeek] = [
                    'session' => $effectiveSession,
                    'leave_type' => $leaveType,
                    'status' => $status,
                    'date' => $date,
                    'raw_leaves' => $dayLeaves // Keep original records for reference
                ];
            }
        }

        return $leaves;
    }

    public static function getWeeklyLeavesByDateRange($startDate,$endDate, array $selectedSalesPeople = null){
        $temp = UserLeave::with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'Approved')
            ->whereHas('user', function ($query) {
                $query->where('role_id', 2); // Filter only users with role_id = 9
            })
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

    public static function getTechnicianWeeklyLeavesByDateRange($startDate, $endDate, array $selectedTechnicians = null)
    {
        $temp = UserLeave::with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereHas('user', function ($query) {
                $query->where('role_id', 9); // Filter only users with role_id = 9
            })
            ->when(!empty($selectedTechnicians), function ($query) use ($selectedTechnicians) {
                return $query->whereIn('user_ID', $selectedTechnicians);
            })
            ->get();

        foreach ($temp as &$row) {
            $row->technicianAvatar = $row->user?->getFilamentAvatarUrl() ?? asset('storage/uploads/photos/default-avatar.png');
            $row->technicianName = $row->user?->name ?? 'Unknown';
        }

        return $temp->toArray();
    }

    public static function getImplementerWeeklyLeavesByDateRange($startDate, $endDate, array $selectedImplementers = null)
    {
        $temp = UserLeave::with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereHas('user', function ($query) {
                $query->whereIn('role_id', [4, 5]); // Filter only implementer roles (4 and 5)
            })
            ->when(!empty($selectedImplementers), function ($query) use ($selectedImplementers) {
                return $query->whereIn('user_ID', $selectedImplementers);
            })
            ->get();

        // Format results to match expected structure
        $implementerLeaves = [];
        foreach ($temp as $leave) {
            if (!$leave->user) continue; // Skip if user relationship is null

            // Process avatar path consistently with other methods
            $avatarPath = $leave->user->avatar_path;
            $avatarUrl = null;

            if ($avatarPath) {
                if (str_starts_with($avatarPath, 'storage/')) {
                    $avatarUrl = asset($avatarPath);
                } elseif (str_starts_with($avatarPath, 'uploads/')) {
                    $avatarUrl = asset('storage/' . $avatarPath);
                } else {
                    $avatarUrl = Storage::url($avatarPath);
                }
            } else {
                $avatarUrl = $leave->user->getFilamentAvatarUrl() ?? asset('storage/uploads/photos/default-avatar.png');
            }

            // Create the day-indexed array structure
            $dayOfWeek = Carbon::parse($leave->date)->dayOfWeek;
            if ($dayOfWeek == 0) $dayOfWeek = 7; // Convert Sunday (0) to 7 if needed

            // Skip weekends if calendar only shows weekdays
            if ($dayOfWeek > 5) {
                continue;
            }

            // Structure data like other methods but with implementer-specific fields
            $implementerLeaves[] = [
                'implementerId' => $leave->user_ID,
                'implementerName' => $leave->user->name,
                'implementerAvatar' => $avatarUrl,
                'leave_type' => $leave->leave_type,
                'status' => $leave->status,
                'date' => $leave->date,
                'day_of_week' => $dayOfWeek,
                'session' => $leave->session ?? 'full', // Default to full day if not specified
            ];
        }

        // Restructure to match the day_of_week indexed format used in other methods
        $newArray = [];
        foreach ($implementerLeaves as $leave) {
            // Combine implementer ID with day to create unique keys for each implementer's leave on each day
            $key = $leave['implementerId'] . '_' . $leave['day_of_week'];
            $newArray[$key] = $leave;

            // Also index by just day_of_week for compatibility with existing code
            if (!isset($newArray[$leave['day_of_week']])) {
                $newArray[$leave['day_of_week']] = $leave;
            }
        }

        return $newArray;
    }

    public static function getAllLeavesForDateRange($startDate, $endDate, $employeeIds = [])
    {
        $query = self::whereBetween('date', [$startDate, $endDate]);

        if (!empty($employeeIds)) {
            $query->whereIn('user_ID', $employeeIds);
        }

        $leaves = $query->get();

        // Transform the leaves into a format suitable for the calendar
        $formattedLeaves = collect();

        foreach ($leaves as $leave) {
            $key = $leave->user_ID;

            if (!$formattedLeaves->has($key)) {
                $formattedLeaves[$key] = collect();
            }

            // Check if there's already a leave entry for this date
            if ($formattedLeaves[$key]->has($leave->date)) {
                // If there's already an entry for this date, we need to handle multiple sessions
                $existingLeave = $formattedLeaves[$key][$leave->date];

                // If the existing session is different from the current one, we have an AM+PM combination
                if ($existingLeave['session'] !== $leave->session) {
                    // Create a combined entry indicating both AM and PM
                    $formattedLeaves[$key][$leave->date] = [
                        'session' => 'am_plus_pm', // Special indicator for AM+PM combination
                        'leave_type' => $existingLeave['leave_type'] . ' + ' . $leave->leave_type,
                        'status' => 'Mixed',
                        'sessions' => [$existingLeave['session'], $leave->session], // Track individual sessions
                        'leave_types' => [$existingLeave['leave_type'], $leave->leave_type] // Track individual types
                    ];
                }
            } else {
                // First leave entry for this date
                $formattedLeaves[$key][$leave->date] = [
                    'session' => $leave->session,
                    'leave_type' => $leave->leave_type,
                    'status' => $leave->status
                ];
            }
        }

        return $formattedLeaves;
    }
}
