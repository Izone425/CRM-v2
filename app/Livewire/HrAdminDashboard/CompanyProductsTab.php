<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\Quotation;
use App\Models\SoftwareHandover;
use App\Services\CRMApiService;
use Carbon\Carbon;
use Livewire\Component;

class CompanyProductsTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public array $productData = [];
    public array $licenseRecords = [];
    public array $groupedLicenseRecords = [];
    public ?string $maxPaidEndDate = null;

    // Edit modal properties
    public bool $showEditModal = false;
    public ?int $editingLicenseNo = null;
    public array $editForm = [
        'total_user' => '',
        'month' => '',
        'start_date' => '',
        'end_date' => '',
        'status' => 'active',
    ];
    public string $editingLicenseType = '';

    // Bulk edit modal properties
    public bool $showBulkEditModal = false;
    public array $bulkEditForm = [
        'total_user' => '',
        'start_date' => '',
        'end_date' => '',
        'status' => '',
    ];
    public array $bulkEditEnabled = [
        'total_user' => false,
        'start_date' => false,
        'end_date' => false,
        'status' => false,
    ];

    // Selection mode properties
    public bool $isSelectionMode = false;
    public array $selectedLicenseNos = [];

    // Filter properties
    public string $filterType = 'all';
    public string $filterStatus = 'all';
    public string $filterProduct = 'all';
    public ?string $filterStartDate = null;
    public ?string $filterEndDate = null;

    // PI Modal properties
    public bool $showPiModal = false;
    public ?string $selectedInvoiceNo = null;
    public array $piData = [];
    public array $apiPiData = [];  // Store API-based PI data
    public bool $piLoading = false;
    public ?string $piError = null;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadLicenseRecords();
        $this->loadProductData();
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();

        // Compute max end date from PAID license records for consolidate billing
        $this->maxPaidEndDate = collect($this->licenseRecords)
            ->where('type', 'PAID')
            ->max('end_date');
    }

    protected function loadProductData(): void
    {
        // Derive counts from active license records
        $counts = [
            'attendance_user' => 0,
            'leave_user' => 0,
            'claim_user' => 0,
            'payroll_user' => 0,
            'onboarding' => 0,
            'recruitment' => 0,
            'appraisal' => 0,
            'training' => 0,
        ];

        foreach ($this->licenseRecords as $record) {
            if (($record['status'] ?? '') === 'expired') continue;
            $type = strtolower($record['license_type'] ?? '');
            $users = (int) ($record['total_user'] ?? 0);

            if (str_contains($type, ' ta') || str_contains($type, 'attendance')) {
                $counts['attendance_user'] += $users;
            }
            if (str_contains($type, 'leave')) {
                $counts['leave_user'] += $users;
            }
            if (str_contains($type, 'claim')) {
                $counts['claim_user'] += $users;
            }
            if (str_contains($type, 'payroll')) {
                $counts['payroll_user'] += $users;
            }
        }

        $totalUsers = max(
            $counts['attendance_user'],
            $counts['leave_user'],
            $counts['claim_user'],
            $counts['payroll_user']
        );

        $this->productData = [
            'user_account' => [
                'total' => $totalUsers,
                'active' => $totalUsers,
                'inactive' => 0,
            ],
            'attendance_user' => [
                'total' => $counts['attendance_user'],
                'active' => $counts['attendance_user'],
                'inactive' => 0,
            ],
            'leave_user' => [
                'total' => $counts['leave_user'],
                'active' => $counts['leave_user'],
                'inactive' => 0,
            ],
            'claim_user' => [
                'total' => $counts['claim_user'],
                'active' => $counts['claim_user'],
                'inactive' => 0,
            ],
            'payroll_user' => [
                'total' => $counts['payroll_user'],
                'active' => $counts['payroll_user'],
                'inactive' => 0,
            ],
            'onboarding_offboarding' => [
                'total' => $counts['onboarding'],
                'active' => $counts['onboarding'],
                'inactive' => 0,
            ],
            'recruitment' => [
                'total' => $counts['recruitment'],
                'active' => $counts['recruitment'],
                'inactive' => 0,
            ],
            'appraisal' => [
                'total' => $counts['appraisal'],
                'active' => $counts['appraisal'],
                'inactive' => 0,
            ],
            'training' => [
                'total' => $counts['training'],
                'active' => $counts['training'],
                'inactive' => 0,
            ],
        ];
    }

    protected function loadLicenseRecords(): void
    {
        $softwareHandover = $this->companyData['software_handover'] ?? null;
        $this->licenseRecords = [];

        if (!$softwareHandover) return;

        $no = 1;

        // Build TRIAL records from type_1_pi_invoice_data (Create DB + Trial License data)
        if ($softwareHandover->crm_buffer_license_id && $softwareHandover->type_1_pi_invoice_data) {
            $piData = is_string($softwareHandover->type_1_pi_invoice_data)
                ? json_decode($softwareHandover->type_1_pi_invoice_data, true)
                : $softwareHandover->type_1_pi_invoice_data;

            $items = $piData['items'] ?? [];
            $bufferMonth = (int) ($piData['buffer_month'] ?? 1);
            $startDate = $softwareHandover->db_creation
                ? Carbon::parse($softwareHandover->db_creation)->format('Y-m-d')
                : null;
            $endDate = $startDate
                ? Carbon::parse($startDate)->addMonths($bufferMonth)->subDay()->format('Y-m-d')
                : null;

            $codeToName = [
                'TCL_TA' => 'TimeTec TA',
                'TCL_LEAVE' => 'TimeTec Leave',
                'TCL_CLAIM' => 'TimeTec Claim',
                'TCL_PAYROLL' => 'TimeTec Payroll',
            ];

            $trialInvoiceNo = 'TR' . Carbon::parse($softwareHandover->db_creation)->format('ymd') . $softwareHandover->crm_buffer_license_id;

            foreach ($items as $item) {
                $code = $item['product_code'] ?? '';
                $licenseName = $item['description'] ?? $code;
                foreach ($codeToName as $prefix => $name) {
                    if (str_starts_with($code, $prefix)) {
                        $licenseName = $name;
                        break;
                    }
                }

                $qty = (int) ($item['qty'] ?? 0);
                $isExpired = $endDate && Carbon::parse($endDate)->isPast();

                $this->licenseRecords[] = [
                    'no' => $no++,
                    'type' => 'TRIAL',
                    'invoice_no' => $trialInvoiceNo,
                    'license_type' => $licenseName,
                    'unit' => $qty,
                    'user_limit' => $qty,
                    'total_user' => $qty,
                    'total_login' => 0,
                    'total_terminal' => 0,
                    'month' => $bufferMonth,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $isExpired ? 'expired' : 'active',
                    'renewed' => '-',
                ];
            }
        }
    }

    protected function getGroupedLicenseRecords(): array
    {
        return $this->getGroupedLicenseRecordsFrom($this->licenseRecords);
    }

    protected function getGroupedLicenseRecordsFrom(array $records): array
    {
        $grouped = [];

        foreach ($records as $record) {
            // Group by invoice_no for both TRIAL and PAID
            $key = $record['invoice_no'];

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'invoice_no' => $record['invoice_no'],
                    'type' => $record['type'],
                    'sales_type' => $record['sales_type'] ?? null,
                    'month' => $record['month'],
                    'start_date' => $record['start_date'],
                    'end_date' => $record['end_date'],
                    'status' => $record['status'],
                    'renewed' => $record['renewed'],
                    'products' => [],
                ];
            }

            $grouped[$key]['products'][] = [
                'no' => $record['no'],
                'license_type' => $record['license_type'],
                'total_user' => $record['total_user'],
                'total_login' => $record['total_login'],
                'month' => $record['month'],
                'start_date' => $record['start_date'],
                'end_date' => $record['end_date'],
            ];
        }

        // For PAID groups, add year sub-grouping based on start_date year
        foreach ($grouped as &$group) {
            if ($group['type'] === 'PAID') {
                $years = [];
                foreach ($group['products'] as $product) {
                    $year = (int) date('Y', strtotime($product['start_date']));
                    if (!isset($years[$year])) {
                        $years[$year] = [];
                    }
                    $years[$year][] = $product;
                }
                ksort($years);
                $group['years'] = $years;
            }
        }

        return array_values($grouped);
    }

    public function applyFilters(): void
    {
        $filtered = collect($this->licenseRecords);

        if ($this->filterType !== 'all') {
            $filtered = $filtered->where('type', $this->filterType);
        }

        if ($this->filterStatus !== 'all') {
            $today = now()->startOfDay();
            $filtered = $filtered->filter(function ($record) use ($today) {
                $start = \Carbon\Carbon::parse($record['start_date'])->startOfDay();
                $end = \Carbon\Carbon::parse($record['end_date'])->endOfDay();
                $isActive = $today->between($start, $end);

                return $this->filterStatus === 'active' ? $isActive : !$isActive;
            });
        }

        if ($this->filterProduct !== 'all') {
            $filtered = $filtered->where('license_type', $this->filterProduct);
        }

        if ($this->filterStartDate) {
            $filtered = $filtered->filter(fn ($record) => $record['start_date'] >= $this->filterStartDate);
        }

        if ($this->filterEndDate) {
            $filtered = $filtered->filter(fn ($record) => $record['end_date'] <= $this->filterEndDate);
        }

        $this->groupedLicenseRecords = $this->getGroupedLicenseRecordsFrom($filtered->values()->toArray());
    }

    public function resetLicenseFilters(): void
    {
        $this->filterType = 'all';
        $this->filterStatus = 'all';
        $this->filterProduct = 'all';
        $this->filterStartDate = null;
        $this->filterEndDate = null;

        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();
    }

    public function openEditModal(int $licenseNo): void
    {
        // Find the license record by 'no'
        $record = collect($this->licenseRecords)->firstWhere('no', $licenseNo);

        if ($record) {
            $this->editingLicenseNo = $licenseNo;
            $this->editingLicenseType = $record['license_type'];
            $this->editForm = [
                'total_user' => $record['total_user'],
                'month' => $record['month'],
                'start_date' => $record['start_date'],
                'end_date' => $record['end_date'],
                'status' => $this->calculateStatus($record['start_date'], $record['end_date']),
            ];
            $this->showEditModal = true;
        }
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingLicenseNo = null;
        $this->editingLicenseType = '';
        $this->editForm = [
            'total_user' => '',
            'month' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
        ];
    }

    public function saveLicense(): void
    {
        // Validate the form
        $this->validate([
            'editForm.total_user' => 'required|integer|min:1',
            'editForm.month' => 'required|integer|min:1|max:36',
            'editForm.start_date' => 'required|date',
            'editForm.end_date' => 'required|date|after_or_equal:editForm.start_date',
            'editForm.status' => 'required|in:active,inactive',
        ]);

        // Find and update the license record
        foreach ($this->licenseRecords as $index => $record) {
            if ($record['no'] === $this->editingLicenseNo) {
                $this->licenseRecords[$index]['total_user'] = (int) $this->editForm['total_user'];
                $this->licenseRecords[$index]['month'] = (int) $this->editForm['month'];
                $this->licenseRecords[$index]['start_date'] = $this->editForm['start_date'];
                $this->licenseRecords[$index]['end_date'] = $this->editForm['end_date'];
                $this->licenseRecords[$index]['status'] = $this->editForm['status'];
                break;
            }
        }

        // Refresh grouped records
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();

        // Close the modal
        $this->closeEditModal();

        // Dispatch success notification
        $this->dispatch('notify', type: 'success', message: 'License updated successfully.');
    }

    protected function calculateStatus(string $startDate, string $endDate): string
    {
        $today = now()->startOfDay();
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();

        return $today->between($start, $end) ? 'active' : 'inactive';
    }

    // Selection mode methods
    public function enterSelectionMode(): void
    {
        $this->isSelectionMode = true;
        $this->selectedLicenseNos = [];
    }

    public function exitSelectionMode(): void
    {
        $this->isSelectionMode = false;
        $this->selectedLicenseNos = [];
    }

    public function toggleLicenseSelection(int $licenseNo): void
    {
        if (in_array($licenseNo, $this->selectedLicenseNos)) {
            $this->selectedLicenseNos = array_values(array_diff($this->selectedLicenseNos, [$licenseNo]));
        } else {
            $this->selectedLicenseNos[] = $licenseNo;
        }
    }

    public function toggleSelectAll(): void
    {
        $allNos = array_column($this->licenseRecords, 'no');
        if (count($this->selectedLicenseNos) === count($allNos)) {
            $this->selectedLicenseNos = [];
        } else {
            $this->selectedLicenseNos = $allNos;
        }
    }

    public function toggleGroupSelection(string $invoiceNo): void
    {
        $groupNos = collect($this->licenseRecords)
            ->where('invoice_no', $invoiceNo)
            ->pluck('no')
            ->toArray();

        $allSelected = count(array_intersect($this->selectedLicenseNos, $groupNos)) === count($groupNos);

        if ($allSelected) {
            $this->selectedLicenseNos = array_values(array_diff($this->selectedLicenseNos, $groupNos));
        } else {
            $this->selectedLicenseNos = array_values(array_unique(array_merge($this->selectedLicenseNos, $groupNos)));
        }
    }

    public function getSelectedLicenseDetails(): array
    {
        return collect($this->licenseRecords)
            ->whereIn('no', $this->selectedLicenseNos)
            ->map(function ($record) {
                $name = $record['license_type'];
                if (!empty($record['invoice_no'])) {
                    $name .= ' (' . $record['invoice_no'] . ')';
                }
                return [
                    'name' => $name,
                    'start_date' => $record['start_date'],
                    'end_date' => $record['end_date'],
                ];
            })
            ->toArray();
    }

    public function openBulkEditModal(): void
    {
        // Validate selection
        if (empty($this->selectedLicenseNos)) {
            $this->dispatch('notify', type: 'error', message: 'Please select at least one license to edit.');
            return;
        }

        // Reset form and checkboxes
        $this->bulkEditForm = [
            'total_user' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
        ];
        $this->bulkEditEnabled = [
            'total_user' => false,
            'start_date' => false,
            'end_date' => false,
            'status' => false,
        ];
        $this->showBulkEditModal = true;
    }

    public function closeBulkEditModal(): void
    {
        $this->showBulkEditModal = false;
        $this->bulkEditForm = [
            'total_user' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
        ];
        $this->bulkEditEnabled = [
            'total_user' => false,
            'start_date' => false,
            'end_date' => false,
            'status' => false,
        ];
    }

    public function saveBulkEdit(): void
    {
        // Check if at least one field is enabled
        $hasEnabledField = in_array(true, $this->bulkEditEnabled, true);
        if (!$hasEnabledField) {
            $this->dispatch('notify', type: 'error', message: 'Please select at least one field to update.');
            return;
        }

        // Build validation rules only for enabled fields
        $rules = [];
        if ($this->bulkEditEnabled['total_user']) {
            $rules['bulkEditForm.total_user'] = 'required|integer|min:1';
        }
        if ($this->bulkEditEnabled['start_date']) {
            $rules['bulkEditForm.start_date'] = 'required|date';
        }
        if ($this->bulkEditEnabled['end_date']) {
            $rules['bulkEditForm.end_date'] = 'required|date';
            if ($this->bulkEditEnabled['start_date']) {
                $rules['bulkEditForm.end_date'] .= '|after_or_equal:bulkEditForm.start_date';
            }
        }
        if ($this->bulkEditEnabled['status']) {
            $rules['bulkEditForm.status'] = 'required|in:active,inactive';
        }

        if (!empty($rules)) {
            $this->validate($rules);
        }

        // Update only selected license records with enabled fields
        $updatedCount = 0;
        foreach ($this->licenseRecords as $index => $record) {
            // Skip if this license is not selected
            if (!in_array($record['no'], $this->selectedLicenseNos)) {
                continue;
            }

            if ($this->bulkEditEnabled['total_user']) {
                $this->licenseRecords[$index]['total_user'] = (int) $this->bulkEditForm['total_user'];
            }
            if ($this->bulkEditEnabled['start_date']) {
                $this->licenseRecords[$index]['start_date'] = $this->bulkEditForm['start_date'];
            }
            if ($this->bulkEditEnabled['end_date']) {
                $this->licenseRecords[$index]['end_date'] = $this->bulkEditForm['end_date'];
            }
            if ($this->bulkEditEnabled['status']) {
                $this->licenseRecords[$index]['status'] = $this->bulkEditForm['status'];
            }
            $updatedCount++;
        }

        // Refresh grouped records
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();

        // Close the modal and exit selection mode
        $this->closeBulkEditModal();
        $this->exitSelectionMode();

        // Dispatch success notification
        $this->dispatch('notify', type: 'success', message: "Successfully updated {$updatedCount} license(s).");
    }

    public function showProformaInvoice(string $invoiceNo): void
    {
        $this->selectedInvoiceNo = $invoiceNo;
        $this->piData = [];
        $this->apiPiData = [];
        $this->piLoading = true;
        $this->piError = null;
        $this->showPiModal = true;

        // Get the software handover record
        $softwareHandover = $this->companyData['software_handover'] ?? null;

        if (!$softwareHandover) {
            $this->piLoading = false;
            $this->piError = 'Software handover record not found.';
            return;
        }

        // Get hr_account_id and hr_company_id for API call
        $accountId = $softwareHandover->hr_account_id ?? null;
        $companyId = $softwareHandover->hr_company_id ?? null;

        // Primary: Build PI from license records (includes all years)
        $this->buildPiFromLicenseRecords($invoiceNo);

        // Fallback: If no local license records matched, try API
        if (empty($this->apiPiData)) {
            if ($accountId && $companyId) {
                try {
                    $apiService = app(CRMApiService::class);
                    $response = $apiService->getProformaInvoiceDetails($accountId, $companyId, $invoiceNo);

                    if ($response['success'] && !empty($response['data'])) {
                        $this->apiPiData = $response['data'];

                        // Store PI data in session for the full page view
                        $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $invoiceNo;
                        session()->put($sessionKey, $this->apiPiData);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to fetch PI from API: ' . $e->getMessage());
                }
            }
        }

        // Fallback 2: Local quotations
        if (empty($this->piData) && empty($this->apiPiData)) {
            $this->loadLocalQuotations($softwareHandover, $invoiceNo);
        }

        // Store PI data in session for the full page view
        if (!empty($this->apiPiData)) {
            $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $invoiceNo;
            session()->put($sessionKey, $this->apiPiData);
        }

        $this->piLoading = false;
    }

    protected function buildPiFromLicenseRecords(string $invoiceNo): void
    {
        // Find license records matching this invoice_no
        $matchingLicenses = collect($this->licenseRecords)
            ->where('invoice_no', $invoiceNo)
            ->values()
            ->toArray();

        if (empty($matchingLicenses)) {
            return;
        }

        // Get company info
        $companyName = $this->companyData['company_name'] ?? '-';
        $companyEmail = $this->companyData['email'] ?? '-';
        $companyAddress = $this->companyData['address'] ?? '-';

        // Build items array
        $items = [];
        $subtotal = 0;

        foreach ($matchingLicenses as $license) {
            $qty = $license['total_user'] ?? $license['unit'] ?? 0;
            $month = $license['month'] ?? 12;
            $startDate = $license['start_date'] ?? '';
            $endDate = $license['end_date'] ?? '';

            // Calculate price per user per month (approximate)
            // Typical pricing: TA=2.00, Leave=1.00, Claim=1.00, Payroll=1.00, Profile=0.50
            $pricePerUser = $this->getLicensePrice($license['license_type'] ?? '');
            $amount = $qty * $pricePerUser * $month;
            $subtotal += $amount;

            $period = '';
            if ($startDate && $endDate) {
                $period = date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));
            }

            $items[] = [
                'year' => (int) date('Y', strtotime($startDate)),
                'description' => ($license['license_type'] ?? 'TimeTec License') . ' (1 User License)',
                'period' => $period,
                'qty' => $qty,
                'price' => $pricePerUser,
                'billing_cycle' => $month,
                'discount' => '0%',
                'amount' => $amount,
            ];
        }

        // Calculate totals
        $discount = 0;
        $sstRate = 8;
        $sst = $subtotal * ($sstRate / 100);
        $totalAmount = $subtotal + $sst;

        // Get date from first license
        $invoiceDate = $matchingLicenses[0]['start_date'] ?? date('Y-m-d');

        // Build API-like PI data structure
        $this->apiPiData = [
            'invoice_no' => $invoiceNo,
            'date' => date('d-m-Y', strtotime($invoiceDate)),
            'status' => strtoupper($matchingLicenses[0]['type'] ?? 'PAID') === 'PAID' ? 'PAID' : 'Pending',
            'trx_rate' => '1',
            'currency' => 'MYR',
            'bill_to' => [
                'company_name' => $companyName,
                'email' => $companyEmail,
                'registration_no' => '',
                'address' => $companyAddress,
            ],
            'items' => $items,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'sst_rate' => $sstRate,
            'sst' => $sst,
            'total_amount' => $totalAmount,
            'amount_due' => $totalAmount,
        ];

        // Store PI data in session for the full page view
        $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $invoiceNo;
        session()->put($sessionKey, $this->apiPiData);
    }

    protected function getLicensePrice(string $licenseType): float
    {
        // Standard pricing per user per month
        $pricing = [
            'TimeTec TA' => 2.00,
            'TimeTec Attendance' => 2.00,
            'TimeTec Leave' => 1.00,
            'TimeTec Claim' => 1.00,
            'TimeTec Payroll' => 1.00,
            'TimeTec Profile' => 0.50,
            'TimeTec Hire' => 1.00,
        ];

        foreach ($pricing as $key => $price) {
            if (stripos($licenseType, $key) !== false) {
                return $price;
            }
        }

        return 1.00; // Default price
    }

    protected function loadLocalQuotations($softwareHandover, string $invoiceNo): void
    {
        $quotationIds = [];

        // Helper function to extract quotation IDs from JSON data with flexible key names
        $extractQuotationIds = function ($data, $targetInvoiceNo) {
            $ids = [];
            if (!is_array($data)) {
                return $ids;
            }

            foreach ($data as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $itemInvoiceNo = $item['invoice_number']
                    ?? $item['invoice_no']
                    ?? $item['invoiceNo']
                    ?? $item['inv_no']
                    ?? $item['tt_invoice_number']
                    ?? null;

                $quotationId = $item['quotation_id']
                    ?? $item['quotationId']
                    ?? $item['pi_id']
                    ?? $item['id']
                    ?? null;

                if ($itemInvoiceNo === $targetInvoiceNo && $quotationId) {
                    $ids[] = $quotationId;
                }
            }

            return $ids;
        };

        // Search through type_1, type_2, type_3 PI invoice data
        $jsonFields = ['type_1_pi_invoice_data', 'type_2_pi_invoice_data', 'type_3_pi_invoice_data'];

        foreach ($jsonFields as $field) {
            $data = $softwareHandover->$field;
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            if (is_array($data)) {
                $foundIds = $extractQuotationIds($data, $invoiceNo);
                $quotationIds = array_merge($quotationIds, $foundIds);
            }
        }

        // Include quotations from proforma_invoice_product and proforma_invoice_hrdf
        $productPiIds = is_string($softwareHandover->proforma_invoice_product)
            ? json_decode($softwareHandover->proforma_invoice_product, true)
            : $softwareHandover->proforma_invoice_product;

        if (is_array($productPiIds)) {
            $productPiIds = array_filter($productPiIds, fn($id) => is_numeric($id));
            $quotationIds = array_merge($quotationIds, $productPiIds);
        }

        $hrdfPiIds = is_string($softwareHandover->proforma_invoice_hrdf)
            ? json_decode($softwareHandover->proforma_invoice_hrdf, true)
            : $softwareHandover->proforma_invoice_hrdf;

        if (is_array($hrdfPiIds)) {
            $hrdfPiIds = array_filter($hrdfPiIds, fn($id) => is_numeric($id));
            $quotationIds = array_merge($quotationIds, $hrdfPiIds);
        }

        // If no quotations found, search by lead_id
        if (empty($quotationIds)) {
            $leadId = $softwareHandover->lead_id ?? null;
            if ($leadId) {
                $quotationIds = Quotation::where('lead_id', $leadId)
                    ->pluck('id')
                    ->toArray();
            }
        }

        $quotationIds = array_unique(array_filter($quotationIds));

        if (!empty($quotationIds)) {
            $quotations = Quotation::with(['items', 'lead.companyDetail', 'sales_person'])
                ->whereIn('id', $quotationIds)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($quotations as $quotation) {
                $this->piData[] = [
                    'id' => $quotation->id,
                    'pi_reference_no' => $quotation->pi_reference_no ?? 'PI-' . str_pad($quotation->id, 6, '0', STR_PAD_LEFT),
                    'company_name' => $quotation->lead?->companyDetail?->company_name ?? '-',
                    'quotation_date' => $quotation->quotation_date ? $quotation->quotation_date->format('Y-m-d') : '-',
                    'currency' => $quotation->currency ?? 'MYR',
                    'salesperson' => $quotation->sales_person?->name ?? '-',
                    'total_amount' => $quotation->items?->sum('amount') ?? 0,
                    'items' => $quotation->items?->map(function ($item) {
                        return [
                            'description' => $item->description ?? '-',
                            'quantity' => $item->quantity ?? 0,
                            'unit_price' => $item->unit_price ?? 0,
                            'amount' => $item->amount ?? 0,
                        ];
                    })->toArray() ?? [],
                ];
            }
        }
    }

    public function closePiModal(): void
    {
        $this->showPiModal = false;
        $this->selectedInvoiceNo = null;
        $this->piData = [];
        $this->apiPiData = [];
        $this->piLoading = false;
        $this->piError = null;
    }

    public function getPiViewUrl(): string
    {
        if (!$this->softwareHandoverId || !$this->selectedInvoiceNo) {
            return '#';
        }

        // Store the PI data in session for the controller to retrieve
        $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $this->selectedInvoiceNo;
        session()->put($sessionKey, $this->apiPiData);

        return route('pdf.license-proforma-invoice', [
            'softwareHandover' => $this->softwareHandoverId,
            'invoiceNo' => $this->selectedInvoiceNo,
        ]);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-products-tab');
    }
}
