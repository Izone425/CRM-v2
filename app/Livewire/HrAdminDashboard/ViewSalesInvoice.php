<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\Quotation;
use App\Models\SoftwareHandover;
use Livewire\Component;

class ViewSalesInvoice extends Component
{
    public ?int $quotationId = null;
    public ?int $softwareHandoverId = null;
    public ?string $invoiceNo = null;

    // Invoice data
    public array $invoice = [];
    public array $items = [];
    public bool $isLoading = true;
    public bool $hasError = false;
    public string $errorMessage = '';

    public function mount(?int $quotationId = null, ?int $softwareHandoverId = null, ?string $invoiceNo = null): void
    {
        $this->quotationId = $quotationId;
        $this->softwareHandoverId = $softwareHandoverId;
        $this->invoiceNo = $invoiceNo;

        if ($this->quotationId) {
            $this->loadInvoice();
        } elseif ($this->invoiceNo && $this->softwareHandoverId) {
            $this->loadInvoiceByInvoiceNo();
        } else {
            $this->hasError = true;
            $this->errorMessage = 'No invoice specified.';
            $this->isLoading = false;
        }
    }

    public function loadInvoice(): void
    {
        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            $quotation = Quotation::with(['items', 'lead.companyDetail'])->find($this->quotationId);

            if (!$quotation) {
                $this->hasError = true;
                $this->errorMessage = 'Invoice not found.';
                $this->isLoading = false;
                return;
            }

            // Get company info
            $companyName = 'Unknown Company';
            $companyAddress = '';
            $mobilePhone = '';
            $email = '';

            if ($this->softwareHandoverId) {
                $sw = SoftwareHandover::with(['lead.companyDetail'])->find($this->softwareHandoverId);
                $hrLicense = HrLicense::where('software_handover_id', $this->softwareHandoverId)->first();

                $companyName = $hrLicense?->company_name ?? $sw?->company_name ?? 'Unknown Company';
                $companyDetail = $sw?->lead?->companyDetail;
                $companyAddress = $companyDetail?->address ?? '';
                $mobilePhone = $companyDetail?->mobile_phone ?? $companyDetail?->phone ?? '';
                $email = $companyDetail?->email ?? '';
            } elseif ($quotation->lead?->companyDetail) {
                $companyDetail = $quotation->lead->companyDetail;
                $companyName = $companyDetail->company_name ?? 'Unknown Company';
                $companyAddress = $companyDetail->address ?? '';
                $mobilePhone = $companyDetail->mobile_phone ?? $companyDetail->phone ?? '';
                $email = $companyDetail->email ?? '';
            }

            // Build invoice data
            $this->invoice = [
                'id' => $quotation->id,
                'reference_no' => $quotation->quotation_reference_no ?? 'INV-' . str_pad($quotation->id, 6, '0', STR_PAD_LEFT),
                'date' => $quotation->quotation_date?->format('d M Y') ?? '-',
                'type' => $quotation->quotation_type ?? 'product',
                'status' => $quotation->status?->value ?? 'new',
                'currency' => $quotation->currency ?? 'MYR',
                'tax_rate' => $quotation->tax_rate ?? 8,
                'headcount' => $quotation->headcount ?? 0,
                'customer' => $companyName,
                'address' => $companyAddress,
                'phone' => $mobilePhone,
                'email' => $email,
            ];

            // Build items
            $this->items = [];
            $subtotal = 0;

            foreach ($quotation->items as $item) {
                $itemData = [
                    'description' => $item->description,
                    'quantity' => $item->quantity ?? 0,
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'subscription_period' => $item->subscription_period ?? 1,
                    'discount' => (float) ($item->discount ?? 0),
                    'total_before_tax' => (float) ($item->total_before_tax ?? 0),
                    'taxation' => (float) ($item->taxation ?? 0),
                    'total_after_tax' => (float) ($item->total_after_tax ?? 0),
                ];
                $this->items[] = $itemData;
                $subtotal += $itemData['total_before_tax'];
            }

            // Calculate totals
            $taxAmount = $subtotal * ($this->invoice['tax_rate'] / 100);
            $grandTotal = $subtotal + $taxAmount;

            $this->invoice['subtotal'] = round($subtotal, 2);
            $this->invoice['tax_amount'] = round($taxAmount, 2);
            $this->invoice['grand_total'] = round($grandTotal, 2);

        } catch (\Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load invoice: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function goBack(): void
    {
        if ($this->softwareHandoverId) {
            $this->redirect(
                url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=products'),
                navigate: false
            );
        } else {
            $this->redirect(url('/admin/hr-license'), navigate: false);
        }
    }

    public function loadInvoiceByInvoiceNo(): void
    {
        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            $sw = SoftwareHandover::with(['lead.companyDetail'])->find($this->softwareHandoverId);

            if (!$sw) {
                $this->hasError = true;
                $this->errorMessage = 'Software handover record not found.';
                $this->isLoading = false;
                return;
            }

            // Get company info
            $hrLicense = HrLicense::where('software_handover_id', $this->softwareHandoverId)->first();
            $companyName = $hrLicense?->company_name ?? $sw->company_name ?? 'Unknown Company';
            $companyDetail = $sw->lead?->companyDetail;
            $companyAddress = $companyDetail?->address ?? '';
            $email = $companyDetail?->email ?? '';

            // Get license records for this invoice number (mock data - same as CompanyProductsTab)
            $licenseRecords = $this->getLicenseRecordsForInvoice($this->invoiceNo);

            if (empty($licenseRecords)) {
                $this->hasError = true;
                $this->errorMessage = 'No license records found for invoice: ' . $this->invoiceNo;
                $this->isLoading = false;
                return;
            }

            // Build items from license records
            $this->items = [];
            $subtotal = 0;
            $totalDiscount = 0;

            foreach ($licenseRecords as $index => $license) {
                $qty = $license['total_user'] ?? $license['unit'] ?? 0;
                $month = $license['month'] ?? 12;
                $startDate = $license['start_date'] ?? '';
                $endDate = $license['end_date'] ?? '';
                $pricePerUser = $this->getLicensePrice($license['license_type'] ?? '');
                $amount = $qty * $pricePerUser * $month;
                $subtotal += $amount;

                $period = '';
                if ($startDate && $endDate) {
                    $period = date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));
                }

                $userCount = $license['user_limit'] ?? 1;
                $description = ($license['license_type'] ?? 'TimeTec License') . ' (' . $userCount . ' User License)';

                $this->items[] = [
                    'description' => $description,
                    'period' => $period,
                    'quantity' => $qty,
                    'unit_price' => $pricePerUser,
                    'subscription_period' => $month,
                    'discount' => 0,
                    'total_before_tax' => $amount,
                ];
            }

            // Calculate totals
            $taxableAmount = $subtotal - $totalDiscount;
            $sstRate = 0; // SST rate (0% as shown in screenshot)
            $sst = $taxableAmount * ($sstRate / 100);
            $grandTotal = $taxableAmount + $sst;

            // Get date from first license
            $invoiceDate = $licenseRecords[0]['start_date'] ?? date('Y-m-d');
            $status = strtoupper($licenseRecords[0]['type'] ?? 'PAID') === 'PAID' ? 'paid' : 'pending';

            // Build invoice data
            $this->invoice = [
                'id' => null,
                'reference_no' => $this->invoiceNo,
                'date' => date('d-m-Y', strtotime($invoiceDate)),
                'type' => 'product',
                'status' => $status,
                'currency' => 'USD',
                'tax_rate' => $sstRate,
                'trx_rate' => '4.1765',
                'customer' => $companyName,
                'address' => $companyAddress,
                'email' => $email,
                'subtotal' => round($subtotal, 2),
                'discount' => round($totalDiscount, 2),
                'taxable_amount' => round($taxableAmount, 2),
                'tax_amount' => round($sst, 2),
                'grand_total' => round($grandTotal, 2),
            ];

        } catch (\Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load invoice: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function getLicenseRecordsForInvoice(string $invoiceNo): array
    {
        // Mock license records - same data as CompanyProductsTab
        $allRecords = [
            [
                'no' => 5,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec TA',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
            ],
            [
                'no' => 6,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Leave',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
            ],
            [
                'no' => 7,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Claim',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
            ],
            [
                'no' => 8,
                'type' => 'PAID',
                'invoice_no' => 'TT2412000246',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
            ],
            [
                'no' => 9,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec TA',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
            ],
            [
                'no' => 10,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Leave',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
            ],
            [
                'no' => 11,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Claim',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
            ],
            [
                'no' => 12,
                'type' => 'PAID',
                'invoice_no' => 'TT2601000335',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
            ],
        ];

        return array_values(array_filter($allRecords, fn($r) => $r['invoice_no'] === $invoiceNo));
    }

    protected function getLicensePrice(string $licenseType): float
    {
        $pricing = [
            'TimeTec TA' => 20.00,
            'TimeTec Attendance' => 20.00,
            'TimeTec Leave' => 20.00,
            'TimeTec Claim' => 20.00,
            'TimeTec Payroll' => 50.00,
            'TimeTec Profile' => 10.00,
            'TimeTec Hire' => 20.00,
        ];

        foreach ($pricing as $key => $price) {
            if (stripos($licenseType, $key) !== false) {
                return $price;
            }
        }

        return 20.00;
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.view-sales-invoice');
    }
}
