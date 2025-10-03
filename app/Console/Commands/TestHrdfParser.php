<?php
// filepath: /var/www/html/timeteccrm/app/Console/Commands/TestHrdfParser.php

namespace App\Console\Commands;

use App\Services\HrdfEmailParser;
use Illuminate\Console\Command;

class TestHrdfParser extends Command
{
    protected $signature = 'hrdf:test-parser';
    protected $description = 'Test HRDF email parser with sample data';

    public function handle()
    {
        $parser = new HrdfEmailParser();

        // Sample email content from your example
        $emailContent = '
HAI KAH LANG SDN BHD

PROGRAMME NAME: TIMETEC HR - OPERATIONAL MODULES
DATE OF PROGRAM: From : 07/10/2025 To : 09/10/2025
APPLICATION NUMBER: 1435132X_25_0004
TOTAL AMOUNT (RM) : 3,087.72
Approved Date : 02/10/2025
        ';

        $result = $parser->parseApprovalEmail(
            $emailContent,
            'SBL-Khas Approved.',
            'HRDCorp NoReply <noreply@notifications.hrdcorp.gov.my>',
            now()
        );

        if ($result) {
            $this->info('✓ Test successful!');
            $this->info('Result: ' . json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->error('✗ Test failed!');
        }
    }
}
