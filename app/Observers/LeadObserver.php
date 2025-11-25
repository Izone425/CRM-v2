<?php
namespace App\Observers;

use App\Models\Lead;
use App\Services\MetaConversionsApiService;
use Illuminate\Support\Facades\Log;

class LeadObserver
{
    /**
     * Handle the Lead "updated" event.
     */
    public function updated(Lead $lead)
    {
        // Check if lead_status changed to "Demo-Assigned"
        if ($lead->isDirty('lead_status') && $lead->lead_status === 'Demo-Assigned') {

            Log::info('Lead status changed to Demo-Assigned, sending to Meta', [
                'lead_id' => $lead->id,
                'lead_status' => $lead->lead_status,
            ]);

            try {
                // ✅ Get social_lead_id from utm_details
                $socialLeadId = null;
                if ($lead->utm_details) {
                    $utmDetails = is_string($lead->utm_details)
                        ? json_decode($lead->utm_details, true)
                        : $lead->utm_details;

                    $socialLeadId = $utmDetails['social_lead_id'] ?? null;
                }

                $metaService = new MetaConversionsApiService();

                $leadData = [
                    'id' => $lead->id,
                    'email' => $lead->email,
                    'phone_number' => $lead->phone_number,
                    'first_name' => $lead->first_name,
                    'last_name' => $lead->last_name,
                    'city' => $lead->city,
                    'state' => $lead->state,
                    'zip' => $lead->zip,
                    'country' => $lead->country,
                    'social_lead_id' => $socialLeadId, // ✅ Use from utm_details
                    'fbclid' => $lead->fbclid,
                ];

                Log::info('Preparing to send Meta event', [
                    'lead_id' => $lead->id,
                    'social_lead_id' => $socialLeadId,
                    'has_email' => !empty($lead->email),
                    'has_phone' => !empty($lead->phone_number),
                ]);

                $result = $metaService->sendLeadEvent($leadData);

                if ($result['success']) {
                    $lead->meta_event_sent_at = now();
                    $lead->saveQuietly(); // Save without triggering observer again

                    Log::info('Meta event sent and timestamp saved', [
                        'lead_id' => $lead->id,
                        'meta_event_sent_at' => $lead->meta_event_sent_at,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Failed to send Meta Conversions API event', [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
