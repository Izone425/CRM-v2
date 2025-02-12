<?php

namespace App\Console\Commands;

use App\Models\PublicHoliday;
use App\Services\LeaveAPIService;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
    protected $description = 'Update Public Holiday From TimeTec WebService';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        PublicHoliday::truncate();

        $dateFrom = $this->argument('dateFrom');
        $dateTo = $this->argument('dateTo');
        $wsdl = "https://api.timeteccloud.com/webservice/WebServiceTimeTecAPI.asmx?WSDL";
        $LeaveAPIService = new LeaveAPIService($wsdl, "hr@timeteccloud.com", "BAKIt9nKbCxr6JJUvLWySQL4oH7a4zJYhIjv4GIJK5CD9RvlLp");
        $params = ["CompanyID" => 351, "DivisionID" => 31010, "DateFrom" => $dateFrom, "DateTo" => $dateTo, "RecordStartFrom" => "0", "LimitRecordShow" => "100"];

        $holidays = json_decode($LeaveAPIService->getClient()->getOrgStructureHoliday($params)->GetOrgStructureHolidayResult, true);
        
        if (isset($holidays['Result']['OrgStructureHolidayObj']) && !empty($holidays['Result']['OrgStructureHolidayObj'][0])) {
            $holidays = $holidays['Result']['OrgStructureHolidayObj'][0]["TimeTec Cloud Sdn. Bhd."];
            foreach ($holidays as &$row) {
                PublicHoliday::create([
                    'day_of_week' => Carbon::parse($row['Date'])->dayOfWeekIso,
                    'date'=> $row['Date'],
                    'name'=>$row['Holiday'] 
                ]);
            }
        }
    }
}
