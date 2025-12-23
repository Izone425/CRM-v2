<?php

namespace App\Console\Commands;

use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdatePublicHoliday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publicholiday:update {dateFrom} {dateTo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Public Holiday From TimeTec HR API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        PublicHoliday::truncate();

        $dateFrom = $this->argument('dateFrom');
        $dateTo = $this->argument('dateTo');

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
                $this->error('Authentication failed: ' . $authResponse->body());
                return 1;
            }

            $authData = $authResponse->json();
            $token = $authData['accessToken'] ?? null;

            if (!$token) {
                $this->error('Token not found in auth response');
                return 1;
            }

            // Get calendar data (include userIds even though we only need holidays)
            $calendarResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->get('https://hr-api.timeteccloud.com/api/v1/mobile-calendar/crm-calendar-list', [
                'userIds' => '342,348,251', // Include some user IDs as the API seems to require this parameter
                'startDate' => $dateFrom,
                'endDate' => $dateTo
            ]);

            if (!$calendarResponse->successful()) {
                $this->error('Calendar API failed: ' . $calendarResponse->body());
                return 1;
            }

            $calendarData = $calendarResponse->json();
            $calendarList = $calendarData['calendarListView'] ?? [];

            $holidayCount = 0;
            foreach ($calendarList as $day) {
                // Only process days with holidays
                if (!empty($day['holidayName'])) {
                    PublicHoliday::create([
                        'day_of_week' => Carbon::parse($day['date'])->dayOfWeekIso,
                        'date' => $day['date'],
                        'name' => $day['holidayName']
                    ]);
                    $holidayCount++;
                }
            }

            $this->info("Successfully updated {$holidayCount} public holidays from {$dateFrom} to {$dateTo}");
            return 0;

        } catch (\Exception $e) {
            $this->error('Error updating public holidays: ' . $e->getMessage());
            return 1;
        }
    }
}
