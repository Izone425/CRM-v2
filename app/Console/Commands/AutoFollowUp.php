<?php
namespace App\Console\Commands;

use App\Mail\FollowUpNotification;
use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\ActivityLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\TemplateSelector;

class AutoFollowUp extends Command
{
    protected $signature = 'follow-up:auto';
    protected $description = 'Automatically follows up on leads every Tuesday 10am';

    public function handle()
    {
        DB::transaction(function () {
            $leads = Lead::where('follow_up_needed', true)
                ->where('follow_up_count', '<', 4)
                ->get();

            $counter = 0;

            foreach ($leads as $lead) {
                // Rate limit: sleep after every 5 leads
                if ($counter > 0 && $counter % 5 === 0) {
                    usleep(1_000_000); // Sleep for 1 second (1,000,000 microseconds)
                }

                $counter++;

                $lead->update([
                    'follow_up_count' => $lead->follow_up_count + 1,
                    'follow_up_date' => now()->next('Tuesday'),
                ]);

                if ($lead->lead_status === 'New' || $lead->lead_status === 'Under Review') {
                    $followUpCount = $lead->follow_up_count;
                    $templateSelector = new TemplateSelector();

                    if ($lead->lead_code && (
                        str_contains($lead->lead_code, '(CN)') ||
                        str_contains($lead->lead_code, 'CN')
                    )) {
                        // Use CN templates
                        $template = $templateSelector->getTemplateByLeadSource('CN', $followUpCount);
                    } else {
                        // Use regular templates based on UTM campaign
                        $template = $templateSelector->getTemplate($lead->utmDetail->utm_campaign ?? null, $followUpCount);
                    }

                    $viewName = $template['email'];
                    $contentTemplateSid = $template['sid'];

                    $followUpDescription = match ($followUpCount) {
                        1 => '1st Automation Follow Up',
                        2 => '2nd Automation Follow Up',
                        3 => '3rd Automation Follow Up',
                        default => 'Final Automation Follow Up',
                    };

                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($latestActivityLog && $lead->follow_up_count >= 4) {
                        $latestActivityLog->update([
                            'description' => 'Final Automation Follow Up',
                            'causer_id' => 0
                        ]);

                        $lead->updateQuietly([
                            'follow_up_needed' => false,
                            'follow_up_count' => 1,
                            'categories' => 'Inactive',
                            'stage' => null,
                            'lead_status' => 'No Response'
                        ]);
                    } else if ($latestActivityLog) {
                        $latestActivityLog->update([
                            'description' => $followUpDescription,
                            'causer_id' => 0
                        ]);
                    } else {
                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($lead)
                            ->withProperties(['description' => $followUpDescription]);
                    }

                    // Email
                    try {
                        $leadowner = User::where('name', $lead->lead_owner)->first();
                        $emailContent = [
                            'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                            'leadOwnerEmail' => $leadowner->email ?? 'Unknown Email',
                            'lead' => [
                                'lastName' => $lead->name ?? 'N/A',
                                'company' => $lead->companyDetail->company_name ?? 'N/A',
                                'companySize' => $lead->company_size ?? 'N/A',
                                'phone' => $lead->phone ?? 'N/A',
                                'email' => $lead->email ?? 'N/A',
                                'country' => $lead->country ?? 'N/A',
                                'products' => $lead->products ?? 'N/A',
                                'position' => $leadowner->position ?? 'N/A',
                                'companyName' => $lead->companyDetail->company_name ?? 'Unknown Company',
                                'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                            ],
                        ];
                        Mail::to($lead->companyDetail->email ?? $lead->email)
                            ->send(new FollowUpNotification($emailContent, $viewName));
                    } catch (Exception $e) {
                        Log::error("Email Error: {$e->getMessage()}");
                    }

                    // WhatsApp
                    try {
                        $phoneNumber = $lead->companyDetail->contact_no ?? $lead->phone;

                        $isChinese = $lead->lead_code && (
                            str_contains($lead->lead_code, '(CN)') ||
                            str_contains($lead->lead_code, 'CN')
                        );

                        // Set variables based on language
                        if ($isChinese) {
                            $variables = [$lead->name];
                        } else {
                            // Regular templates need both lead name and lead owner
                            $variables = [$lead->name, $lead->lead_owner];
                        }

                        $whatsappController = new \App\Http\Controllers\WhatsAppController();
                        $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);
                    } catch (Exception $e) {
                        Log::error("WhatsApp Error: {$e->getMessage()}");
                    }
                }
                info("Processing follow-up for Lead ID: {$lead->id}, Follow-Up Count: {$lead->follow_up_count}");
            }
        });
    }
}
