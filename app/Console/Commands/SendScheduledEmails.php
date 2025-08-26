<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Mail\Message;

class SendScheduledEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails that have been scheduled for delivery today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $this->info("Starting scheduled email processing at {$now}");

        // Get today's scheduled emails with "New" status
        $scheduledEmails = DB::table('scheduled_emails')
            ->whereDate('scheduled_date', $today)
            ->where('status', 'New')
            ->get();

        $count = $scheduledEmails->count();
        $this->info("Found {$count} emails scheduled for today");

        foreach ($scheduledEmails as $email) {
            try {
                $emailData = json_decode($email->email_data, true);

                if (!$emailData) {
                    $this->error("Could not parse email data for ID {$email->id}");
                    continue;
                }

                // Send the email
                Mail::html($emailData['content'], function (Message $message) use ($emailData) {
                    $message->to($emailData['recipients'])
                        ->bcc($emailData['sender_email'])
                        ->subject($emailData['subject'])
                        ->from($emailData['sender_email'], $emailData['sender_name']);
                });

                // Mark the email as sent
                DB::table('scheduled_emails')
                    ->where('id', $email->id)
                    ->update([
                        'status' => 'Done',
                        'sent_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                $this->info("Email ID {$email->id} sent successfully");

                // Optional: Add logging for tracking
                Log::info('Scheduled email sent', [
                    'email_id' => $email->id,
                    'recipients' => $emailData['recipients'],
                    'subject' => $emailData['subject'],
                ]);

                // Sleep briefly to prevent flooding the mail server
                usleep(500000); // 0.5 seconds

            } catch (\Exception $e) {
                $this->error("Failed to send email ID {$email->id}: {$e->getMessage()}");
                Log::error('Failed to send scheduled email', [
                    'email_id' => $email->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info('Email scheduling task completed');
        return 0;
    }
}
