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
    protected $signature = 'userleave:update {dateFrom} {dateTo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all salesperson leave. eg. 2025-01-01 2026-01-01';

    /**
     * Execute the console command.
     */
    public function handle()
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
    }

    private function getAllSalesPeople()
    {
        return User::where('role_id', '2')
            ->select('id', 'name','api_user_id')
            ->get();
    }
}
