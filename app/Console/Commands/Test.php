<?php

namespace App\Console\Commands;

use App\Services\LeaveAPIService;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $wsdl = "https://api.timeteccloud.com/webservice/WebServiceTimeTecAPI.asmx?WSDL";
        $LeaveAPIService = new LeaveAPIService($wsdl, "hr@timeteccloud.com", "BAKIt9nKbCxr6JJUvLWySQL4oH7a4zJYhIjv4GIJK5CD9RvlLp");
        $params = ["CompanyID" => 351, "CheckTimeFrom" => "2025-01-15", "CheckTimeTo" => "2025-02-10"];

        $leave = json_decode($LeaveAPIService->getClient()->GetApprovedPendingLeaves($params)->UserLeaveObj,true);
        $this->info('Starting the command...');
    }
}
