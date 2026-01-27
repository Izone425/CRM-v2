<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CRMApiService
{
    private string $apiUrl;
    private string $apiKey;
    private string $privateKeyPath;
    private $privateKey;

    public function __construct()
    {
        $this->apiUrl = config('services.crm.api_url') ?? 'https://profile-crm-hr-test.timeteccloud.com';
        $this->apiKey = config('services.crm.api_key') ?? 'crm_external_api';

        $configPath = config('services.crm.private_key_path', 'storage/keys/crm_client.private.pem');

        if (strpos($configPath, '/') !== 0) {
            $this->privateKeyPath = base_path($configPath);
        } else {
            $this->privateKeyPath = $configPath;
        }

        if (empty($this->apiUrl)) {
            throw new \Exception("CRM API URL is not configured");
        }

        if (empty($this->apiKey)) {
            throw new \Exception("CRM API Key is not configured");
        }

        $this->loadPrivateKey();
    }

    private function loadPrivateKey(): void
    {
        if (!file_exists($this->privateKeyPath)) {
            throw new \Exception("Private key not found at: {$this->privateKeyPath}");
        }

        $keyContent = file_get_contents($this->privateKeyPath);

        if (empty($keyContent)) {
            throw new \Exception("Private key file is empty");
        }

        $this->privateKey = openssl_pkey_get_private($keyContent);

        if (!$this->privateKey) {
            throw new \Exception("Failed to load private key: " . openssl_error_string());
        }

        Log::info("CRM API: Private key loaded successfully");
    }

    private function createSignature(string $payload, string $timestamp): string
    {
        $dataToSign = $payload . $timestamp;

        $signature = '';
        $success = openssl_sign(
            $dataToSign,
            $signature,
            $this->privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$success) {
            throw new \Exception("Failed to create signature: " . openssl_error_string());
        }

        return base64_encode($signature);
    }

    private function getTimestamp(): string
    {
        // ISO8601 format like Node.js: 2024-10-30T10:30:45.123Z
        return gmdate('Y-m-d\TH:i:s.v\Z');
    }

    private function makeRequest(string $method, string $endpoint, ?array $data = null): array
    {
        $payload = $data ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $timestamp = $this->getTimestamp();
        $signature = $this->createSignature($payload, $timestamp);

        $url = $this->apiUrl . $endpoint;

        // ✅ More detailed logging
        Log::info("CRM API Request Details", [
            'method' => $method,
            'url' => $url,
            'timestamp' => $timestamp,
            'payload' => $payload,
            'payload_length' => strlen($payload),
            'signature' => $signature,
            'signature_length' => strlen($signature),
            'api_key' => $this->apiKey,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'X-Signature' => substr($signature, 0, 50) . '...',
                'X-Timestamp' => $timestamp,
                'Content-Type' => 'application/json',
            ]
        ]);

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'X-Signature' => $signature,
                'X-Timestamp' => $timestamp,
                'Content-Type' => 'application/json',
            ])
            ->withBody($payload, 'application/json')
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->send($method, $url);

            $statusCode = $response->status();
            $responseBody = $response->body();

            // ✅ Log full response
            Log::info("CRM API Response", [
                'status' => $statusCode,
                'body' => $responseBody,
                'headers' => $response->headers(),
            ]);

            if ($response->successful()) {
                Log::info("CRM API Success", [
                    'endpoint' => $endpoint,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error("CRM API Error", [
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'body' => $responseBody,
                'json' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? $responseBody,
                'status' => $statusCode
            ];

        } catch (\Exception $e) {
            Log::error("CRM API Exception", [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create new account in CRM
     * POST /api/crm/account
     *
     * Matches the Node.js sample structure exactly
     */
    public function createAccount(array $data): array
    {
        // Validate required fields
        $required = ['company_name', 'country_id', 'name', 'email', 'password', 'phone_code', 'phone', 'timezone'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return [
                    'success' => false,
                    'error' => "Missing required field: $field"
                ];
            }
        }

        // Validate timezone
        if (!$this->isValidIANATimezone($data['timezone'])) {
            return [
                'success' => false,
                'error' => "Invalid timezone: {$data['timezone']}. Must be IANA format like 'Asia/Kuala_Lumpur'"
            ];
        }

        // Build payload matching Node.js sample exactly
        $payload = [
            'companyName' => $data['company_name'],
            'countryId' => (int)$data['country_id'],
            'name' => $data['name'],  // ✅ THIS WAS MISSING!
            'email' => $data['email'],
            'password' => $data['password'],
            'phoneCode' => $data['phone_code'],
            'phone' => $data['phone'],
            'timezone' => $data['timezone'],
        ];

        Log::info("CRM API: Creating account", $payload);

        return $this->makeRequest('POST', '/api/crm/account', $payload);
    }

    private function isValidIANATimezone(string $timezone): bool
    {
        $validTimezones = timezone_identifiers_list();
        return in_array($timezone, $validTimezones);
    }

    public function __destruct()
    {
        if ($this->privateKey) {
            openssl_free_key($this->privateKey);
        }
    }

    /**
     * Add buffer license
     */
    public function addBufferLicense(int $accountId, int $companyId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/buffer";
        return $this->makeRequest('POST', $endpoint, $licenseData);
    }

    /**
     * Update buffer license
     */
    public function updateBufferLicense(int $accountId, int $companyId, int $licenseSetId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/buffer/{$licenseSetId}";
        return $this->makeRequest('PUT', $endpoint, $licenseData);
    }

    /**
     * Add paid application license
     */
    public function addPaidApplicationLicense(int $accountId, int $companyId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/paid-app";
        return $this->makeRequest('POST', $endpoint, $licenseData);
    }

    /**
     * Update paid application license
     */
    public function updatePaidApplicationLicense(int $accountId, int $companyId, int $periodId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/paid-app/{$periodId}";
        return $this->makeRequest('PUT', $endpoint, $licenseData);
    }

    /**
     * Get company invoices from TimeTec Backend
     * GET /api/crm/account/{accountId}/company/{companyId}/invoices
     *
     * @param int $accountId - hr_account_id from SoftwareHandover
     * @param int $companyId - hr_company_id from SoftwareHandover
     * @param array $params - Optional query parameters (search, page, limit)
     * @return array
     */
    public function getCompanyInvoices(int $accountId, int $companyId, array $params = []): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/invoices";

        if (!empty($params)) {
            $queryString = http_build_query($params);
            $endpoint .= "?{$queryString}";
        }

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get Proforma Invoice details from TimeTec Backend
     * GET /api/crm/account/{accountId}/company/{companyId}/proforma-invoice/{invoiceNo}
     *
     * @param int $accountId - hr_account_id from SoftwareHandover
     * @param int $companyId - hr_company_id from SoftwareHandover
     * @param string $invoiceNo - Invoice number (e.g., TT2512000122)
     * @return array
     */
    public function getProformaInvoiceDetails(int $accountId, int $companyId, string $invoiceNo): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/proforma-invoice/{$invoiceNo}";
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get company licenses from TimeTec Backend
     * GET /api/crm/account/{accountId}/company/{companyId}/licenses
     *
     * @param int $accountId - hr_account_id from SoftwareHandover
     * @param int $companyId - hr_company_id from SoftwareHandover
     * @return array
     */
    public function getCompanyLicenses(int $accountId, int $companyId): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses";
        return $this->makeRequest('GET', $endpoint);
    }
}
