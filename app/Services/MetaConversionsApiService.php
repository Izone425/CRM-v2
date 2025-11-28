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

            // ✅ Correct URL format with access_token as query parameter
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->datasetId}/events?access_token={$this->accessToken}";

            // ✅ Add test_event_code to URL if provided
            if ($testEventCode) {
                $url .= "&test_event_code={$testEventCode}";
            }

            Log::info('Meta API URL', [
                'url' => str_replace($this->accessToken, '[HIDDEN]', $url), // Hide token in logs
                'has_test_code' => !empty($testEventCode),
            ]);

            // ✅ Send only the data array in the body
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

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
     * Build payload for Meta Conversions API
     *
     * @param array $leadData
     * @param string|null $testEventCode
     * @return array
     */
    private function buildPayload(array $leadData, ?string $testEventCode = null): array
    {
        $userData = [];

        // ✅ Hash email with SHA256 if provided
        if (!empty($leadData['email'])) {
            $userData['em'] = [hash('sha256', strtolower(trim($leadData['email'])))];
        }

        // ✅ Hash phone with SHA256 if provided (remove spaces and special chars)
        if (!empty($leadData['phone_number'])) {
            $cleanPhone = preg_replace('/[^0-9+]/', '', $leadData['phone_number']);
            $userData['ph'] = [hash('sha256', $cleanPhone)];
        }

        // ✅ Hash first name with SHA256 if provided
        if (!empty($leadData['first_name'])) {
            $userData['fn'] = [hash('sha256', strtolower(trim($leadData['first_name'])))];
        }

        // ✅ Hash last name with SHA256 if provided
        if (!empty($leadData['last_name'])) {
            $userData['ln'] = [hash('sha256', strtolower(trim($leadData['last_name'])))];
        }

        // ✅ Hash city with SHA256 if provided
        if (!empty($leadData['city'])) {
            $userData['ct'] = [hash('sha256', strtolower(trim($leadData['city'])))];
        }

        // ✅ Hash state with SHA256 if provided
        if (!empty($leadData['state'])) {
            $userData['st'] = [hash('sha256', strtolower(trim($leadData['state'])))];
        }

        // ✅ Hash zip code with SHA256 if provided
        if (!empty($leadData['zip'])) {
            $userData['zp'] = [hash('sha256', trim($leadData['zip']))];
        }

        // ✅ Hash country with SHA256 if provided (use 2-letter country code)
        if (!empty($leadData['country'])) {
            $userData['country'] = [hash('sha256', strtolower(trim($leadData['country'])))];
        }

        // ✅ Add lead_id (Meta's original lead ID) - NOT HASHED
        if (!empty($leadData['social_lead_id'])) {
            $userData['lead_id'] = $leadData['social_lead_id'];
            Log::info('Meta lead_id added to payload', [
                'social_lead_id' => $leadData['social_lead_id']
            ]);
        }

        // ✅ Build the event payload
        $eventData = [
            'event_name' => 'Lead',
            'event_time' => time(),
            'action_source' => 'system_generated',
            'custom_data' => [
                'event_source' => 'crm',
                'lead_event_source' => 'TimeTec CRM',
            ],
            'user_data' => $userData,
        ];

        // ✅ Add fbclid if available
        if (!empty($leadData['fbclid'])) {
            $eventData['user_data']['fbc'] = $leadData['fbclid'];
        }

        return [
            'data' => [$eventData]
        ];
    }

    /**
     * Test dataset access
     */
    public function testDatasetAccess(): array
    {
        try {
            // ✅ Test endpoint to verify dataset access
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->datasetId}?access_token={$this->accessToken}";

            Log::info('Testing dataset access', [
                'dataset_id' => $this->datasetId,
                'api_version' => $this->apiVersion,
            ]);

            $response = Http::get($url);
            $responseData = $response->json();

            if ($response->successful()) {
                Log::info('Dataset access test successful', [
                    'dataset_id' => $this->datasetId,
                    'response' => $responseData,
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            } else {
                Log::error('Dataset access test failed', [
                    'dataset_id' => $this->datasetId,
                    'status' => $response->status(),
                    'response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'error' => $responseData,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Dataset access test exception', [
                'error' => $e->getMessage(),
                'dataset_id' => $this->datasetId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
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
