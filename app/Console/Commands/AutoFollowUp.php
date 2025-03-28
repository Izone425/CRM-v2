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

class AutoFollowUp extends Command
{
    protected $signature = 'follow-up:auto';
    protected $description = 'Automatically follows up on leads every Tuesday 10am';

    public function handle()
    {
        DB::transaction(function () {
            // Fetch leads that need follow-up
            $leads = Lead::where('follow_up_needed', true)
                ->where('follow_up_count', '<', 4)
                ->get();

            foreach ($leads as $lead) {
                info("Processing follow-up for Lead ID: {$lead->id}, Name: {$lead->name}, Follow-Up Count: {$lead->follow_up_count}");
                $lead->update([
                    'follow_up_count' => $lead->follow_up_count + 1,
                    'follow_up_date' => now()->next('Tuesday'),
                ]);

                if ($lead->lead_status === 'New' || $lead->lead_status === 'Under Review') {
                    $followUpCount = $lead->follow_up_count;
                    $viewName = 'emails.email_blasting_1st';
                    $contentTemplateSid = 'HX50fdd31004919fd43e647ebfb934d608';

                    $followUpDescription = "{$followUpCount}st Automation Follow Up";
                    if ($followUpCount == 2) {
                        $viewName = 'emails.email_blasting_2nd';
                        $followUpDescription = '2nd Automation Follow Up';
                        $contentTemplateSid = 'HXee59098cc1d267094875b84ceed0dc09';
                    } elseif ($followUpCount == 3) {
                        $viewName = 'emails.email_blasting_3rd';
                        $followUpDescription = '3rd Automation Follow Up';
                        $contentTemplateSid = 'HXddbbe2f375b1ad34e9cd6f9e35fa62f0';
                    } elseif ($followUpCount >= 4) {
                        $viewName = 'emails.email_blasting_4th';
                        $followUpDescription = 'Final Automation Follow Up';
                        $contentTemplateSid = 'HX17778b5cec4858f24535bdbc69eebd8a';
                    }

                    // Retrieve latest activity log
                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    // Stop follow-ups if limit is reached
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

                    $leadowner = User::where('name', $lead->lead_owner)->first();
                    try {
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

                    // ✅ Send WhatsApp message
                    try {
                        $phoneNumber = $lead->companyDetail->contact_no ?? $lead->phone; // Recipient's WhatsApp number
                        $variables = [$lead->name, $lead->lead_owner];
                        // $contentTemplateSid = 'HX6de8cec52e6c245826a67456a3ea3144'; // Your Content Template SID

                        // $whatsappController = new \App\Http\Controllers\WhatsAppController();
                        // $response = $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);

                        // return $response;
                    } catch (Exception $e) {
                        Log::error("WhatsApp Error: {$e->getMessage()}");
                    }
                }
            }
        });
    }
}
