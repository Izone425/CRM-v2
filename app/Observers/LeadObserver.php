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

            // ✅ Get social_lead_id from utm_details relationship first
            $socialLeadId = $lead->utmDetail->social_lead_id ?? null;

            // ✅ Only proceed if social_lead_id exists
            if (!$socialLeadId) {
                Log::info('Meta Conversions API: No social_lead_id found, skipping event', [
                    'lead_id' => $lead->id,
                    'lead_status' => $lead->lead_status,
                ]);
                return;
            }

            Log::info('Lead status changed to Demo-Assigned, sending to Meta', [
                'lead_id' => $lead->id,
                'lead_status' => $lead->lead_status,
                'social_lead_id' => $socialLeadId,
            ]);

            try {
                $metaService = new MetaConversionsApiService();
                $fbclid = $lead->utmDetail->fbclid ?? null;

                $leadData = [
                    'id' => $lead->id,
                    'email' => $lead->companyDetail->email ?? $lead->email,
                    'phone_number' => $lead->companyDetail->contact_no ?? $lead->phone,
                    'first_name' => $lead->companyDetail->name ?? $lead->name ?? null,
                    'last_name' => null, // Add if you have last name field
                    'city' => $lead->city ?? null,
                    'state' => $lead->state ?? null,
                    'zip' => $lead->zip ?? null,
                    'country' => $lead->country ?? null,
                    'social_lead_id' => $socialLeadId,
                    'fbclid' => $fbclid,
                ];

                Log::info('Preparing to send Meta event', [
                    'lead_id' => $lead->id,
                    'social_lead_id' => $socialLeadId,
                    'has_email' => !empty($leadData['email']),
                    'has_phone' => !empty($leadData['phone_number']),
                ]);

                $result = $metaService->sendLeadEvent($leadData);

                if ($result['success']) {
                    $lead->meta_event_sent_at = now();
                    $lead->saveQuietly(); // Save without triggering observer again

                    Log::info('Meta Conversions API: Demo-Assigned event sent successfully', [
                        'lead_id' => $lead->id,
                        'social_lead_id' => $socialLeadId,
                        'meta_event_sent_at' => $lead->meta_event_sent_at,
                    ]);
                } else {
                    Log::warning('Meta Conversions API: Failed to send Demo-Assigned event', [
                        'lead_id' => $lead->id,
                        'social_lead_id' => $socialLeadId,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Meta Conversions API: Exception during Demo-Assigned event', [
                    'lead_id' => $lead->id,
                    'social_lead_id' => $socialLeadId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
