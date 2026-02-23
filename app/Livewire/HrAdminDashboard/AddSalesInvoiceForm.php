<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\HrSalesInvoice;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AddSalesInvoiceForm extends Component
{
    public ?int $softwareHandoverId = null;
    public ?int $quotationId = null;
    public ?string $invoiceNo = null;
    public string $mode = 'create'; // 'create' or 'edit'

    // Customer Section
    public string $selectedCustomer = '';
    public string $invoiceDate = '';
    public string $invoiceTitle = 'TimeTec License Purchase';
    public string $invoiceType = 'normal';
    public string $companyAddress = '';
    public string $mobilePhone = '';
    public string $billingInformation = '';

    // Dropdown options
    public array $customerOptions = [];
    public array $billingOptions = [];

    // Order Items
    public array $orderItems = [];
    public array $availableProducts = [];

    // Totals
    public $discountPercent = 0;
    public $taxPercent = 8;

    // Currency
    public string $currency = 'MYR';

    // Active license end date (for consolidate billing cycle)
    public ?string $activeLicenseEndDate = null;

    // Bulk Configuration
    public array $bulkProducts = [];
    public int $bulkUnits = 0;
    public float $bulkUnitPrice = 5.00;
    public string $bulkStartDate = '';
    public string $bulkBillingCycle = '12';
    public int $bulkYears = 1;

    // Pay By (visible only when account is under a Reseller/Distributor)
    public string $payBy = 'Subscriber';
    public bool $isUnderDealer = false;

    public ?string $returnUrl = null;

    public function mount(
        ?int $softwareHandoverId = null,
        ?int $quotationId = null,
        ?string $activeLicenseEndDate = null,
        ?string $prefillInvoiceNo = null,
        ?float $prefillTotal = null,
        ?string $prefillCurrency = null,
        ?string $prefillInvoiceDate = null,
        ?float $prefillTaxRate = null,
        ?string $prefillDescription = null,
        ?string $returnUrl = null,
    ): void {
        $this->returnUrl = $returnUrl;
        $this->softwareHandoverId = $softwareHandoverId;
        $this->quotationId = $quotationId;

        if ($this->quotationId) {
            $this->mode = 'edit';
            $this->loadExistingInvoice();
        } else {
            $this->mode = 'create';
            $this->loadCompanyData();

            // Use URL-provided active license end date if available (overrides DB value)
            if ($activeLicenseEndDate) {
                $this->activeLicenseEndDate = $activeLicenseEndDate;
            }

            // Pre-fill from dummy invoice data (when editing a dummy invoice)
            if ($prefillInvoiceNo !== null) {
                $this->mode = 'edit';
                $this->invoiceNo = $prefillInvoiceNo;
                $this->invoiceDate = $prefillInvoiceDate
                    ? Carbon::parse($prefillInvoiceDate)->format('Y-m-d')
                    : Carbon::today()->format('Y-m-d');
                $this->currency = $prefillCurrency ?? 'MYR';
                $this->taxPercent = $prefillTaxRate ?? 0;

                $this->initializeOrderItems();

                // Try to load all line items from the HrSalesInvoice record
                if (!$this->loadFromSalesInvoice($prefillInvoiceNo)) {
                    // Fallback: single-item prefill (legacy behavior)
                    $this->prefillFirstItem(
                        $prefillDescription ?? 'TimeTec License Purchase',
                        $prefillTotal ?? 0,
                    );
                }
            } else {
                $this->invoiceDate = Carbon::today()->format('Y-m-d');
                $this->initializeOrderItems();
            }
        }
    }

    protected function loadCompanyData(): void
    {
        if (!$this->softwareHandoverId) {
            return;
        }

        $sw = SoftwareHandover::with(['lead.companyDetail'])->find($this->softwareHandoverId);
        if (!$sw) {
            return;
        }

        // Check if this account is under a Reseller or Distributor
        $this->isUnderDealer = !empty($sw->reseller_id);

        $hrLicense = HrLicense::where('software_handover_id', $this->softwareHandoverId)->first();

        // Store active license end date for consolidate billing cycle
        $this->activeLicenseEndDate = $hrLicense?->end_date
            ? Carbon::parse($hrLicense->end_date)->format('Y-m-d')
            : null;

        $companyName = $hrLicense?->company_name ?? $sw->company_name ?? 'Unknown Company';
        $hrAccountId = $sw->hr_account_id ?? '';
        $companyDetail = $sw->lead?->companyDetail;

        // Pre-fill customer dropdown
        $customerLabel = $hrAccountId ? ($hrAccountId . '-' . $companyName) : $companyName;
        $this->selectedCustomer = $customerLabel;
        $this->customerOptions = [
            $customerLabel => $customerLabel,
        ];

        // Pre-fill address
        $this->companyAddress = $companyDetail?->address ?? '';

        // Pre-fill mobile phone
        $this->mobilePhone = $companyDetail?->mobile_phone ?? $companyDetail?->phone ?? '';

        // Build billing information
        $email = $companyDetail?->email ?? '';
        $phone = $companyDetail?->phone ?? $companyDetail?->mobile_phone ?? '';
        $country = $companyDetail?->country ?? 'Malaysia';
        $billingLabel = implode(' | ', array_filter([
            $companyName,
            $email,
            $phone,
            $companyName,
            $country,
        ]));
        $this->billingInformation = $billingLabel;
        $this->billingOptions = [
            $billingLabel => $billingLabel,
        ];
    }

    protected function loadExistingInvoice(): void
    {
        $quotation = Quotation::with(['items'])->find($this->quotationId);
        if (!$quotation) {
            return;
        }

        $this->invoiceNo = $quotation->quotation_reference_no;

        // Derive softwareHandoverId from lead if not provided
        if (!$this->softwareHandoverId && $quotation->lead_id) {
            $sw = SoftwareHandover::where('lead_id', $quotation->lead_id)->first();
            $this->softwareHandoverId = $sw?->id;
        }

        // Load company data for customer/billing dropdowns
        $this->loadCompanyData();

        // Populate header fields from quotation
        $this->invoiceDate = $quotation->quotation_date
            ? Carbon::parse($quotation->quotation_date)->format('Y-m-d')
            : Carbon::today()->format('Y-m-d');
        $this->currency = $quotation->currency ?? 'MYR';
        $this->taxPercent = $quotation->tax_rate ?? 8;

        // Initialize 5 default empty product rows
        $this->initializeOrderItems();

        // Map saved QuotationDetail items back into orderItems
        foreach ($quotation->items->sortBy('sort_order') as $detail) {
            $itemData = [
                'item_name' => $detail->description,
                'units' => $detail->quantity ?? 0,
                'unit_price' => (float) ($detail->unit_price ?? 5.00),
                'currency' => $this->currency,
                'license_start_date' => $detail->license_start_date
                    ? Carbon::parse($detail->license_start_date)->format('Y-m-d') : '',
                'license_end_date' => $detail->license_end_date
                    ? Carbon::parse($detail->license_end_date)->format('Y-m-d') : '',
                'billing_cycle' => (string) ($detail->subscription_period ?? 1),
                'discount' => (float) ($detail->discount ?? 0),
                'total_price' => (float) ($detail->total_before_tax ?? 0),
            ];

            // Try to match to one of the 5 standard product slots by name
            $matchedIndex = null;
            foreach ($this->orderItems as $i => $row) {
                if ($row['item_name'] === $detail->description && (int) $row['units'] === 0) {
                    $matchedIndex = $i;
                    break;
                }
            }

            if ($matchedIndex !== null) {
                $this->orderItems[$matchedIndex] = $itemData;
            } else {
                // No name match — use first empty slot (units === 0)
                $emptyIndex = null;
                foreach ($this->orderItems as $i => $row) {
                    if ((int) ($row['units'] ?? 0) === 0) {
                        $emptyIndex = $i;
                        break;
                    }
                }

                if ($emptyIndex !== null) {
                    $this->orderItems[$emptyIndex] = $itemData;
                } else {
                    $this->orderItems[] = $itemData;
                }
            }
        }

        $this->recalculateItemTotals();
    }

    protected function initializeOrderItems(): void
    {
        $products = [
            ['name' => 'TimeTec Attendance', 'unit_price' => 5.00],
            ['name' => 'TimeTec Leave', 'unit_price' => 5.00],
            ['name' => 'TimeTec Claim', 'unit_price' => 5.00],
            ['name' => 'TimeTec Payroll', 'unit_price' => 5.00],
            ['name' => 'TimeTec Appraisal', 'unit_price' => 5.00],
        ];

        $this->availableProducts = $products;

        $today = Carbon::today();
        $todayFormatted = $today->format('Y-m-d');
        // Default end date: start date + 1 month - 1 day (for default billing cycle of 1 month)
        $endDateFormatted = $today->copy()->addMonth()->subDay()->format('Y-m-d');

        $this->orderItems = [];
        foreach ($products as $product) {
            $this->orderItems[] = [
                'item_name' => $product['name'],
                'units' => 0,
                'unit_price' => $product['unit_price'],
                'currency' => 'MYR',
                'license_start_date' => $todayFormatted,
                'license_end_date' => $endDateFormatted,
                'billing_cycle' => '1',
                'discount' => 0,
                'total_price' => 0.00,
            ];
        }
    }

    protected function prefillFirstItem(string $description, float $total): void
    {
        if (empty($this->orderItems)) {
            return;
        }

        $this->orderItems[0]['item_name'] = $description;
        $this->orderItems[0]['units'] = 1;
        $this->orderItems[0]['unit_price'] = $total;
        $this->orderItems[0]['currency'] = $this->currency;
        $this->orderItems[0]['billing_cycle'] = '1';
        $this->orderItems[0]['discount'] = 0;
        $this->orderItems[0]['total_price'] = $total;
    }

    /**
     * Load order items from an HrSalesInvoice record's line_items JSON.
     * Returns true if line items were found and loaded, false otherwise.
     */
    protected function loadFromSalesInvoice(string $invoiceNo): bool
    {
        $salesInvoice = HrSalesInvoice::where('invoice_no', $invoiceNo)->first();

        if (!$salesInvoice || empty($salesInvoice->line_items)) {
            return false;
        }

        // Map line_items license_type to orderItems item_name
        $nameMap = [
            'TimeTec TA' => 'TimeTec Attendance',
            'TimeTec Attendance' => 'TimeTec Attendance',
            'TimeTec Leave' => 'TimeTec Leave',
            'TimeTec Claim' => 'TimeTec Claim',
            'TimeTec Payroll' => 'TimeTec Payroll',
            'TimeTec Appraisal' => 'TimeTec Appraisal',
        ];

        foreach ($salesInvoice->line_items as $lineItem) {
            $licenseType = $lineItem['license_type'] ?? '';
            $itemName = $nameMap[$licenseType] ?? $licenseType;

            $units = (int) ($lineItem['total_user'] ?? 0);
            $unitPrice = (float) ($lineItem['unit_price'] ?? 5.00);
            $billingCycle = (string) ($lineItem['month'] ?? 1);
            $startDate = $lineItem['start_date'] ?? Carbon::today()->format('Y-m-d');
            $endDate = $lineItem['end_date'] ?? Carbon::parse($startDate)->addMonths((int) $billingCycle)->subDay()->format('Y-m-d');

            $itemData = [
                'item_name' => $itemName,
                'units' => $units,
                'unit_price' => $unitPrice,
                'currency' => $this->currency,
                'license_start_date' => $startDate,
                'license_end_date' => $endDate,
                'billing_cycle' => $billingCycle,
                'discount' => 0,
                'total_price' => round($units * $unitPrice * (int) $billingCycle, 2),
            ];

            // Try to match to an existing default row by item_name (with units === 0)
            $matchedIndex = null;
            foreach ($this->orderItems as $i => $row) {
                if ($row['item_name'] === $itemName && (int) $row['units'] === 0) {
                    $matchedIndex = $i;
                    break;
                }
            }

            if ($matchedIndex !== null) {
                $this->orderItems[$matchedIndex] = $itemData;
            } else {
                $this->orderItems[] = $itemData;
            }
        }

        $this->recalculateItemTotals();
        return true;
    }

    public function addItemRow(): void
    {
        // Use dates from the first existing item so new rows match the form's configured dates
        $firstItem = $this->orderItems[0] ?? null;
        $startDate = $firstItem['license_start_date'] ?? Carbon::today()->format('Y-m-d');
        $billingCycle = $firstItem['billing_cycle'] ?? '1';
        $endDate = Carbon::parse($startDate)->addMonths((int) $billingCycle)->subDay()->format('Y-m-d');

        $this->orderItems[] = [
            'item_name' => '',
            'units' => 0,
            'unit_price' => 5.00,
            'currency' => $firstItem['currency'] ?? 'MYR',
            'license_start_date' => $startDate,
            'license_end_date' => $endDate,
            'billing_cycle' => $billingCycle,
            'discount' => 0,
            'total_price' => 0.00,
        ];
    }

    public function updateItemProduct(int $index, string $productName): void
    {
        $product = collect($this->availableProducts)->firstWhere('name', $productName);
        if ($product) {
            $this->orderItems[$index]['unit_price'] = $product['unit_price'];
        }
        $this->recalculateItemTotals();
    }

    public function removeItemRow(int $index): void
    {
        if (count($this->orderItems) > 1) {
            array_splice($this->orderItems, $index, 1);
            $this->orderItems = array_values($this->orderItems);
            $this->recalculateItemTotals();
        }
    }

    protected function calculateConsolidateMonths(string $startDate): int
    {
        if (!$this->activeLicenseEndDate) {
            return 1;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($this->activeLicenseEndDate)->startOfDay();

        if ($end->lte($start)) {
            return 0;
        }

        // Count full months
        $months = 0;
        while ($start->copy()->addMonths($months + 1)->lte($end)) {
            $months++;
        }

        // Check remaining days after full months
        $afterFullMonths = $start->copy()->addMonths($months);
        $remainingDays = $afterFullMonths->diffInDays($end);

        if ($remainingDays >= 15) {
            $months++;
        }

        return $months;
    }

    public function updatedOrderItems(): void
    {
        $this->recalculateItemTotals();
        $this->recalculateEndDates();
    }

    public function recalculateItemTotals(): void
    {
        foreach ($this->orderItems as $index => $item) {
            $units = (float) ($item['units'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $billingCycle = $item['billing_cycle'] ?? '1';

            // For consolidate, calculate months dynamically
            $billingCycleMonths = $billingCycle === 'consolidate'
                ? $this->calculateConsolidateMonths($item['license_start_date'] ?? '')
                : (int) $billingCycle;

            // Sanitize discount: clamp between 0-100, round to 2 decimal places
            $discount = (float) ($item['discount'] ?? 0);
            $discount = max(0, min(100, $discount));
            $discount = round($discount, 2);
            $this->orderItems[$index]['discount'] = $discount;

            $subtotal = $units * $unitPrice * $billingCycleMonths;
            $discountAmount = $subtotal * ($discount / 100);
            $this->orderItems[$index]['total_price'] = round($subtotal - $discountAmount, 2);
        }
    }

    public function recalculateEndDates(): void
    {
        foreach ($this->orderItems as $index => $item) {
            $startDate = $item['license_start_date'] ?? '';
            $billingCycle = $item['billing_cycle'] ?? '1';

            if (!empty($startDate)) {
                try {
                    // Always compute consolidate months so the dropdown label stays up-to-date
                    if ($this->activeLicenseEndDate) {
                        $this->orderItems[$index]['consolidate_months'] = $this->calculateConsolidateMonths($startDate);
                    }

                    if ($billingCycle === 'consolidate' && $this->activeLicenseEndDate) {
                        $this->orderItems[$index]['license_end_date'] = $this->activeLicenseEndDate;
                    } else {
                        $billingCycleMonths = (int) $billingCycle;
                        $endDate = Carbon::parse($startDate)->addMonths($billingCycleMonths)->subDay()->format('Y-m-d');
                        $this->orderItems[$index]['license_end_date'] = $endDate;
                    }
                } catch (\Exception $e) {
                    $this->orderItems[$index]['license_end_date'] = '';
                }
            }
        }
    }

    public function updatedDiscountPercent($value): void
    {
        // Sanitize discount: clamp between 0-100, round to 2 decimal places
        $this->discountPercent = max(0, min(100, round((float) $value, 2)));
    }

    #[Computed]
    public function subtotal(): float
    {
        return round(collect($this->orderItems)->sum('total_price'), 2);
    }

    #[Computed]
    public function discountAmount(): float
    {
        return round($this->subtotal * ((float) $this->discountPercent / 100), 2);
    }

    #[Computed]
    public function subtotalAfterDiscount(): float
    {
        return round($this->subtotal - $this->discountAmount, 2);
    }

    #[Computed]
    public function taxAmount(): float
    {
        return round($this->subtotalAfterDiscount * ((float) $this->taxPercent / 100), 2);
    }

    #[Computed]
    public function totalInclTax(): float
    {
        return round($this->subtotalAfterDiscount + $this->taxAmount, 2);
    }

    #[Computed]
    public function grandTotal(): float
    {
        return $this->totalInclTax;
    }

    #[Computed]
    public function bulkConsolidateMonths(): int
    {
        $startDate = $this->bulkStartDate ?? now()->format('Y-m-d');
        return $this->calculateConsolidateMonths($startDate);
    }

    public function applyBulkConfig(): void
    {
        if (empty($this->bulkProducts) || empty($this->bulkStartDate) || $this->bulkUnits <= 0) {
            return;
        }

        $newItems = [];
        $isConsolidate = $this->bulkBillingCycle === 'consolidate';
        $billingCycleMonths = $isConsolidate
            ? $this->calculateConsolidateMonths($this->bulkStartDate)
            : (int) $this->bulkBillingCycle;

        for ($year = 0; $year < $this->bulkYears; $year++) {
            $yearStartDate = Carbon::parse($this->bulkStartDate)->addYears($year);

            if ($isConsolidate && $this->activeLicenseEndDate) {
                $yearEndDate = Carbon::parse($this->activeLicenseEndDate);
                $billingCycleMonths = $this->calculateConsolidateMonths($yearStartDate->format('Y-m-d'));
            } else {
                $yearEndDate = $yearStartDate->copy()->addMonths($billingCycleMonths)->subDay();
            }

            foreach ($this->bulkProducts as $productIndex) {
                $product = $this->availableProducts[$productIndex] ?? null;
                if (!$product) {
                    continue;
                }

                $subtotal = $this->bulkUnits * $this->bulkUnitPrice * $billingCycleMonths;

                $newItems[] = [
                    'item_name' => $product['name'],
                    'units' => $this->bulkUnits,
                    'unit_price' => $this->bulkUnitPrice,
                    'currency' => $this->currency,
                    'license_start_date' => $yearStartDate->format('Y-m-d'),
                    'license_end_date' => $yearEndDate->format('Y-m-d'),
                    'billing_cycle' => $isConsolidate ? 'consolidate' : (string) $billingCycleMonths,
                    'discount' => 0,
                    'total_price' => round($subtotal, 2),
                ];
            }
        }

        $this->orderItems = $newItems;
        $this->recalculateItemTotals();
    }

    public function createInvoice(): void
    {
        $this->validate([
            'selectedCustomer' => 'required|string',
            'invoiceDate' => 'required|date',
            'invoiceTitle' => 'required|string|max:255',
            'invoiceType' => 'required|in:normal,free_device_campaign',
            'companyAddress' => 'nullable|string',
            'mobilePhone' => 'nullable|string',
            'billingInformation' => 'required|string',
        ], [
            'selectedCustomer.required' => 'Please select a customer.',
            'invoiceDate.required' => 'Invoice date is required.',
            'invoiceTitle.required' => 'Invoice title is required.',
            'invoiceType.required' => 'Please select an invoice type.',
            'billingInformation.required' => 'Please select billing information.',
        ]);

        // Ensure at least one item has units > 0
        $hasItems = collect($this->orderItems)->contains(fn($item) => ($item['units'] ?? 0) > 0);
        if (!$hasItems) {
            $this->dispatch('notify', type: 'error', message: 'Please add at least one item with units greater than 0.');
            return;
        }

        try {
            // Get lead_id from SoftwareHandover
            $sw = SoftwareHandover::find($this->softwareHandoverId);
            $leadId = $sw?->lead_id;

            // Create the Quotation record
            $quotation = Quotation::create([
                'lead_id' => $leadId,
                'quotation_date' => $this->invoiceDate,
                'quotation_type' => 'product',
                'currency' => $this->orderItems[0]['currency'] ?? 'MYR',
                'sales_type' => 'NEW SALES',
                'hrdf_status' => 'NON HRDF',
                'subscription_period' => 12,
                'status' => 'new',
                'tax_rate' => (int) $this->taxPercent,
                'headcount' => collect($this->orderItems)->sum('units'),
            ]);

            // Create QuotationDetail records for items with units > 0
            $sortOrder = 1;
            foreach ($this->orderItems as $item) {
                $units = (int) ($item['units'] ?? 0);
                if ($units <= 0) {
                    continue;
                }

                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $billingCycleMonths = (int) ($item['billing_cycle'] ?? 1);
                $discount = (float) ($item['discount'] ?? 0);

                $totalBeforeTax = $units * $unitPrice * $billingCycleMonths;
                $discountAmount = $totalBeforeTax * ($discount / 100);
                $totalBeforeTax = $totalBeforeTax - $discountAmount;
                $taxAmount = $totalBeforeTax * ((float) $this->taxPercent / 100);
                $totalAfterTax = $totalBeforeTax + $taxAmount;

                QuotationDetail::create([
                    'quotation_id' => $quotation->id,
                    'description' => $item['item_name'],
                    'quantity' => $units,
                    'subscription_period' => $billingCycleMonths,
                    'license_start_date' => $item['license_start_date'] ?? null,
                    'license_end_date' => $item['license_end_date'] ?? null,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'taxation' => $taxAmount,
                    'total_before_tax' => $totalBeforeTax,
                    'total_after_tax' => $totalAfterTax,
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Redirect to view the created invoice
            session()->flash('notify', [
                'type' => 'success',
                'message' => 'Sales invoice created successfully.',
            ]);

            $this->redirect(
                url('/admin/view-sales-invoice?quotationId=' . $quotation->id . '&softwareHandoverId=' . $this->softwareHandoverId),
                navigate: false
            );
        } catch (\Exception $e) {
            Log::error('Failed to create sales invoice: ' . $e->getMessage(), [
                'softwareHandoverId' => $this->softwareHandoverId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'Failed to create invoice. Please try again.');
        }
    }

    public function submitInvoice(): void
    {
        if ($this->mode === 'edit') {
            $this->updateInvoice();
        } else {
            $this->createInvoice();
        }
    }

    public function updateInvoice(): void
    {
        $this->validate([
            'selectedCustomer' => 'required|string',
            'invoiceDate' => 'required|date',
            'invoiceTitle' => 'required|string|max:255',
            'invoiceType' => 'required|in:normal,free_device_campaign',
            'companyAddress' => 'nullable|string',
            'mobilePhone' => 'nullable|string',
            'billingInformation' => 'required|string',
        ], [
            'selectedCustomer.required' => 'Please select a customer.',
            'invoiceDate.required' => 'Invoice date is required.',
            'invoiceTitle.required' => 'Invoice title is required.',
            'invoiceType.required' => 'Please select an invoice type.',
            'billingInformation.required' => 'Please select billing information.',
        ]);

        $hasItems = collect($this->orderItems)->contains(fn($item) => ($item['units'] ?? 0) > 0);
        if (!$hasItems) {
            $this->dispatch('notify', type: 'error', message: 'Please add at least one item with units greater than 0.');
            return;
        }

        try {
            DB::transaction(function () {
                $quotation = Quotation::findOrFail($this->quotationId);

                // Update the Quotation record
                $quotation->update([
                    'quotation_date' => $this->invoiceDate,
                    'currency' => $this->orderItems[0]['currency'] ?? 'MYR',
                    'tax_rate' => (int) $this->taxPercent,
                    'headcount' => collect($this->orderItems)->sum('units'),
                ]);

                // Delete all existing detail rows, then re-create
                $quotation->items()->delete();

                $sortOrder = 1;
                foreach ($this->orderItems as $item) {
                    $units = (int) ($item['units'] ?? 0);
                    if ($units <= 0) {
                        continue;
                    }

                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $billingCycleMonths = (int) ($item['billing_cycle'] ?? 1);
                    $discount = (float) ($item['discount'] ?? 0);

                    $totalBeforeTax = $units * $unitPrice * $billingCycleMonths;
                    $discountAmount = $totalBeforeTax * ($discount / 100);
                    $totalBeforeTax = $totalBeforeTax - $discountAmount;
                    $taxAmount = $totalBeforeTax * ((float) $this->taxPercent / 100);
                    $totalAfterTax = $totalBeforeTax + $taxAmount;

                    QuotationDetail::create([
                        'quotation_id' => $quotation->id,
                        'description' => $item['item_name'],
                        'quantity' => $units,
                        'subscription_period' => $billingCycleMonths,
                        'license_start_date' => $item['license_start_date'] ?? null,
                        'license_end_date' => $item['license_end_date'] ?? null,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                        'taxation' => $taxAmount,
                        'total_before_tax' => $totalBeforeTax,
                        'total_after_tax' => $totalAfterTax,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            });

            session()->flash('notify', [
                'type' => 'success',
                'message' => 'Sales invoice updated successfully.',
            ]);

            $this->redirect(
                url('/admin/view-sales-invoice?quotationId=' . $this->quotationId . '&softwareHandoverId=' . $this->softwareHandoverId),
                navigate: false
            );
        } catch (\Exception $e) {
            Log::error('Failed to update sales invoice: ' . $e->getMessage(), [
                'quotationId' => $this->quotationId,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'Failed to update invoice. Please try again.');
        }
    }

    public function goBack(): void
    {
        if ($this->returnUrl) {
            $this->redirect($this->returnUrl, navigate: false);
        } elseif ($this->mode === 'edit' && $this->quotationId) {
            $this->redirect(
                url('/admin/view-sales-invoice?quotationId=' . $this->quotationId . '&softwareHandoverId=' . $this->softwareHandoverId),
                navigate: false
            );
        } else {
            $this->redirect(
                url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=products'),
                navigate: false
            );
        }
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.add-sales-invoice-form');
    }
}
