<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\Quotation;
use App\Models\SoftwareHandover;
use App\Services\CRMApiService;
use Livewire\Component;

class CompanyProductsTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public array $productData = [];
    public array $licenseRecords = [];
    public array $groupedLicenseRecords = [];

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
        $this->loadProductData();
        $this->loadLicenseRecords();
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();
    }

    protected function loadProductData(): void
    {
        $hrLicense = $this->companyData['hr_license'] ?? null;
        $softwareHandover = $this->companyData['software_handover'] ?? null;

        // Get actual usage counts (from API later, use available data for now)
        $totalUsers = $hrLicense?->total_user ?? 0;

        // Build product data with active/inactive breakdown
        // Note: Actual active/inactive requires HR Backend API - using placeholders for now
        $this->productData = [
            'user_account' => [
                'total' => $totalUsers,
                'active' => $totalUsers,
                'inactive' => 0,
            ],
            'attendance_user' => [
                'total' => ($softwareHandover?->ta ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->ta ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'leave_user' => [
                'total' => ($softwareHandover?->tl ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->tl ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'claim_user' => [
                'total' => ($softwareHandover?->tc ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->tc ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'payroll_user' => [
                'total' => ($softwareHandover?->tp ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->tp ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'onboarding_offboarding' => [
                'total' => ($softwareHandover?->thire ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->thire ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'recruitment' => [
                'total' => ($softwareHandover?->thire ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->thire ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'appraisal' => [
                'total' => ($softwareHandover?->tapp ?? false) ? $totalUsers : 0,
                'active' => ($softwareHandover?->tapp ?? false) ? $totalUsers : 0,
                'inactive' => 0,
            ],
            'training' => [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
            ],
        ];
    }

    protected function loadLicenseRecords(): void
    {
        // Mock data matching "Koperasi Perbadanan Putrajaya Berhad" screenshot
        $this->licenseRecords = [
            [
                'no' => 1,
                'type' => 'TRIAL',
                'invoice_no' => '',
                'license_type' => 'TimeTec Profile',
                'unit' => 3,
                'user_limit' => 10,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 1,
                'start_date' => '2025-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 2,
                'type' => 'TRIAL',
                'invoice_no' => '',
                'license_type' => 'TimeTec TA',
                'unit' => 3,
                'user_limit' => 10,
                'total_user' => 28,
                'total_login' => 30,
                'total_terminal' => 150,
                'month' => 1,
                'start_date' => '2024-12-23',
                'end_date' => '2025-01-23',
                'status' => 'expired',
                'renewed' => '-',
            ],
            [
                'no' => 3,
                'type' => 'TRIAL',
                'invoice_no' => '',
                'license_type' => 'TimeTec Leave',
                'unit' => 3,
                'user_limit' => 10,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 1,
                'start_date' => '2024-12-23',
                'end_date' => '2025-01-23',
                'status' => 'expired',
                'renewed' => '-',
            ],
            [
                'no' => 4,
                'type' => 'TRIAL',
                'invoice_no' => '',
                'license_type' => 'TimeTec Claim',
                'unit' => 3,
                'user_limit' => 10,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 1,
                'start_date' => '2024-12-23',
                'end_date' => '2025-01-23',
                'status' => 'expired',
                'renewed' => '-',
            ],
            [
                'no' => 5,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec TA',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 28,
                'total_terminal' => 140,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 6,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Leave',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 7,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Claim',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 8,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 9,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec TA',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 28,
                'total_terminal' => 140,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 10,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Leave',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 11,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Claim',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
            [
                'no' => 12,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 1,
                'total_user' => 28,
                'total_login' => 0,
                'total_terminal' => 0,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
                'status' => 'active',
                'renewed' => '-',
            ],
        ];
    }

    protected function getGroupedLicenseRecords(): array
    {
        $grouped = [];

        foreach ($this->licenseRecords as $record) {
            // Group by invoice_no for PAID only; TRIAL licenses are individual (no grouping)
            if ($record['invoice_no']) {
                $key = $record['invoice_no'];
            } else {
                // Each TRIAL license gets its own row (no grouping)
                $key = 'TRIAL_' . $record['no'];
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'invoice_no' => $record['invoice_no'],
                    'type' => $record['type'],
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

        return array_values($grouped);
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

        // Try to fetch PI data from TimeTec Backend API
        if ($accountId && $companyId) {
            try {
                $apiService = app(CRMApiService::class);
                $response = $apiService->getProformaInvoiceDetails($accountId, $companyId, $invoiceNo);

                if ($response['success'] && !empty($response['data'])) {
                    // API data found - use it directly
                    $this->apiPiData = $response['data'];

                    // Store PI data in session for the full page view
                    $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $invoiceNo;
                    session()->put($sessionKey, $this->apiPiData);

                    $this->piLoading = false;
                    return;
                }
            } catch (\Exception $e) {
                // Log error but continue to fallback
                \Log::warning('Failed to fetch PI from API: ' . $e->getMessage());
            }
        }

        // Fallback 1: Search local quotations
        $this->loadLocalQuotations($softwareHandover, $invoiceNo);

        // Fallback 2: If still no data, build PI from license records
        if (empty($this->piData) && empty($this->apiPiData)) {
            $this->buildPiFromLicenseRecords($invoiceNo);
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
