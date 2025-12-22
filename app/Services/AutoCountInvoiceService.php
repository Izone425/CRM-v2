<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AutoCountInvoiceService
{
    private $apiUrl;
    private $username;
    private $encryptionKey;
    private $aesKey;
    private $aesIv;

    public function __construct()
    {
        $this->apiUrl = env('AUTOCOUNT_API_URL', 'http://crmwebapi.timeteccloud.com');
        $this->username = env('AUTOCOUNT_API_USERNAME', 'admin');
        $this->encryptionKey = 'HjuJ7WVa402L';

        // AES encryption parameters
        $this->aesKey = env('AUTOCOUNT_AES_KEY', 'CX4LXhzRO6AamhElngNEJETfn3UE6xMF'); // 32 bytes
        $this->aesIv = env('AUTOCOUNT_AES_IV', 'evpScxYkmY2Jq0NB'); // 16 bytes
    }

    /**
     * Encrypt using AES-256-CBC
     */
    private function encrypt($plainText, $key, $iv): string
    {
        return base64_encode(
            openssl_encrypt($plainText, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv)
        );
    }

    /**
     * Decrypt using AES-256-CBC
     */
    private function decrypt($encryptedText, $key, $iv): string
    {
        return openssl_decrypt(
            base64_decode($encryptedText),
            "AES-256-CBC",
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    /**
     * Generate encrypted API password using AES-256-CBC
     */
    public function generateApiPassword(): string
    {
        $todayDate = Carbon::now()->format('Y-m-d');
        $plaintext = $todayDate . $this->encryptionKey;

        return $this->encrypt($plaintext, $this->aesKey, $this->aesIv);
    }

    /**
     * Test encryption/decryption
     */
    public function testEncryption(): array
    {
        $todayDate = Carbon::now()->format('Y-m-d');
        $plaintext = $todayDate . $this->encryptionKey;

        $encrypted = $this->encrypt($plaintext, $this->aesKey, $this->aesIv);
        $decrypted = $this->decrypt($encrypted, $this->aesKey, $this->aesIv);

        return [
            'original' => $plaintext,
            'encrypted' => $encrypted,
            'decrypted' => $decrypted,
            'matches' => $plaintext === $decrypted
        ];
    }

    /**
     * Get state list from AutoCount
     */
    public function getStateList(string $company = null): array
    {
        try {
            // Check cache first
            $cacheKey = "autocount_states_" . ($company ?? 'default');
            if (Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'states' => Cache::get($cacheKey),
                    'from_cache' => true
                ];
            }

            $payload = [
                'apiUsername' => $this->username,
                'apiPassword' => $this->generateApiPassword(),
                'company' => $company ?? 'TIMETEC CLOUD Sandbox', // Updated default
            ];

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/api/Tax/GetStateList', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['dataResults'] && $data['dataResults']['stateList']) {
                    // Cache for 7 days
                    Cache::put($cacheKey, $data['dataResults']['stateList'], now()->addDays(7));

                    Log::info('AutoCount State List Retrieved Successfully', [
                        'states_count' => count($data['dataResults']['stateList']),
                        'company' => $company ?? 'TIMETEC CLOUD Sandbox',
                    ]);

                    return [
                        'success' => true,
                        'states' => $data['dataResults']['stateList'],
                        'data' => $data,
                        'from_cache' => false
                    ];
                } else {
                    Log::error('AutoCount State List Retrieval Failed', [
                        'error' => $data['dataResults']['error'] ?? 'Unknown error',
                        'response' => $data,
                    ]);

                    return [
                        'success' => false,
                        'error' => $data['dataResults']['error'] ?? 'Unknown error',
                        'data' => $data
                    ];
                }
            } else {
                Log::error('AutoCount State List API Request Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'State List API request failed: ' . $response->status(),
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('AutoCount State List Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get country list from AutoCount
     */
    public function getCountryList(string $company = null): array
    {
        try {
            // Check cache first
            $cacheKey = "autocount_countries_" . ($company ?? 'default');
            if (Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'countries' => Cache::get($cacheKey),
                    'from_cache' => true
                ];
            }

            $payload = [
                'apiUsername' => $this->username,
                'apiPassword' => $this->generateApiPassword(),
                'company' => $company ?? 'TIMETEC CLOUD Sandbox', // Updated default
            ];

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/api/Tax/GetCountryList', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['dataResults'] && $data['dataResults']['countryList']) {
                    // Cache for 7 days
                    Cache::put($cacheKey, $data['dataResults']['countryList'], now()->addDays(7));

                    Log::info('AutoCount Country List Retrieved Successfully', [
                        'countries_count' => count($data['dataResults']['countryList']),
                        'company' => $company ?? 'TIMETEC CLOUD Sandbox',
                    ]);

                    return [
                        'success' => true,
                        'countries' => $data['dataResults']['countryList'],
                        'data' => $data,
                        'from_cache' => false
                    ];
                } else {
                    Log::error('AutoCount Country List Retrieval Failed', [
                        'error' => $data['dataResults']['error'] ?? 'Unknown error',
                        'response' => $data,
                    ]);

                    return [
                        'success' => false,
                        'error' => $data['dataResults']['error'] ?? 'Unknown error',
                        'data' => $data
                    ];
                }
            } else {
                Log::error('AutoCount Country List API Request Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Country List API request failed: ' . $response->status(),
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('AutoCount Country List Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get UDF Item List from AutoCount
     */
    public function getUDFItemList(string $udfListName, string $company = null): array
    {
        try {
            // Check cache first
            $cacheKey = "autocount_udf_{$udfListName}_" . ($company ?? 'default');
            if (Cache::has($cacheKey)) {
                return [
                    'success' => true,
                    'udf_items' => Cache::get($cacheKey),
                    'from_cache' => true
                ];
            }

            $payload = [
                'apiUsername' => $this->username,
                'apiPassword' => $this->generateApiPassword(),
                'company' => $company ?? 'TIMETEC CLOUD Sandbox',
                'udfListName' => $udfListName,
            ];

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/api/Invoices/GetUDFItemList', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['dataResults'] && isset($data['dataResults']['udfItemList'])) {
                    $udfItems = $data['dataResults']['udfItemList'] ?? [];

                    // Cache for 24 hours (UDF lists change less frequently)
                    Cache::put($cacheKey, $udfItems, now()->addHours(24));

                    Log::info('AutoCount UDF Item List Retrieved Successfully', [
                        'udf_list_name' => $udfListName,
                        'items_count' => count($udfItems),
                        'company' => $company ?? 'TIMETEC CLOUD Sandbox',
                    ]);

                    return [
                        'success' => true,
                        'udf_items' => $udfItems,
                        'data' => $data,
                        'from_cache' => false
                    ];
                } else {
                    Log::error('AutoCount UDF Item List Retrieval Failed', [
                        'udf_list_name' => $udfListName,
                        'error' => $data['dataResults']['error'] ?? 'Unknown error',
                        'response' => $data,
                    ]);

                    return [
                        'success' => false,
                        'error' => $data['dataResults']['error'] ?? 'Unknown error',
                        'data' => $data
                    ];
                }
            } else {
                Log::error('AutoCount UDF Item List API Request Failed', [
                    'udf_list_name' => $udfListName,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'UDF Item List API request failed: ' . $response->status(),
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('AutoCount UDF Item List Exception', [
                'udf_list_name' => $udfListName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get all UDF lists needed for invoices
     */
    public function getAllUDFLists(string $company = null): array
    {
        $udfLists = ['SalesAdmin', 'Support', 'BillingType', 'ResellerInfo'];
        $results = [];

        foreach ($udfLists as $listName) {
            $results[$listName] = $this->getUDFItemList($listName, $company);
        }

        return $results;
    }

    /**
     * Get sales admin list
     */
    public function getSalesAdminList(string $company = null): array
    {
        return $this->getUDFItemList('SalesAdmin', $company);
    }

    /**
     * Get support list
     */
    public function getSupportList(string $company = null): array
    {
        return $this->getUDFItemList('Support', $company);
    }

    /**
     * Get billing type list
     */
    public function getBillingTypeList(string $company = null): array
    {
        return $this->getUDFItemList('BillingType', $company);
    }

    /**
     * Get reseller info list
     */
    public function getResellerInfoList(string $company = null): array
    {
        return $this->getUDFItemList('ResellerInfo', $company);
    }

    /**
     * Helper method to validate UDF values against AutoCount lists
     */
    public function validateUDFValues(array $udfData, string $company = null): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'suggestions' => []
        ];

        // Validate SalesAdmin
        if (isset($udfData['sales_admin'])) {
            $salesAdminList = $this->getSalesAdminList($company);
            if ($salesAdminList['success']) {
                $validSalesAdmins = $salesAdminList['udf_items'];
                if (!in_array($udfData['sales_admin'], $validSalesAdmins)) {
                    $results['valid'] = false;
                    $results['errors'][] = "Invalid SalesAdmin: {$udfData['sales_admin']}";
                    $results['suggestions']['sales_admin'] = $this->findClosestMatch($udfData['sales_admin'], $validSalesAdmins);
                }
            }
        }

        // Validate Support
        if (isset($udfData['support'])) {
            $supportList = $this->getSupportList($company);
            if ($supportList['success']) {
                $validSupports = $supportList['udf_items'];
                if (!in_array($udfData['support'], $validSupports)) {
                    $results['valid'] = false;
                    $results['errors'][] = "Invalid Support: {$udfData['support']}";
                    $results['suggestions']['support'] = $this->findClosestMatch($udfData['support'], $validSupports);
                }
            }
        }

        // Validate BillingType
        if (isset($udfData['billing_type'])) {
            $billingTypeList = $this->getBillingTypeList($company);
            if ($billingTypeList['success']) {
                $validBillingTypes = $billingTypeList['udf_items'];
                if (!in_array($udfData['billing_type'], $validBillingTypes)) {
                    $results['valid'] = false;
                    $results['errors'][] = "Invalid BillingType: {$udfData['billing_type']}";
                    $results['suggestions']['billing_type'] = $this->findClosestMatch($udfData['billing_type'], $validBillingTypes);
                }
            }
        }

        // Validate ResellerInfo
        if (isset($udfData['reseller_info']) && !empty($udfData['reseller_info'])) {
            $resellerInfoList = $this->getResellerInfoList($company);
            if ($resellerInfoList['success']) {
                $validResellerInfos = $resellerInfoList['udf_items'];
                if (!in_array($udfData['reseller_info'], $validResellerInfos)) {
                    $results['valid'] = false;
                    $results['errors'][] = "Invalid ResellerInfo: {$udfData['reseller_info']}";
                    $results['suggestions']['reseller_info'] = $this->findClosestMatch($udfData['reseller_info'], $validResellerInfos);
                }
            }
        }

        return $results;
    }

    /**
     * Find closest match in a list (simple similarity check)
     */
    private function findClosestMatch(string $needle, array $haystack): ?string
    {
        if (empty($haystack)) {
            return null;
        }

        $needle = strtolower($needle);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($haystack as $item) {
            $item = (string) $item;
            $itemLower = strtolower($item);

            // Check for partial matches
            if (str_contains($itemLower, $needle) || str_contains($needle, $itemLower)) {
                return $item;
            }

            // Calculate similarity score
            $score = similar_text($needle, $itemLower);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $item;
            }
        }

        return $bestScore > 3 ? $bestMatch : null; // Only return if similarity is decent
    }

    /**
     * Create debtor in AutoCount
     */
    public function createDebtor(array $debtorData): array
    {
        try {
            // ✅ Updated payload structure to match the sample you provided
            $payload = [
                'apiUsername' => $this->username,
                'apiPassword' => $this->generateApiPassword(),
                'company' => $debtorData['company'] ?? 'TIMETEC CLOUD Sandbox',
                'debtor' => [
                    'controlAccount' => $debtorData['control_account'] ?? 'ARM-0112-01',
                    'companyName' => $debtorData['company_name'],
                    'addr1' => $debtorData['addr1'] ?? '',
                    'addr2' => $debtorData['addr2'] ?? '',
                    'addr3' => $debtorData['addr3'] ?? '',
                    'addr4' => $debtorData['addr4'] ?? '',
                    'postCode' => $debtorData['post_code'] ?? '',
                    'deliverAddr1' => $debtorData['deliver_addr1'] ?? $debtorData['addr1'] ?? '',
                    'deliverAddr2' => $debtorData['deliver_addr2'] ?? $debtorData['addr2'] ?? '',
                    'deliverAddr3' => $debtorData['deliver_addr3'] ?? $debtorData['addr3'] ?? '',
                    'deliverAddr4' => $debtorData['deliver_addr4'] ?? $debtorData['addr4'] ?? '',
                    'deliverPostCode' => $debtorData['deliver_post_code'] ?? $debtorData['post_code'] ?? '',
                    'contactPerson' => $debtorData['contact_person'] ?? '',
                    'phone' => $debtorData['phone'] ?? '',
                    'mobile' => $debtorData['mobile'] ?? '',
                    'fax1' => $debtorData['fax1'] ?? '',
                    'fax2' => $debtorData['fax2'] ?? '',
                    'salesAgent' => $debtorData['sales_agent'] ?? '',
                    'areaCode' => $debtorData['area_code'] ?? 'MYS-SEL',
                    'email' => $debtorData['email'] ?? '',
                    'taxEntityID' => $debtorData['tax_entity_id'] ?? 3,
                ]
            ];

            Log::info('AutoCount Create Debtor Request', [
                'company_name' => $debtorData['company_name'],
                'payload' => $payload,
            ]);

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/api/Debtor/CreateDebtor', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('AutoCount Debtor API Response', [
                    'full_response' => $data,
                    'status' => $response->status(),
                ]);

                // ✅ Updated response handling to handle multiple possible response structures
                $debtorCode = null;
                $success = false;
                $errorMessage = null;

                // Check different possible response structures
                if (isset($data['dataResults'])) {
                    // Structure 1: dataResults.newDebtorCode
                    if (isset($data['dataResults']['newDebtorCode'])) {
                        $debtorCode = $data['dataResults']['newDebtorCode'];
                        $success = true;
                    }
                    // Structure 2: dataResults.debtorCode
                    elseif (isset($data['dataResults']['debtorCode'])) {
                        $debtorCode = $data['dataResults']['debtorCode'];
                        $success = true;
                    }
                    // Structure 3: error in dataResults
                    elseif (isset($data['dataResults']['error'])) {
                        $errorMessage = $data['dataResults']['error'];
                    }
                    // Structure 4: check if dataResults is a string (debtor code)
                    elseif (is_string($data['dataResults'])) {
                        $debtorCode = $data['dataResults'];
                        $success = true;
                    }
                }
                // Structure 5: Direct response fields
                elseif (isset($data['newDebtorCode'])) {
                    $debtorCode = $data['newDebtorCode'];
                    $success = true;
                }
                elseif (isset($data['debtorCode'])) {
                    $debtorCode = $data['debtorCode'];
                    $success = true;
                }
                // Structure 6: Check message field
                elseif (isset($data['message'])) {
                    if (str_contains(strtolower($data['message']), 'success')) {
                        $success = true;
                        // Try to extract debtor code from message if present
                        if (preg_match('/([A-Z]+-[A-Z0-9]+)/', $data['message'], $matches)) {
                            $debtorCode = $matches[1];
                        } else {
                            // If no code found, use the requested code
                            $debtorCode = $debtorData['debtor_code'] ?? 'Unknown';
                        }
                    } else {
                        $errorMessage = $data['message'];
                    }
                }

                if ($success && $debtorCode) {
                    Log::info('AutoCount Debtor Created Successfully', [
                        'debtor_code' => $debtorCode,
                        'company_name' => $debtorData['company_name'],
                        'response_structure' => 'Parsed successfully',
                    ]);

                    return [
                        'success' => true,
                        'debtor_code' => $debtorCode,
                        'message' => 'Debtor created successfully',
                        'data' => $data
                    ];
                } else {
                    $finalError = $errorMessage ?? 'Unknown error - no debtor code found in response';

                    Log::error('AutoCount Debtor Creation Failed', [
                        'error' => $finalError,
                        'full_response' => $data,
                        'payload_sent' => $payload,
                    ]);

                    return [
                        'success' => false,
                        'error' => $finalError,
                        'data' => $data
                    ];
                }
            } else {
                Log::error('AutoCount Debtor API Request Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload_sent' => $payload,
                ]);

                return [
                    'success' => false,
                    'error' => 'Debtor API request failed: ' . $response->status() . ' - ' . $response->body(),
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('AutoCount Debtor Creation Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload_attempted' => $payload ?? null,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create invoice in AutoCount
     */
    public function createInvoice(array $data): array
    {
        try {
            $apiUrl = $this->apiUrl . '/api/Invoices/CreateInvoice';

            $payload = [
                "apiUsername" => "admin",
                "apiPassword" => $this->generateApiPassword(),
                "company" => $data['company'],
                "invoice" => [
                    "customerCode" => $data['customer_code'],
                    "documentNo" => $data['document_no'],
                    "documentDate" => $data['document_date'],
                    "description" => $data['description'],
                    "salesPerson" => $data['salesperson'],
                    "roundMethod" => $data['round_method'],
                    "inclusive" => $data['inclusive'],
                    "details" => $data['details'],
                    "uDFCustomerName" => $data['uDFCustomerName'] ?? '',
                    "uDFLicenseNumber" => $data['uDFLicenseNumber'] ?? '',
                    "udfSupport" => $data['udfSupport'] ?? '',
                ]
            ];

            Log::info('Creating AutoCount invoice', [
                'url' => $apiUrl,
                'payload' => $payload
            ]);

            // ✅ Increase timeout and add retry logic
            $response = Http::timeout(120) // Increase from default 30s to 120s
                ->retry(3, 5000) // Retry 3 times with 5 second delay
                ->connectTimeout(30) // Connection timeout
                ->withOptions([
                    'verify' => false, // Skip SSL verification if needed
                    'curl' => [
                        CURLOPT_TIMEOUT => 120,
                        CURLOPT_CONNECTTIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 3,
                    ]
                ])
                ->post($apiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('AutoCount invoice created successfully', [
                    'response' => $responseData
                ]);

                return [
                    'success' => true,
                    'invoice_no' => $responseData['invoiceNo'] ?? 'Unknown',
                    'response' => $responseData
                ];
            } else {
                Log::error('AutoCount API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers()
                ]);

                return [
                    'success' => false,
                    'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ];
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('AutoCount API connection failed', [
                'error' => $e->getMessage(),
                'url' => $apiUrl ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => 'Connection timeout: Unable to reach AutoCount API server. Please check network connectivity or try again later.'
            ];
        } catch (\Exception $e) {
            Log::error('AutoCount invoice creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function formatInvoiceDetailsExact(array $details): array
    {
        return array_map(function ($item) {
            return [
                'account' => $item['account'] ?? 'TCL-R5003',
                'itemCode' => $item['itemCode'] ?? $item['item_code'] ?? '',
                'description' => $item['description'] ?? '',
                'location' => $item['location'] ?? 'HQ',
                'quantity' => floatval($item['quantity'] ?? 1),
                'uom' => $item['uom'] ?? 'UNIT',
                'unitPrice' => floatval($item['unitPrice'] ?? $item['unit_price'] ?? 0),
                'amount' => floatval($item['amount'] ?? 0),
            ];
        }, $details);
    }

    /**
     * Create debtor and invoice in sequence
     */
    public function createDebtorAndInvoice(array $debtorData, array $invoiceData): array
    {
        try {
            // Step 1: Create debtor first
            $debtorResult = $this->createDebtor($debtorData);

            if (!$debtorResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to create debtor: ' . $debtorResult['error'],
                    'debtor_result' => $debtorResult,
                    'invoice_result' => null,
                ];
            }

            // Step 2: Use the returned customer code for invoice creation
            $invoiceData['customer_code'] = $debtorResult['debtor_code'];
            $invoiceResult = $this->createInvoice($invoiceData);

            return [
                'success' => $invoiceResult['success'],
                'debtor_code' => $debtorResult['debtor_code'],
                'invoice_no' => $invoiceResult['success'] ? $invoiceResult['invoice_no'] : null,
                'error' => $invoiceResult['success'] ? null : $invoiceResult['error'],
                'debtor_result' => $debtorResult,
                'invoice_result' => $invoiceResult,
            ];
        } catch (\Exception $e) {
            Log::error('AutoCount Debtor and Invoice Creation Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debtor_result' => null,
                'invoice_result' => null,
            ];
        }
    }

    /**
     * Convert Quotation to Debtor data
     */
    public function convertQuotationToDebtorData(\App\Models\Quotation $quotation): array
    {
        $lead = $quotation->lead;
        $companyDetail = $lead->companyDetail;

        return [
            'company' => $this->determineCompanyBySubsidiary($quotation),
            'control_account' => 'ARM-0112-01', // Default control account
            'company_name' => $companyDetail->company_name,
            'addr1' => $companyDetail->address_1 ?? '',
            'addr2' => $companyDetail->address_2 ?? '',
            'addr3' => $companyDetail->address_3 ?? '',
            'addr4' => '', // Usually empty
            'post_code' => $companyDetail->postcode ?? '',
            'deliver_addr1' => $companyDetail->address_1 ?? '',
            'deliver_addr2' => $companyDetail->address_2 ?? '',
            'deliver_addr3' => $companyDetail->address_3 ?? '',
            'deliver_addr4' => '',
            'deliver_post_code' => $companyDetail->postcode ?? '',
            'contact_person' => $companyDetail->name ?? '',
            'phone' => $companyDetail->contact_no ?? $lead->phone ?? '',
            'mobile' => $lead->phone ?? '',
            'fax1' => $companyDetail->fax ?? '',
            'fax2' => '',
            'sales_agent' => $quotation->salesPerson->name ?? 'UNKNOWN',
            'area_code' => $this->determineAreaCodeFromAutoCount($companyDetail, $quotation),
            'email' => $companyDetail->email ?? $lead->email ?? '',
            'tax_entity_id' => 3, // Default tax entity
        ];
    }

    /**
     * Convert Quotation record to AutoCount invoice format
     */
    public function convertQuotationToInvoiceData(\App\Models\Quotation $quotation, ?string $customerCode = null): array
    {
        $lead = $quotation->lead;
        $companyDetail = $lead->companyDetail;

        $details = [];
        foreach ($quotation->items as $item) {
            $product = $item->product;

            // ✅ Calculate tax-inclusive unit price for AutoCount
            $baseUnitPrice = (float) $item->unit_price;
            $taxInclusiveUnitPrice = $baseUnitPrice;

            // Check if product is taxable and calculate inclusive price
            if ($product && $product->taxable && ($quotation->sst_rate ?? 0) > 0) {
                $taxRate = (float) ($quotation->sst_rate ?? 0);
                $taxInclusiveUnitPrice = $baseUnitPrice * (1 + ($taxRate / 100));
            }

            $details[] = [
                'account' => 'TCL-R5003', // Default account
                'itemCode' => $product->code ?? $product->id,
                'description' => strip_tags($item->description),
                'location' => 'HQ',
                'project' => '',
                'department' => '',
                'quantity' => (float) $item->quantity,
                'uom' => 'UNIT',
                'unitPrice' => $taxInclusiveUnitPrice, // ✅ Use tax-inclusive price for AutoCount
                'discount' => '0',
                'amount' => (float) $item->total_after_tax, // ✅ Use total after tax
                'gstCode' => '',
                'gstAdjustment' => 0,
                'taxCode' => $item->tax_code ?? '',
                'taxRate' => $quotation->sst_rate ?? 0,
            ];
        }

        return [
            'company' => $this->determineCompanyBySubsidiary($quotation),
            'customer_code' => $customerCode, // Will be set after debtor creation
            'document_date' => Carbon::parse($quotation->quotation_date)->format('Y-m-d'),
            'description' => 'PROFORMA INVOICE - ' . $companyDetail->company_name,
            'currency_rate' => $quotation->currency === 'USD' ? 1 : 1,
            'salesperson' => $quotation->salesPerson->name ?? 'UNKNOWN',
            'inclusive' => true,
            'details' => $details,
            'udf_sales_admin' => $quotation->salesPerson->name ?? 'UNKNOWN',
            'udf_support' => $lead->support ?? $quotation->salesPerson->name ?? 'UNKNOWN',
            'udf_billing_type' => $quotation->sales_type ?? 'NEW SALES',
            'udf_reseller_info' => $lead->customer_type === 'RESELLER' ? $companyDetail->company_name : '',
        ];
    }

    /**
     * Enhanced convert Quotation to Invoice data with UDF validation
     */
    public function convertQuotationToInvoiceDataEnhanced(\App\Models\Quotation $quotation, ?string $customerCode = null): array
    {
        $invoiceData = $this->convertQuotationToInvoiceData($quotation, $customerCode);

        // Validate UDF values against AutoCount lists
        $udfData = [
            'sales_admin' => $invoiceData['udf_sales_admin'],
            'support' => $invoiceData['udf_support'],
            'billing_type' => $invoiceData['udf_billing_type'],
            'reseller_info' => $invoiceData['udf_reseller_info'],
        ];

        $validation = $this->validateUDFValues($udfData, $invoiceData['company']);

        if (!$validation['valid']) {
            Log::warning('UDF Validation Failed for Quotation', [
                'quotation_id' => $quotation->id,
                'errors' => $validation['errors'],
                'suggestions' => $validation['suggestions'],
            ]);

            // Apply suggestions if available
            if (isset($validation['suggestions']['sales_admin'])) {
                $invoiceData['udf_sales_admin'] = $validation['suggestions']['sales_admin'];
            }
            if (isset($validation['suggestions']['support'])) {
                $invoiceData['udf_support'] = $validation['suggestions']['support'];
            }
            if (isset($validation['suggestions']['billing_type'])) {
                $invoiceData['udf_billing_type'] = $validation['suggestions']['billing_type'];
            }
            if (isset($validation['suggestions']['reseller_info'])) {
                $invoiceData['udf_reseller_info'] = $validation['suggestions']['reseller_info'];
            }
        }

        return $invoiceData;
    }

    /**
     * Convert DebtorAging record to AutoCount invoice format
     */
    public function convertDebtorAgingToInvoiceData(\App\Models\DebtorAging $record): array
    {
        // Get invoice details from your invoice_details table
        $invoiceDetails = \App\Models\InvoiceDetail::where('invoice_no', $record->invoice_number)->get();

        $details = [];
        foreach ($invoiceDetails as $detail) {
            $details[] = [
                'account' => $detail->account ?? 'TCL-R5003', // Default account
                'itemCode' => $detail->item_code,
                'description' => $detail->description,
                'location' => $detail->location ?? 'HQ', // Default location
                'project' => $detail->project ?? '',
                'department' => $detail->department ?? '',
                'quantity' => $detail->quantity,
                'uom' => $detail->uom ?? 'UNIT',
                'unitPrice' => $detail->unit_price,
                'discount' => $detail->discount ?? '0',
                'amount' => $detail->local_sub_total,
                'gstCode' => $detail->gst_code ?? '',
                'gstAdjustment' => $detail->gst_adjustment ?? 0,
                'taxCode' => $detail->tax_code ?? '',
                'taxRate' => $detail->tax_rate ?? 0,
            ];
        }

        return [
            'customer_code' => $record->debtor_code,
            'document_date' => Carbon::parse($record->invoice_date)->format('Y-m-d'),
            'description' => 'INVOICE - ' . $record->company_name,
            'currency_rate' => $record->exchange_rate,
            'salesperson' => $record->salesperson,
            'inclusive' => true,
            'details' => $details,
            'udf_sales_admin' => $record->salesperson, // You might need to map this
            'udf_support' => $record->support ?? '',
            'udf_billing_type' => 'NORMAL', // You might need to determine this
            'udf_reseller_info' => '',
        ];
    }

    /**
     * Helper method to determine company by subsidiary
     */
    private function determineCompanyBySubsidiary(\App\Models\Quotation $quotation): string
    {
        // For testing purposes, always use TIMETEC CLOUD Sandbox
        // TODO: Update this logic once testing is complete
        return 'TIMETEC CLOUD Sandbox';

        // Original logic (commented out for testing):
        /*
        $subsidiary = $quotation->subsidiary ?? 'TIMETEC CLOUD SDN BHD';

        $companyMap = [
            'TIMETEC CLOUD SDN BHD' => 'TIMETEC CLOUD SDN BHD',
            'TIMETEC COMPUTING SDN BHD' => 'TIMETEC COMPUTING SDN BHD',
            'TIMETEC COMMUNITY SDN. BHD.' => 'TIMETEC COMMUNITY SDN. BHD.',
            'TIMETEC PARKING SDN. BHD.' => 'TIMETEC PARKING SDN. BHD.',
            // Add more mappings as needed
        ];

        return $companyMap[$subsidiary] ?? 'TIMETEC CLOUD SDN BHD';
        */
    }

    /**
     * Helper method to determine area code using AutoCount API
     */
    private function determineAreaCodeFromAutoCount($companyDetail, \App\Models\Quotation $quotation): string
    {
        // Try to get state list from AutoCount for accurate mapping
        $company = $this->determineCompanyBySubsidiary($quotation);
        $stateListResult = $this->getStateList($company);

        if ($stateListResult['success']) {
            $states = $stateListResult['states'];
            $userState = strtolower($companyDetail->state ?? '');

            // Create mapping array from AutoCount response
            foreach ($states as $stateData) {
                $stateName = strtolower($stateData['state']);
                $stateValue = $stateData['value'];

                // Check for exact matches or partial matches
                if (str_contains($userState, $stateName) || str_contains($stateName, $userState)) {
                    // Convert state value to area code format
                    return $this->convertStateValueToAreaCode($stateValue, $stateName);
                }
            }
        }

        // Fallback to manual mapping if API fails
        return $this->getFallbackAreaCode($companyDetail->state ?? '');
    }

    /**
     * Convert AutoCount state value to area code format
     */
    private function convertStateValueToAreaCode(string $stateValue, string $stateName): string
    {
        // Map AutoCount state values to area codes
        $stateToAreaCode = [
            '01' => 'MYS-JHR', // Johor
            '02' => 'MYS-KDH', // Kedah
            '03' => 'MYS-KTN', // Kelantan
            '04' => 'MYS-MLK', // Melaka
            '05' => 'MYS-NS',  // Negeri Sembilan
            '06' => 'MYS-PHG', // Pahang
            '07' => 'MYS-PNG', // Pulau Pinang
            '08' => 'MYS-PRK', // Perak
            '09' => 'MYS-PLS', // Perlis
            '10' => 'MYS-SEL', // Selangor
            '11' => 'MYS-TRG', // Terengganu
            '12' => 'MYS-SBH', // Sabah
            '13' => 'MYS-SWK', // Sarawak
            '14' => 'MYS-KUL', // Wilayah Persekutuan Kuala Lumpur
            '15' => 'MYS-LBN', // Wilayah Persekutuan Labuan
            '16' => 'MYS-PJY', // Wilayah Persekutuan Putrajaya
        ];

        return $stateToAreaCode[$stateValue] ?? 'MYS-SEL'; // Default to Selangor
    }

    /**
     * Fallback area code mapping if API fails
     */
    private function getFallbackAreaCode(string $state): string
    {
        $state = strtolower($state);

        $areaCodes = [
            'johor' => 'MYS-JHR',
            'kedah' => 'MYS-KDH',
            'kelantan' => 'MYS-KTN',
            'melaka' => 'MYS-MLK',
            'malacca' => 'MYS-MLK',
            'negeri sembilan' => 'MYS-NS',
            'pahang' => 'MYS-PHG',
            'penang' => 'MYS-PNG',
            'pulau pinang' => 'MYS-PNG',
            'perak' => 'MYS-PRK',
            'perlis' => 'MYS-PLS',
            'selangor' => 'MYS-SEL',
            'terengganu' => 'MYS-TRG',
            'sabah' => 'MYS-SBH',
            'sarawak' => 'MYS-SWK',
            'kuala lumpur' => 'MYS-KUL',
            'kl' => 'MYS-KUL',
            'labuan' => 'MYS-LBN',
            'putrajaya' => 'MYS-PJY',
        ];

        foreach ($areaCodes as $stateName => $areaCode) {
            if (str_contains($state, $stateName)) {
                return $areaCode;
            }
        }

        return 'MYS-SEL'; // Default to Selangor
    }
}
