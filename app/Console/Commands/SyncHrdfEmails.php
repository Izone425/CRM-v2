<?php

namespace App\Console\Commands;

use App\Models\HrdfClaim;
use App\Models\HRDFHandover;
use Illuminate\Console\Command;
use App\Models\HrdfMail;
use App\Models\Lead;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncHrdfEmails extends Command
{
    protected $signature = 'hrdf:sync-emails {--days=7 : Number of days to look back for emails}';
    protected $description = 'Sync HRDF emails from Microsoft Graph API to database';

    public function handle()
    {
        $this->info('Starting HRDF email sync...');

        try {
            $accessToken = MicrosoftGraphService::getAccessToken();

            if (!$accessToken) {
                $this->error('Failed to get access token');
                return 1;
            }

            $mailboxEmail = 'hrcrmmailbox@timeteccloud.com';
            $apiUrl = "https://graph.microsoft.com/v1.0/users/{$mailboxEmail}/messages";
            $days = $this->option('days');

            $this->info("Checking emails from last {$days} days...");

            // Get recent emails
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($apiUrl, [
                '$top' => 100,
                '$filter' => "receivedDateTime ge " . now()->subDays($days)->toISOString(),
                '$orderby' => 'receivedDateTime desc',
                '$select' => 'id,subject,from,receivedDateTime'
            ]);

            if ($response->failed()) {
                $this->error('Failed to retrieve emails: ' . $response->status());
                Log::error('HRDF Email Sync Failed', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return 1;
            }

            $emailData = $response->json();
            $totalEmails = count($emailData['value'] ?? []);
            $newEmails = 0;
            $skippedEmails = 0;
            $errors = [];

            $this->info("Found {$totalEmails} emails to process...");

            // Create progress bar
            $bar = $this->output->createProgressBar($totalEmails);
            $bar->start();

            foreach ($emailData['value'] ?? [] as $email) {
                $messageId = $email['id'];

                try {
                    // Check if email already exists
                    if (HrdfMail::where('message_id', $messageId)->exists()) {
                        $skippedEmails++;
                        $bar->advance();
                        continue;
                    }

                    // Get full email content
                    $fullEmailResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ])->get("https://graph.microsoft.com/v1.0/users/{$mailboxEmail}/messages/{$messageId}");

                    if ($fullEmailResponse->successful()) {
                        $fullEmail = $fullEmailResponse->json();

                        // Process and store email
                        $this->storeEmail($fullEmail, $messageId);
                        $newEmails++;

                        // Add small delay to avoid rate limiting
                        usleep(100000); // 0.1 second delay
                    } else {
                        $errors[] = "Failed to fetch email {$messageId}: " . $fullEmailResponse->status();
                    }

                } catch (\Exception $e) {
                    $errors[] = "Error processing email {$messageId}: " . $e->getMessage();
                    Log::error('Error processing HRDF email', [
                        'message_id' => $messageId,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Summary
            $this->info("Email sync completed!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total emails found', $totalEmails],
                    ['New emails stored', $newEmails],
                    ['Emails skipped (already exists)', $skippedEmails],
                    ['Errors', count($errors)],
                ]
            );

            if (!empty($errors)) {
                $this->error('Errors encountered:');
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
            }

            // Log the sync results
            Log::info('HRDF Email Sync Completed', [
                'total_found' => $totalEmails,
                'new_emails' => $newEmails,
                'skipped' => $skippedEmails,
                'errors' => count($errors)
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('HRDF Email Sync Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function storeEmail($fullEmail, $messageId)
    {
        $bodyContent = $fullEmail['body']['content'] ?? '';
        $bodyType = $fullEmail['body']['contentType'] ?? 'text';

        // Clean HTML content
        $cleanBodyContent = $this->cleanHtmlContent($bodyContent);

        // Prepare basic email data for storage (only fillable fields)
        $emailData = [
            'message_id' => $messageId,
            'subject' => $fullEmail['subject'] ?? '',
            'from_email' => $fullEmail['from']['emailAddress']['address'] ?? '',
            'from_name' => $fullEmail['from']['emailAddress']['name'] ?? '',
            'to_recipients' => collect($fullEmail['toRecipients'] ?? [])->map(function($recipient) {
                return [
                    'name' => $recipient['emailAddress']['name'] ?? '',
                    'address' => $recipient['emailAddress']['address'] ?? ''
                ];
            })->toArray(),
            'cc_recipients' => collect($fullEmail['ccRecipients'] ?? [])->map(function($recipient) {
                return [
                    'name' => $recipient['emailAddress']['name'] ?? '',
                    'address' => $recipient['emailAddress']['address'] ?? ''
                ];
            })->toArray(),
            'bcc_recipients' => collect($fullEmail['bccRecipients'] ?? [])->map(function($recipient) {
                return [
                    'name' => $recipient['emailAddress']['name'] ?? '',
                    'address' => $recipient['emailAddress']['address'] ?? ''
                ];
            })->toArray(),
            'received_date' => $fullEmail['receivedDateTime'] ? Carbon::parse($fullEmail['receivedDateTime']) : null,
            'sent_date' => $fullEmail['sentDateTime'] ? Carbon::parse($fullEmail['sentDateTime']) : null,
            'body_preview' => $fullEmail['bodyPreview'] ?? '',
            'body_content' => $cleanBodyContent,
            'body_type' => $bodyType,
            'has_attachments' => $fullEmail['hasAttachments'] ?? false,
            'importance' => $fullEmail['importance'] ?? 'normal',
            'is_read' => $fullEmail['isRead'] ?? false,
            'raw_email_data' => $fullEmail,
        ];

        $subject = $fullEmail['subject'] ?? '';

        // Check for different types of HRDF emails
        if (stripos($subject, 'SBL-Khas Approved') !== false) {
            // Grant approval email - create claim and handover
            $hrdCorpData = HrdfMail::parseHrdfData($cleanBodyContent);
            $emailData = array_merge($emailData, $hrdCorpData);
            $emailData['status'] = 'pending';

            Log::info('HRDF Grant Approved Email Found', [
                'message_id' => $messageId,
                'subject' => $subject,
                'from_email' => $fullEmail['from']['emailAddress']['address'] ?? '',
            ]);

        } elseif (stripos($subject, 'Claim Approval for Claim Ref') !== false) {
            // Claim approval email - update existing claim status
            $emailData['status'] = 'claim_approved';

            Log::info('HRDF Claim Approval Email Found', [
                'message_id' => $messageId,
                'subject' => $subject,
                'from_email' => $fullEmail['from']['emailAddress']['address'] ?? '',
            ]);

        } else {
            $emailData['status'] = 'processed';
        }

        // Create the email record
        $hrdfMail = HrdfMail::create($emailData);

        // Process different email types
        if ($hrdfMail->status === 'pending') {
            // Handle grant approval emails (existing logic)
            try {
                $claim = $hrdfMail->createClaimTracker();

                Log::info('HRDF Claim Created', [
                    'message_id' => $messageId,
                    'hrdf_mail_id' => $hrdfMail->id,
                    'claim_id' => $claim->id,
                    'subject' => $subject,
                    'extracted_data' => [
                        'company_name' => $claim->company_name,
                        'hrdf_grant_id' => $claim->hrdf_grant_id,
                        'programme_name' => $claim->programme_name,
                        'hrdf_training_date' => $claim->hrdf_training_date,
                        'invoice_amount' => $claim->invoice_amount
                    ]
                ]);

                $this->line("  └─ Created HRDF claim for: {$claim->company_name} (Grant: {$claim->hrdf_grant_id})");

                // Create HRDF handover record
                try {
                    $hrdfHandover = $this->createHrdfHandover($claim, $hrdfMail);

                    if ($hrdfHandover) {
                        $this->line("  └─ Created HRDF handover for: {$claim->company_name} (ID: {$hrdfHandover->id})");
                    }
                } catch (\Exception $e) {
                    $this->error("  └─ Failed to create HRDF handover: " . $e->getMessage());
                }

            } catch (\Exception $e) {
                $this->error("  └─ Failed to create HRDF claim: " . $e->getMessage());
            }

        } elseif ($hrdfMail->status === 'claim_approved') {
            // Handle claim approval emails
            try {
                $this->processClaimApprovalEmail($hrdfMail, $subject);
            } catch (\Exception $e) {
                $this->error("  └─ Failed to process claim approval: " . $e->getMessage());

                Log::error('HRDF Claim Approval Processing Failed', [
                    'message_id' => $messageId,
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $hrdfMail;
    }

    /**
     * Process claim approval emails and update HRDF claim status
     */
    private function processClaimApprovalEmail($hrdfMail, $subject)
    {
        // Extract claim reference from subject
        // Example: "Claim Approval for Claim Ref: C832542SBL_25_2335927"
        if (preg_match('/Claim Approval for Claim Ref:\s*([A-Z0-9_]+)/i', $subject, $matches)) {
            $claimRef = trim($matches[1]);

            Log::info('Extracted Claim Reference', [
                'subject' => $subject,
                'claim_ref' => $claimRef,
                'hrdf_mail_id' => $hrdfMail->id
            ]);

            // Find HRDF handover with matching hrdf_claim_id
            $hrdfHandover = \App\Models\HRDFHandover::where('hrdf_claim_id', $claimRef)->first();

            if ($hrdfHandover) {
                // Update the related HRDF claim status through the relationship
                if ($hrdfHandover->hrdfClaim) {
                    $hrdfHandover->hrdfClaim->update([
                        'claim_status' => 'APPROVED'
                    ]);

                    Log::info('HRDF Claim Status Updated to APPROVED', [
                        'claim_ref' => $claimRef,
                        'handover_id' => $hrdfHandover->id,
                        'claim_id' => $hrdfHandover->hrdfClaim->id,
                        'hrdf_grant_id' => $hrdfHandover->hrdf_grant_id,
                        'company_name' => $hrdfHandover->hrdfClaim->company_name
                    ]);

                    $this->line("  └─ Updated claim status to APPROVED for: {$claimRef}");
                    $this->line("      Company: {$hrdfHandover->hrdfClaim->company_name}");

                } else {
                    Log::warning('No HRDF Claim relationship found for handover', [
                        'claim_ref' => $claimRef,
                        'handover_id' => $hrdfHandover->id,
                        'hrdf_grant_id' => $hrdfHandover->hrdf_grant_id
                    ]);

                    $this->line("  └─ Warning: No HRDF claim relationship found for handover {$hrdfHandover->id}");
                }

            } else {
                Log::warning('No HRDF Handover found with matching claim ID', [
                    'claim_ref' => $claimRef,
                    'subject' => $subject
                ]);

                $this->line("  └─ Warning: No HRDF handover found with claim ID: {$claimRef}");
            }

        } else {
            Log::warning('Could not extract claim reference from subject', [
                'subject' => $subject,
                'hrdf_mail_id' => $hrdfMail->id
            ]);

            $this->line("  └─ Warning: Could not extract claim reference from subject");
        }
    }

    public function scopeHrdcorpEmails($query)
    {
        return $query->where('subject', 'like', '%SBL-Khas Approved%');
    }

    /**
     * Clean HTML content and convert to plain text
     */
    private function cleanHtmlContent($htmlContent)
    {
        if (empty($htmlContent)) {
            return '';
        }

        // First, convert &nbsp; to regular spaces
        $htmlContent = str_replace('&nbsp;', '', $htmlContent);

        // Convert other HTML entities
        $htmlContent = html_entity_decode($htmlContent, ENT_QUOTES, 'UTF-8');

        // Add line breaks before closing tags to preserve structure
        $htmlContent = preg_replace('/<\/(div|p|br|tr|td|th|li|h[1-6])>/i', "\n", $htmlContent);
        $htmlContent = preg_replace('/<(br|hr)\s*\/?>/i', "\n", $htmlContent);

        // Add line breaks after common block elements
        $htmlContent = preg_replace('/<(div|p|tr|li|h[1-6])[^>]*>/i', "\n", $htmlContent);

        // Remove all HTML tags
        $cleanContent = strip_tags($htmlContent);

        // Convert multiple spaces to single space (but preserve line breaks)
        $cleanContent = preg_replace('/[ \t]+/', ' ', $cleanContent);

        // Split into lines for processing
        $lines = explode("\n", $cleanContent);

        // Clean each line but keep structure
        $cleanLines = [];
        foreach ($lines as $line) {
            $cleanLines[] = trim($line);
        }

        // Join lines with single line breaks
        $cleanContent = implode("\n", $cleanLines);

        // Clean up multiple line breaks (max 2 consecutive)
        $cleanContent = preg_replace('/\n{3,}/', "\n\n", $cleanContent);

        return trim($cleanContent);
    }
}
