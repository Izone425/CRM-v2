<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserLeave;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateUserLeave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userleave:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all employee leave from TimeTec HR API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDate = Carbon::now();
        $employees = $this->getAllEmployees();

        // Create a lookup array for faster user matching
        $userLookup = $employees->keyBy('api_user_id');

        //$i is the number of months forward to keep in DB
        for ($i = 0; $i < 12; $i++) {
            $dateFrom = $currentDate->copy()->startOfMonth()->format('Y-m-d');
            $dateTo = $currentDate->copy()->endOfMonth()->format('Y-m-d');

            try {
                // Get authentication token
                $authResponse = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post('https://hr-api.timeteccloud.com/api/auth-mobile/token', [
                    'username' => 'hr@timeteccloud.com',
                    'password' => 'Abc123456'
                ]);

                if (!$authResponse->successful()) {
                    $this->error('Authentication failed for month ' . $currentDate->format('Y-m'));
                    $currentDate->addMonth(1);
                    continue;
                }

                $authData = $authResponse->json();
                $token = $authData['accessToken'] ?? null;

                if (!$token) {
                    $this->error('Token not found for month ' . $currentDate->format('Y-m'));
                    $currentDate->addMonth(1);
                    continue;
                }

                // Get calendar data
                $calendarResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->get('https://hr-api.timeteccloud.com/api/v1/mobile-calendar/crm-calendar-list', [
                    'startDate' => $dateFrom,
                    'endDate' => $dateTo
                ]);

                if (!$calendarResponse->successful()) {
                    $this->error('Calendar API failed for month ' . $currentDate->format('Y-m'));
                    $currentDate->addMonth(1);
                    continue;
                }

                $calendarData = $calendarResponse->json();
                $calendarList = $calendarData['calendarListView'] ?? [];

                $leaveCount = 0;
                $updateCount = 0;
                foreach ($calendarList as $day) {
                    // Process leaves for this day
                    foreach ($day['calendarDetailListing'] as $leaveRecord) {
                        $employeeUserId = (string)$leaveRecord['employeeUserId'];
                        $user = $userLookup->get($employeeUserId);

                        if (!$user) {
                            // Skip if user not found in our system
                            continue;
                        }

                        // Determine session based on partialAmPm or requestType
                        $session = 'full'; // default
                        if (!empty($leaveRecord['partialAmPm'])) {
                            $session = strtolower($leaveRecord['partialAmPm']);
                        } elseif ($leaveRecord['requestType'] === 'Partial') {
                            // If partial but no AM/PM specified, try to determine from times
                            if (!empty($leaveRecord['startTime']) && !empty($leaveRecord['endTime'])) {
                                $endTime = Carbon::parse($leaveRecord['endTime'])->hour;
                                $session = $endTime < 14 ? 'am' : 'pm';
                            }
                        }

                        // Check if leave record already exists
                        $existingLeave = UserLeave::where('user_ID', $user->id)
                            ->where('date', $leaveRecord['date'])
                            ->where('leave_type', $leaveRecord['leaveType'])
                            ->where('session', $session)
                            ->first();

                        $leaveData = [
                            'user_ID' => $user->id,
                            'leave_type' => $leaveRecord['leaveType'],
                            'date' => $leaveRecord['date'],
                            'day_of_week' => Carbon::parse($leaveRecord['date'])->dayOfWeekIso,
                            'status' => $leaveRecord['status'],
                            'session' => $session,
                            'start_time' => $session === 'full' ? '00:00:00' : ($leaveRecord['startTime'] ?: null),
                            'end_time' => $session === 'full' ? '00:00:00' : ($leaveRecord['endTime'] ?: null),
                        ];

                        if ($existingLeave) {
                            // Update existing leave record (especially status)
                            $existingLeave->update($leaveData);
                            $updateCount++;
                        } else {
                            // Create new leave record
                            UserLeave::create($leaveData);
                            $leaveCount++;
                        }
                    }
                }

                $this->info("Processed {$leaveCount} new leaves and updated {$updateCount} existing leaves for {$currentDate->format('Y-m')}");

            } catch (\Exception $e) {
                $this->error('Error processing month ' . $currentDate->format('Y-m') . ': ' . $e->getMessage());
            }

            $currentDate->addMonth(1);
        }

        $this->info('User leave update completed successfully.');
        return 0;
    }

    private function getAllEmployees()
    {
        return User::whereIn('role_id', ['1', '2', '3', '4', '5', '6', '8', '9'])
            ->whereNotNull('api_user_id')
            ->select('id', 'name', 'api_user_id')
            ->get();
    }
}
