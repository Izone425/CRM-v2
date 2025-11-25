<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaConversionsApiService
{
    private $accessToken;
    private $datasetId;
    private $apiVersion;

    public function __construct()
    {
        $this->accessToken = env('META_CONVERSIONS_ACCESS_TOKEN');
        $this->datasetId = env('META_CONVERSIONS_DATASET_ID', '1043374506092464');
        $this->apiVersion = env('META_CONVERSIONS_API_VERSION', 'v24.0');
    }

    /**
     * Send lead event to Meta Conversions API
     *
     * @param array $leadData
     * @param string|null $testEventCode Optional test event code
     * @return array
     */
    public function sendLeadEvent(array $leadData, ?string $testEventCode = null): array
    {
        try {
            $payload = $this->buildPayload($leadData, $testEventCode);

            Log::info('Sending event to Meta Conversions API', [
                'lead_id' => $leadData['id'] ?? null,
                'social_lead_id' => $leadData['social_lead_id'] ?? null,
                'payload' => $payload,
            ]);

            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->datasetId}/events";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'data' => $payload['data'],
                'access_token' => $this->accessToken,
                'test_event_code' => $testEventCode, // Only used for testing
            ]);

            $responseData = $response->json();

            if ($response->successful()) {
                Log::info('Meta Conversions API event sent successfully', [
                    'lead_id' => $leadData['id'] ?? null,
                    'social_lead_id' => $leadData['social_lead_id'] ?? null,
                    'response' => $responseData,
                ]);

                return [
                    'success' => true,
                    'response' => $responseData,
                ];
            } else {
                Log::error('Meta Conversions API request failed', [
                    'lead_id' => $leadData['id'] ?? null,
                    'social_lead_id' => $leadData['social_lead_id'] ?? null,
                    'status' => $response->status(),
                    'response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'error' => $responseData['error'] ?? 'Unknown error',
                    'status' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Meta Conversions API exception', [
                'lead_id' => $leadData['id'] ?? null,
                'social_lead_id' => $leadData['social_lead_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the payload for Meta Conversions API
     */
    private function buildPayload(array $leadData, ?string $testEventCode = null): array
    {
        $eventTime = now()->timestamp;

        // Hash customer information using SHA256
        $hashedEmail = !empty($leadData['email'])
            ? hash('sha256', strtolower(trim($leadData['email'])))
            : null;

        $hashedPhone = !empty($leadData['phone_number'])
            ? hash('sha256', preg_replace('/\D/', '', $leadData['phone_number']))
            : null;

        $userData = [];

        if ($hashedEmail) {
            $userData['em'] = [$hashedEmail];
        }

        if ($hashedPhone) {
            $userData['ph'] = [$hashedPhone];
        }

        // ✅ Use social_lead_id from utm_details (Meta's lead_id)
        if (!empty($leadData['social_lead_id'])) {
            $userData['lead_id'] = $leadData['social_lead_id'];

            Log::info('Meta lead_id added to payload', [
                'social_lead_id' => $leadData['social_lead_id'],
            ]);
        }

        // Add click_id (fbclid) if available
        if (!empty($leadData['fbclid'])) {
            $userData['fbp'] = $leadData['fbclid'];
        }

        // Add additional hashed data for better matching
        if (!empty($leadData['first_name'])) {
            $userData['fn'] = [hash('sha256', strtolower(trim($leadData['first_name'])))];
        }

        if (!empty($leadData['last_name'])) {
            $userData['ln'] = [hash('sha256', strtolower(trim($leadData['last_name'])))];
        }

        if (!empty($leadData['city'])) {
            $userData['ct'] = [hash('sha256', strtolower(trim($leadData['city'])))];
        }

        if (!empty($leadData['state'])) {
            $userData['st'] = [hash('sha256', strtolower(trim($leadData['state'])))];
        }

        if (!empty($leadData['zip'])) {
            $userData['zp'] = [hash('sha256', trim($leadData['zip']))];
        }

        if (!empty($leadData['country'])) {
            $userData['country'] = [hash('sha256', strtolower(trim($leadData['country'])))];
        }

        $payload = [
            'data' => [
                [
                    'event_name' => 'Lead', // Event name for Demo-Assigned
                    'event_time' => $eventTime,
                    'action_source' => 'system_generated',
                    'custom_data' => [
                        'event_source' => 'crm',
                        'lead_event_source' => 'TimeTec CRM', // Your CRM name
                    ],
                    'user_data' => $userData,
                ]
            ]
        ];

        // Add test event code if provided
        if ($testEventCode) {
            $payload['test_event_code'] = $testEventCode;
        }

        return $payload;
    }

    /**
     * Send test event
     */
    public function sendTestEvent(array $leadData): array
    {
        $testEventCode = 'TEST' . time();

        Log::info('Sending test event to Meta Conversions API', [
            'test_event_code' => $testEventCode,
            'social_lead_id' => $leadData['social_lead_id'] ?? null,
        ]);

        return $this->sendLeadEvent($leadData, $testEventCode);
    }
}
