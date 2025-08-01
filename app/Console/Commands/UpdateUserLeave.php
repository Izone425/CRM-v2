<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserLeave;
use App\Services\LeaveAPIService;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
    protected $description = 'Update all salesperson leave. eg. 2025-01-01 2026-01-01';

    /**
     * Execute the console command.
     */
    /*public function handle()
    {
        UserLeave::truncate();

        $dateFrom = $this->argument('dateFrom');
        $dateTo = $this->argument('dateTo');
        $salesPeople = $this->getAllSalesPeople();
        foreach($salesPeople as $salesPerson){
            $wsdl = "https://api.timeteccloud.com/webservice/WebServiceTimeTecAPI.asmx?WSDL";
            $LeaveAPIService = new LeaveAPIService($wsdl, "hr@timeteccloud.com", "BAKIt9nKbCxr6JJUvLWySQL4oH7a4zJYhIjv4GIJK5CD9RvlLp");
            $params = ["CompanyID" => 351, "UserID" => $salesPerson->api_user_id, "CheckTimeFrom" => $dateFrom, "CheckTimeTo" => $dateTo, "RecordStartFrom" => "0", "LimitRecordShow" => "100"];

            $leave = json_decode($LeaveAPIService->getClient()->getUserLeave($params)->GetUserLeaveResult,true);

            if(!empty($leave['Result']['UserLeaveObj'])){
                foreach($leave['Result']['UserLeaveObj'] as $row){
                    UserLeave::create([
                        'user_ID' => $salesPerson->id,
                        'leave_type' => $row['LeaveType'],
                        'date' => $row['Date'],
                        'day_of_week' => Carbon::parse($row['Date'])->dayOfWeekIso,
                    ]); ;
                }

            }
        }
    }*/ //OLD

    public function handle(){
        //Clear Current User leave
        UserLeave::truncate();

        $currentDate = Carbon::now()->startOfYear();

        //
        $salesPeople = $this->getAllSalesPeople()->toArray();

        //$i is the number of months forward to keep in DB
        for ($i=0; $i < 12; $i++) {

            $dateFrom = $currentDate->copy()->startOfMonth();
            $dateTo = $currentDate->copy()->endOfMonth();
            $wsdl = "https://api.timeteccloud.com/webservice/WebServiceTimeTecAPI.asmx?WSDL";
            $LeaveAPIService = new LeaveAPIService($wsdl, "hr@timeteccloud.com", "BAKIt9nKbCxr6JJUvLWySQL4oH7a4zJYhIjv4GIJK5CD9RvlLp");
            $params = ["CompanyID" => 351, "DateFrom" => $dateFrom, "DateTo" => $dateTo];
            $leave = json_decode($LeaveAPIService->getClient()->GetApprovedPendingLeaves($params)->GetApprovedPendingLeavesResult,true)['Result']['UserLeaveObj'];

            array_map(function ($row) use($salesPeople) {
                    foreach($salesPeople as $salesPerson){
                        if($salesPerson['api_user_id'] == $row['User_ID']) {

                            if(empty($row['StartTime']) || empty($row['EndTime'])){
                                $session = "full";
                            }
                            else {
                                $convertedEndTime = Carbon::parse($row["EndTime"])->hour;
                                if($convertedEndTime < 14){
                                    $session = "am";
                                }
                                else
                                    $session = "pm";
                            }

                            UserLeave::create([
                                'user_ID' => $salesPerson['id'],
                                'leave_type' => $row['LeaveType'],
                                'date' => $row['Date'],
                                'day_of_week' => Carbon::parse($row['Date'])->dayOfWeekIso,
                                'status'=> $row['Status'],
                                'session'=> $session,
                                'start_time'=> $row['StartTime'] ?? null,
                                'end_time'=> $row['EndTime'] ?? null,
                            ]);
                        }
                    }
            }, $leave);

            $currentDate->addMonth(1);
        }

    }

    private function getAllSalesPeople()
    {
        return User::whereIn('role_id', ['1', '2', '3', '4', '5', '6', '8', '9'])
            ->select('id', 'name','api_user_id')
            ->get();
    }
}
