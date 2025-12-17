<?php

namespace App\Console\Commands;

use App\Services\AutoCountInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAutoCountApi extends Command
{
    protected $signature = 'autocount:test-api {--company=} {--discover}';
    protected $description = 'Test AutoCount API connectivity and methods';

    protected $createdDebtorCode = null;

    public function handle()
    {
        $company = $this->option('company') ?: 'TIMETEC CLOUD Sandbox';
        $discover = $this->option('discover');
        $service = new AutoCountInvoiceService();

        $apiUrl = env('AUTOCOUNT_API_URL');
        $this->info("Testing AutoCount API at: {$apiUrl}");
        $this->info("Using company: {$company}");
        $this->newLine();

        // Test 0: Basic connectivity
        $this->info('0. Testing basic connectivity...');
        try {
            $response = Http::timeout(10)->get($apiUrl);
            $this->info("✅ Server responded with status: {$response->status()}");

            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                $this->line("   Content-Type: {$contentType}");

                $body = $response->body();
                if (strlen($body) < 500) {
                    $this->line("   Response: " . substr($body, 0, 200));
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Server connection failed: " . $e->getMessage());
            return 1;
        }
        $this->newLine();

        // Test 1: Encryption
        $this->info('1. Testing encryption/decryption...');
        $encryptionTest = $service->testEncryption();
        if ($encryptionTest['matches']) {
            $this->info('✅ Encryption test passed');
            $this->line("   Today's date: " . now()->format('Y-m-d'));
            $this->line("   Plaintext: " . $encryptionTest['original']);
            $this->line("   Encrypted: " . $encryptionTest['encrypted']);
        } else {
            $this->error('❌ Encryption test failed');
            return 1;
        }
        $this->newLine();

        if ($discover) {
            $this->info('🔍 Discovery Mode: Testing common API endpoints...');
            $this->discoverApiEndpoints($apiUrl, $service);
            return 0;
        }

        // Test 2: API endpoints
        $this->info('2. Testing API endpoints...');
        $endpoints = [
            '/api/Tax/GetStateList' => 'State List',
            '/api/Tax/GetCountryList' => 'Country List',
            '/api/Invoices/GetUDFItemList' => 'UDF Items',
        ];

        foreach ($endpoints as $endpoint => $description) {
            $this->testEndpoint($apiUrl . $endpoint, $description, $service);
        }
        $this->newLine();

        // ✅ Test 3: Create Invoice
        // $this->testCreateInvoice($service);

        // // ✅ Test 4: Create Debtor
        // $this->testCreateDebtor($service);

        // // ✅ Test 5: Create Debtor and Invoice Combined
        // $this->testCreateDebtorAndInvoice($service);

        return 0;
    }

    private function testEndpoint(string $url, string $description, AutoCountInvoiceService $service): void
    {
        try {
            // For POST endpoints, try with minimal payload
            if (str_contains($url, 'GetStateList') || str_contains($url, 'GetCountryList')) {
                $payload = [
                    'apiUsername' => 'admin',
                    'apiPassword' => $service->generateApiPassword(),
                    'company' => 'TIMETEC CLOUD Sandbox',
                ];
                $response = Http::timeout(5)->post($url, $payload);
            } elseif (str_contains($url, 'GetUDFItemList')) {
                $payload = [
                    'apiUsername' => 'admin',
                    'apiPassword' => $service->generateApiPassword(),
                    'company' => 'TIMETEC CLOUD Sandbox',
                    'udfListName' => 'SalesAdmin',
                ];
                $response = Http::timeout(5)->post($url, $payload);
            } else {
                // For GET endpoints
                $response = Http::timeout(5)->get($url);
            }

            $status = $response->status();
            if ($status === 200) {
                $this->info("   ✅ {$description} - {$status}");

                // Try to parse response for useful info
                if ($response->header('Content-Type') && str_contains($response->header('Content-Type'), 'json')) {
                    $data = $response->json();
                    if (isset($data['dataResults'])) {
                        $this->line("      📊 Contains dataResults");
                    }
                    if (isset($data['message'])) {
                        $this->line("      💬 Message: " . $data['message']);
                    }
                }
            } elseif ($status === 404) {
                $this->line("   ❌ {$description} - 404 Not Found");
            } elseif ($status === 401 || $status === 403) {
                $this->warn("   ⚠️  {$description} - {$status} Auth Required");
            } else {
                $this->line("   ℹ️  {$description} - {$status}");
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'Connection refused')) {
                $this->line("   ❌ {$description} - Connection refused");
            } elseif (str_contains($errorMsg, 'timeout')) {
                $this->line("   ⏱️  {$description} - Timeout");
            } else {
                $this->line("   ❌ {$description} - Error");
            }
        }
    }

    private function discoverApiEndpoints(string $baseUrl, AutoCountInvoiceService $service): void
    {
        $this->info('🔍 Trying to discover API structure...');

        // Common API base paths
        $basePaths = [
            '',
            '/api',
            '/Api',
            '/API',
            '/webapi',
            '/WebAPI',
            '/rest',
            '/v1',
            '/v2',
        ];

        // Common controller/resource names
        $resources = [
            'Tax', 'Invoices', 'Debtor', 'Customer', 'Item', 'Product',
            'tax', 'invoices', 'debtor', 'customer', 'item', 'product'
        ];

        // Common action names
        $actions = [
            'GetStateList', 'GetCountryList', 'GetUDFItemList',
            'getStateList', 'getCountryList', 'getUDFItemList',
            'statelist', 'countrylist', 'udflist'
        ];

        $foundEndpoints = [];

        foreach ($basePaths as $basePath) {
            foreach ($resources as $resource) {
                foreach ($actions as $action) {
                    $endpoint = $basePath . '/' . $resource . '/' . $action;
                    $url = $baseUrl . $endpoint;

                    try {
                        $response = Http::timeout(3)->get($url);
                        if ($response->status() !== 404) {
                            $foundEndpoints[] = [
                                'endpoint' => $endpoint,
                                'status' => $response->status(),
                                'method' => 'GET'
                            ];
                        }
                    } catch (\Exception $e) {
                        // Ignore errors for discovery
                    }

                    // Also try POST
                    try {
                        $response = Http::timeout(3)->post($url, ['test' => 'discovery']);
                        if ($response->status() !== 404) {
                            $foundEndpoints[] = [
                                'endpoint' => $endpoint,
                                'status' => $response->status(),
                                'method' => 'POST'
                            ];
                        }
                    } catch (\Exception $e) {
                        // Ignore errors for discovery
                    }
                }
            }
        }

        if (!empty($foundEndpoints)) {
            $this->info('🎯 Found potential endpoints:');
            foreach ($foundEndpoints as $found) {
                $this->line("   {$found['method']} {$found['endpoint']} - Status: {$found['status']}");
            }
        } else {
            $this->warn('No working endpoints discovered with common patterns');
        }
    }

    private function testCreateInvoice(AutoCountInvoiceService $service): void
    {
        $this->info('3. Testing Create Invoice...');

        try {
            // Get document number from option, or use your preset default
            $documentNo = 'EHIN2512-0002';

            $this->line('   🔍 Making API call with document number: ' . $documentNo);

            $apiUrl = env('AUTOCOUNT_API_URL') . '/api/Invoices/CreateInvoice';

            // ✅ Use the EXACT payload structure that works
            $payload = [
                "apiUsername" => "admin",
                "apiPassword" => $service->generateApiPassword(),
                "company" => "TIMETEC CLOUD Sandbox",
                "invoice" => [
                    "customerCode" => "ARM-A0003",
                    "documentNo" => $documentNo,
                    "documentDate" => date('Y-m-d'),
                    "description" => "Test invoice - " . $documentNo,
                    "salesPerson" => "FAZA",
                    "roundMethod" => 0,
                    "inclusive" => true,
                    "details" => [
                        [
                            "account" => "TCL-R5003",
                            "itemCode" => "TCL_ACCESS-NEW",
                            "description" => "Access License",
                            "location" => "HQ",
                            "quantity" => 5,
                            "uom" => "DOOR",
                            "unitPrice" => 1275,
                            "amount" => 1275
                        ]
                    ]
                ]
            ];

            $this->line('   📝 Invoice Data:');
            $this->line("      Customer Code: {$payload['invoice']['customerCode']}");
            $this->line("      Document No: {$payload['invoice']['documentNo']}");
            $this->line("      Document Date: {$payload['invoice']['documentDate']}");
            $this->line("      Description: {$payload['invoice']['description']}");
            $this->line("      Salesperson: {$payload['invoice']['salesPerson']}");
            $this->newLine();

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($apiUrl, $payload);

            $this->line("   📡 API Response Status: " . $response->status());

            if ($response->successful()) {
                $data = $response->json();
                $this->info("   ✅ API call successful!");

                if (isset($data['dataResults']['newInvoiceNo'])) {
                    $returnedDocNo = $data['dataResults']['newInvoiceNo'];
                    $this->line("      📄 New Invoice No: {$returnedDocNo}");

                    if ($returnedDocNo === $documentNo) {
                        $this->info("      ✅ Document number matches request");
                    } else {
                        $this->warn("      ⚠️ Document number differs:");
                        $this->line("         Requested: {$documentNo}");
                        $this->line("         Returned:  {$returnedDocNo}");
                    }
                }

                if (isset($data['dataResults']['error'])) {
                    if (empty($data['dataResults']['error'])) {
                        $this->info("      ✅ No errors reported");
                    } else {
                        $this->warn("      ⚠️ Error: {$data['dataResults']['error']}");
                    }
                }

            } else {
                $this->error("   ❌ API call failed with status: " . $response->status());
                $this->line("   Response: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Invoice test exception: " . $e->getMessage());
        }

        $this->newLine();
    }

    // Update the testCreateDebtor method to show the actual response
    private function testCreateDebtor(AutoCountInvoiceService $service): void
    {
        $this->info('4. Testing Create Debtor...');

        try {
            // Sample debtor data for testing
            $debtorData = [
                'company' => 'TIMETEC CLOUD Sandbox',
                'control_account' => 'ARM-0112-01',
                'company_name' => 'Test Company API ' . now()->format('His'),
                'addr1' => 'Test Address Line 1',
                'addr2' => 'Test Address Line 2',
                'addr3' => 'Test City',
                'addr4' => 'Test State',
                'post_code' => '12345',
                'deliver_addr1' => 'Test Address Line 1',
                'deliver_addr2' => 'Test Address Line 2',
                'deliver_addr3' => 'Test City',
                'deliver_addr4' => 'Test State',
                'deliver_post_code' => '12345',
                'contact_person' => 'Test Contact Person',
                'phone' => '03-12345678',
                'mobile' => '012-3456789',
                'fax1' => '03-87654321',
                'fax2' => '03-87654822',
                'sales_agent' => 'ADMIN',
                'area_code' => 'MYS-SEL',
                'email' => 'test@company.com',
                'tax_entity_id' => 3,
            ];

            $this->line('   📝 Debtor Data:');
            $this->line("      Company Name: {$debtorData['company_name']}");
            $this->line("      Contact Person: {$debtorData['contact_person']}");
            $this->line("      Phone: {$debtorData['phone']}");
            $this->line("      Sales Agent: {$debtorData['sales_agent']}");
            $this->line("      Area Code: {$debtorData['area_code']}");
            $this->newLine();

            // ✅ Make API call using the CORRECT format
            $this->line('   🔍 Making direct API call with CORRECT format...');

            $apiUrl = env('AUTOCOUNT_API_URL') . '/api/Debtor/CreateDebtor';

            // ✅ Use the exact format that works (with debtor wrapper)
            $payload = [
                'apiUsername' => 'admin',
                'apiPassword' => $service->generateApiPassword(),
                'company' => $debtorData['company'],
                'debtor' => [
                    'controlAccount' => $debtorData['control_account'],
                    'companyName' => $debtorData['company_name'],
                    'addr1' => $debtorData['addr1'],
                    'addr2' => $debtorData['addr2'],
                    'addr3' => $debtorData['addr3'],
                    'addr4' => $debtorData['addr4'],
                    'postCode' => $debtorData['post_code'],
                    'deliverAddr1' => $debtorData['deliver_addr1'],
                    'deliverAddr2' => $debtorData['deliver_addr2'],
                    'deliverAddr3' => $debtorData['deliver_addr3'],
                    'deliverAddr4' => $debtorData['deliver_addr4'],
                    'deliverPostCode' => $debtorData['deliver_post_code'],
                    'contactPerson' => $debtorData['contact_person'],
                    'phone' => $debtorData['phone'],
                    'mobile' => $debtorData['mobile'],
                    'fax1' => $debtorData['fax1'],
                    'fax2' => $debtorData['fax2'],
                    'salesAgent' => $debtorData['sales_agent'],
                    'areaCode' => $debtorData['area_code'],
                    'email' => $debtorData['email'],
                    'taxEntityID' => $debtorData['tax_entity_id']
                ]
            ];

            $this->line('   📤 Payload Structure:');
            $this->line('   ' . json_encode($payload, JSON_PRETTY_PRINT));
            $this->newLine();

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($apiUrl, $payload);

            $this->line("   📡 API Response Status: " . $response->status());
            $this->line("   📄 Raw Response Body:");
            $this->line("   " . str_repeat("-", 60));
            $responseBody = $response->body();
            $this->line("   " . $responseBody);
            $this->line("   " . str_repeat("-", 60));

            if ($response->successful()) {
                $data = $response->json();
                if ($data) {
                    $this->line("   📊 Parsed JSON Response:");
                    $this->line("   " . json_encode($data, JSON_PRETTY_PRINT));

                    // Show all available keys
                    $this->line("   🔑 Available Response Keys:");
                    if (is_array($data)) {
                        foreach (array_keys($data) as $key) {
                            $this->line("      - {$key}");
                        }
                    }

                    // ✅ Check for success indicators
                    if (isset($data['success']) || isset($data['result']) || isset($data['debtorCode']) || isset($data['customerCode'])) {
                        $this->info("   🎉 Direct API call appears successful!");

                        // Try to find the debtor code
                        $debtorCode = $data['debtorCode'] ?? $data['customerCode'] ?? $data['code'] ?? 'Unknown';
                        $this->line("      👤 Debtor Code: {$debtorCode}");
                    }
                }
            } else {
                $this->error("   ❌ API call failed with status: " . $response->status());
                if ($response->body()) {
                    $this->line("   Error response: " . $response->body());
                }
            }

            // Now try the service method (this will likely still fail until we update the service)
            $this->line('   🔧 Testing service method...');
            $result = $service->createDebtor($debtorData);

            if ($result['success']) {
                $this->info("   ✅ Service method: Debtor created successfully!");
                if (isset($result['debtor_code'])) {
                    $this->line("      Debtor Code: {$result['debtor_code']}");
                }

                // Store the debtor code for invoice testing
                $this->createdDebtorCode = $result['debtor_code'] ?? null;

            } else {
                $this->error("   ❌ Service method: Debtor creation failed!");
                $this->line("      Error: {$result['error']}");

                if (isset($result['data'])) {
                    $this->line("      Service Response Data:");
                    $this->line("   " . json_encode($result['data'], JSON_PRETTY_PRINT));
                }
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Debtor test exception: " . $e->getMessage());
            $this->line("      Stack trace: " . $e->getTraceAsString());
        }

        $this->newLine();
    }

    private function testCreateDebtorAndInvoice(AutoCountInvoiceService $service): void
    {
        $this->info('5. Testing Create Debtor and Invoice (Combined)...');

        try {
            // Sample debtor data
            $debtorData = [
                'company' => 'TIMETEC CLOUD Sandbox',
                'control_account' => 'ARM-0112-01',
                'company_name' => 'Combined Test Company ' . now()->format('His'),
                'addr1' => 'Combined Test Address',
                'addr2' => 'Suite 123',
                'addr3' => 'Kuala Lumpur',
                'addr4' => 'Selangor',
                'post_code' => '50000',
                'contact_person' => 'Combined Test Contact',
                'phone' => '03-11111111',
                'mobile' => '012-1111111',
                'sales_agent' => 'ADMIN',
                'area_code' => 'MYS-SEL',
                'email' => 'combined@test.com',
                'tax_entity_id' => 3,
            ];

            // Sample invoice data
            $invoiceData = [
                'company' => 'TIMETEC CLOUD Sandbox',
                'document_date' => now()->format('Y-m-d'),
                'description' => 'COMBINED TEST INVOICE',
                'currency_rate' => 1,
                'salesperson' => 'ADMIN',
                'round_method' => 5,
                'inclusive' => true,
                'details' => [
                    [
                        'account' => 'TCL-R5003',
                        'itemCode' => 'COMBO-ITEM',
                        'description' => 'Combined Test Product',
                        'location' => 'HQ',
                        'project' => '',
                        'department' => '',
                        'quantity' => 2.0,
                        'uom' => 'UNIT',
                        'unitPrice' => 150.00,
                        'discount' => '0',
                        'amount' => 300.00,
                        'gstCode' => '',
                        'gstAdjustment' => 0,
                        'taxCode' => 'SR',
                        'taxRate' => 6,
                    ]
                ],
                'udf_sales_admin' => 'ADMIN',
                'udf_support' => 'ADMIN',
                'udf_billing_type' => 'NEW SALES',
                'udf_reseller_info' => '',
            ];

            $this->line('   📝 Creating debtor and invoice together...');

            $result = $service->createDebtorAndInvoice($debtorData, $invoiceData);

            if ($result['success']) {
                $this->info("   ✅ Debtor and Invoice created successfully!");
                $this->line("      Debtor Code: {$result['debtor_code']}");
                $this->line("      Invoice No: {$result['invoice_no']}");
                $this->line("      Company Name: {$debtorData['company_name']}");
            } else {
                $this->error("   ❌ Combined creation failed!");
                $this->line("      Error: {$result['error']}");

                if (isset($result['debtor_result']) && $result['debtor_result']) {
                    $this->line("      Debtor Status: " . ($result['debtor_result']['success'] ? 'Success' : 'Failed'));
                }
                if (isset($result['invoice_result']) && $result['invoice_result']) {
                    $this->line("      Invoice Status: " . ($result['invoice_result']['success'] ? 'Success' : 'Failed'));
                }
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Combined test exception: " . $e->getMessage());
        }

        $this->newLine();
    }
}
