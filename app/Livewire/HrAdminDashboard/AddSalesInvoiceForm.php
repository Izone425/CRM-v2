<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AddSalesInvoiceForm extends Component
{
    public ?int $softwareHandoverId = null;

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

    // Totals
    public $discountPercent = 0;
    public $taxPercent = 8;

    public function mount(?int $softwareHandoverId = null): void
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->invoiceDate = Carbon::today()->format('Y-m-d');

        $this->loadCompanyData();
        $this->initializeOrderItems();
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

        $hrLicense = HrLicense::where('software_handover_id', $this->softwareHandoverId)->first();

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

    protected function initializeOrderItems(): void
    {
        $products = [
            ['name' => 'TimeTec Attendance (1 User License)', 'unit_price' => 5.00],
            ['name' => 'TimeTec Leave (1 User License)', 'unit_price' => 5.00],
            ['name' => 'TimeTec Claim (1 User License)', 'unit_price' => 5.00],
            ['name' => 'TimeTec Payroll (1 Payroll License)', 'unit_price' => 5.00],
            ['name' => 'TimeTec Appraisal (1 User License)', 'unit_price' => 5.00],
            ['name' => 'TimeTec Profile (1 User License)', 'unit_price' => 5.00],
        ];

        $this->orderItems = [];
        foreach ($products as $product) {
            $this->orderItems[] = [
                'item_name' => $product['name'],
                'units' => 0,
                'unit_price' => $product['unit_price'],
                'currency' => 'MYR',
                'license_start_date' => '',
                'license_end_date' => '',
                'billing_cycle' => '1',
                'discount' => 0,
                'total_price' => 0.00,
            ];
        }
    }

    public function updatedOrderItems(): void
    {
        $this->recalculateItemTotals();
    }

    public function recalculateItemTotals(): void
    {
        foreach ($this->orderItems as $index => $item) {
            $units = (float) ($item['units'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $billingCycleMonths = (int) ($item['billing_cycle'] ?? 1);
            $discount = (float) ($item['discount'] ?? 0);

            $subtotal = $units * $unitPrice * $billingCycleMonths;
            $discountAmount = $subtotal * ($discount / 100);
            $this->orderItems[$index]['total_price'] = round($subtotal - $discountAmount, 2);
        }
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

    public function createInvoice(): void
    {
        $this->validate([
            'selectedCustomer' => 'required|string',
            'invoiceDate' => 'required|date',
            'invoiceTitle' => 'required|string|max:255',
            'invoiceType' => 'required|in:normal,free_device_campaign',
            'mobilePhone' => 'required|string',
            'billingInformation' => 'required|string',
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
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'taxation' => $taxAmount,
                    'total_before_tax' => $totalBeforeTax,
                    'total_after_tax' => $totalAfterTax,
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Redirect back to company license details with success message
            session()->flash('notify', [
                'type' => 'success',
                'message' => 'Sales invoice created successfully.',
            ]);

            $this->redirect(
                url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId),
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

    public function goBack(): void
    {
        $this->redirect(
            url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId),
            navigate: false
        );
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.add-sales-invoice-form');
    }
}
