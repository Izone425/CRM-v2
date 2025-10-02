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

                // Get CC recipients (if stored in email data)
                $ccRecipients = $emailData['cc_recipients'] ?? [];

                // Get attachments data
                $attachmentsData = $emailData['attachments_data'] ?? [];

                // Validate that attachment files still exist
                $validAttachments = [];
                foreach ($attachmentsData as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $validAttachments[] = $attachment;
                    } else {
                        $this->warn("Attachment file not found: " . ($attachment['path'] ?? 'Unknown path'));
                        Log::warning("Scheduled email attachment not found", [
                            'email_id' => $email->id,
                            'attachment_path' => $attachment['path'] ?? 'Unknown',
                            'quotation_id' => $attachment['quotation_id'] ?? 'Unknown'
                        ]);
                    }
                }

                // Send the email with attachments
                Mail::html($emailData['content'], function (Message $message) use ($emailData, $ccRecipients, $validAttachments) {
                    $message->to($emailData['recipients'])
                        ->subject($emailData['subject'])
                        ->from($emailData['sender_email'], $emailData['sender_name']);

                    // Add CC recipients if we have any
                    if (!empty($ccRecipients)) {
                        $message->cc($ccRecipients);
                    }

                    // BCC the sender
                    $message->bcc($emailData['sender_email']);

                    // Add PDF attachments
                    foreach ($validAttachments as $attachment) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'],
                            'mime' => $attachment['mime']
                        ]);
                    }
                });

                // Mark the email as sent
                DB::table('scheduled_emails')
                    ->where('id', $email->id)
                    ->update([
                        'status' => 'Done',
                        'sent_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                $this->info("Email ID {$email->id} sent successfully with " . count($validAttachments) . " attachment(s)");

                // Log successful send
                Log::info('Scheduled email sent', [
                    'email_id' => $email->id,
                    'recipients' => $emailData['recipients'],
                    'cc_recipients' => $ccRecipients,
                    'subject' => $emailData['subject'],
                    'attachments_count' => count($validAttachments),
                    'template' => $emailData['template_name'] ?? 'Unknown',
                ]);

                // Sleep briefly to prevent flooding the mail server
                usleep(500000); // 0.5 seconds

            } catch (\Exception $e) {
                $this->error("Failed to send email ID {$email->id}: {$e->getMessage()}");

                // Mark as failed
                DB::table('scheduled_emails')
                    ->where('id', $email->id)
                    ->update([
                        'status' => 'Failed',
                        'error_message' => $e->getMessage(),
                        'updated_at' => Carbon::now(),
                    ]);

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
