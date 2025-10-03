<?php
// filepath: /var/www/html/timeteccrm/app/Console/Commands/ProcessHrdfEmails.php

namespace App\Console\Commands;

use App\Services\HrdfEmailParser;
use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;

class ProcessHrdfEmails extends Command
{
    protected $signature = 'hrdf:process-emails';
    protected $description = 'Process HRDF approval emails and update claims';

    protected $hrdfParser;

    public function __construct(HrdfEmailParser $hrdfParser)
    {
        parent::__construct();
        $this->hrdfParser = $hrdfParser;
    }

    public function handle()
    {
        $this->info('Starting HRDF email processing...');

        try {
            // Connect to email server
            $client = Client::account('hrdf'); // Configure in config/imap.php
            $client->connect();

            // Get INBOX folder
            $folder = $client->getFolder('INBOX');

            // Search for unread HRDF approval emails
            $messages = $folder->search()
                ->unseen()
                ->from('noreply@notifications.hrdcorp.gov.my')
                ->subject('SBL-Khas Approved.')
                ->get();

            $processedCount = 0;
            $errorCount = 0;

            foreach ($messages as $message) {
                $this->info("Processing email: {$message->getSubject()}");

                $result = $this->hrdfParser->parseApprovalEmail(
                    $message->getHTMLBody() ?: $message->getTextBody(),
                    $message->getSubject(),
                    $message->getFrom()[0]->full,
                    $message->getDate()
                );

                if ($result && $result['success']) {
                    $this->info("✓ {$result['message']} (ID: {$result['claim_id']})");
                    $processedCount++;

                    // Mark as read
                    $message->setFlag('Seen');
                } else {
                    $this->error("✗ Failed to process email");
                    $errorCount++;
                }
            }

            $this->info("Processing complete: {$processedCount} processed, {$errorCount} errors");

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
