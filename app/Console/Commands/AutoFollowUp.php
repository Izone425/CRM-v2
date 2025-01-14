<?php
namespace App\Console\Commands;

use App\Mail\FollowUpNotification;
use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\ActivityLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AutoFollowUp extends Command
{
    protected $signature = 'follow-up:auto';
    protected $description = 'Automatically follows up on leads every Tuesday 10am';

    public function handle()
    {
        info('Follow-up auto command in every Tuesday 10am executed at ' . now());

        // Begin a database transaction to ensure atomicity
        DB::transaction(function () {
            // Fetch leads that need follow-up and haven't reached the maximum count
            $leads = Lead::where('follow_up_needed', true)
                ->where('follow_up_count', '<', 4)
                ->get();

            foreach ($leads as $lead) {
                // Increment follow-up count and set the next follow-up date
                $lead->update([
                    'follow_up_count' => $lead->follow_up_count + 1,
                    'follow_up_date' => now()->addWeek(1),
                ]);

                if ($lead->lead_status === 'New' || $lead->lead_status === 'Under Review') {
                    // Count the follow-ups for 'Under Review' status
                    $followUpCount = ActivityLog::where('subject_id', $lead->id)
                        ->whereJsonContains('properties->attributes->lead_status', 'Under Review')
                        ->count();
                    $viewName = 'emails.email_blasting_1st';
                    $contentTemplateSid = 'HX2d4adbe7d011693a90af7a09c866100f'; // Your Content Template SID

                    // Define the follow-up description based on the count
                    $followUpDescription = ($followUpCount) . 'st Lead Owner Follow Up (Auto Follow Up Started)';
                    if ($followUpCount == 2) {
                        $viewName = 'emails.email_blasting_2nd';
                        $followUpDescription = '2nd Lead Owner Follow Up';
                        $contentTemplateSid = 'HX72acd0ab4ffec49493288f9c0b53a17a';
                    } elseif ($followUpCount == 3) {
                        $followUpDescription = '3rd Lead Owner Follow Up';
                        $viewName = 'emails.email_blasting_3rd';
                        $contentTemplateSid = 'HX9ed8a4589f03d9563e94d47c529aaa0a';
                    } elseif ($followUpCount >= 4) {
                        $followUpDescription = $followUpCount . 'th Lead Owner Follow Up';
                        $viewName = 'emails.email_blasting_4th';
                        $contentTemplateSid = 'HXa18012edd80d072d54b60b93765dd3af';
                    }

                    // Retrieve the latest activity log for the lead
                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    // Stop follow-ups if the counter reaches 4
                    if ($latestActivityLog && $lead->follow_up_count >= 4) {
                        $latestActivityLog->update([
                            'description' => '4th Lead Owner Follow Up (Auto Follow Up Stop)',
                            'causer_id' => 0
                        ]);
                        $lead->updateQuietly([
                            'follow_up_needed' => false,
                            'follow_up_count' => 1,
                        ]);
                    } else if ($latestActivityLog) {
                        $latestActivityLog
                        ->update([
                            'description' => $followUpDescription,
                            'causer_id' => 0
                        ]);
                    } else {
                        // Create a new activity log if none exists
                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($lead)
                            ->withProperties(['description' => $followUpDescription]);
                    }

                    $leadowner = User::where('name', $lead->lead_owner)->first();
                    try {
                        $emailContent = [
                            'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                            'lead' => [
                                'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
                                'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                                'companySize' => $lead->company_size ?? 'N/A', // Company Size
                                'phone' => $lead->phone ?? 'N/A', // Lead's Phone
                                'email' => $lead->email ?? 'N/A', // Lead's Email
                                'country' => $lead->country ?? 'N/A', // Lead's Country
                                'products' => $lead->products ?? 'N/A', // Products
                                'department' => $leadowner->department ?? 'N/A', // department
                                'companyName' => $lead->companyDetail->company_name ?? 'Unknown Company',
                                'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
                            ],
                        ];

                        Mail::mailer('secondary')->to($lead->email)
                            ->send(new FollowUpNotification($emailContent, $viewName));
                    } catch (\Exception $e) {
                        // Handle email sending failure
                        Log::error("Error: {$e->getMessage()}");
                    }

                    $phoneNumber = $lead->phone; // Recipient's WhatsApp number
                    $variables = [$lead->name, $lead->lead_owner];
                    // $contentTemplateSid = 'HX6de8cec52e6c245826a67456a3ea3144'; // Your Content Template SID

                    $whatsappController = new \App\Http\Controllers\WhatsAppController();
                    $response = $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);

                    return $response;
                } else {
                    // Handle case where lead is canceled
                    $cancelfollowUpCount = ActivityLog::where('subject_id', $lead->id)
                        ->whereJsonContains('properties->attributes->lead_status', 'Demo Cancelled')
                        ->count();

                    $cancelFollowUpDescription = ($cancelfollowUpCount) . 'st Demo Cancelled Follow Up';
                    if ($cancelfollowUpCount == 2) {
                        $cancelFollowUpDescription = '2nd Demo Cancelled Follow Up';
                    } elseif ($cancelfollowUpCount == 3) {
                        $cancelFollowUpDescription = '3rd Demo Cancelled Follow Up';
                    } elseif ($cancelfollowUpCount >= 4) {
                        $cancelFollowUpDescription = $cancelfollowUpCount . 'th Demo Cancelled Follow Up';
                    }

                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($latestActivityLog && $lead->follow_up_count >= 4) {
                        $latestActivityLog->update([
                            'description' => 'Demo Cancelled. ' . $cancelfollowUpCount . 'th Demo Cancelled Follow Up (Auto Follow Up Stop)',
                        ]);
                        $lead->updateQuietly([
                            'follow_up_needed' => false,
                            'follow_up_count' => 1,
                        ]);
                    } else if ($latestActivityLog) {
                        $latestActivityLog->update([
                            'description' => 'Demo Cancelled. ' . $cancelFollowUpDescription,
                        ]);
                    } else {
                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($lead)
                            ->withProperties(['description' => $cancelFollowUpDescription]);
                    }
                }
            }
        });
    }
}
