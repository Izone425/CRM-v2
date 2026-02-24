<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\HrSalesInvoice;
use App\Models\Quotation;
use App\Models\SoftwareHandover;
use Livewire\Component;

class ViewSalesInvoice extends Component
{
    public ?int $quotationId = null;
    public ?int $softwareHandoverId = null;
    public ?string $invoiceNo = null;

    // Extra params for dummy invoices
    public ?float $paramTotal = null;
    public ?string $paramCurrency = null;
    public ?string $paramStatus = null;
    public ?string $paramInvoiceDate = null;
    public ?string $paramDueDate = null;

    // Navigation context
    public ?string $from = null;

    // Invoice data
    public array $invoice = [];
    public array $items = [];
    public bool $isLoading = true;
    public bool $hasError = false;
    public string $errorMessage = '';

    // Payment modal
    public bool $showPaymentModal = false;
    public array $paymentForm = [
        'company' => '',
        'currency' => 'MYR',
        'amount' => 0,
        'license_number' => '',
        'autocount_invoice' => '',
    ];

    // Cancel invoice modal
    public bool $showCancelModal = false;
    public array $cancelForm = [
        'doc_no' => '',
        'status' => 'cancelled',
        'remark' => '',
    ];
    public array $cancelRemarks = [];

    public function mount(
        ?int $quotationId = null,
        ?int $softwareHandoverId = null,
        ?string $invoiceNo = null,
        ?float $total = null,
        ?string $currency = null,
        ?string $status = null,
        ?string $invoiceDate = null,
        ?string $dueDate = null,
        ?string $from = null,
    ): void {
        $this->quotationId = $quotationId;
        $this->softwareHandoverId = $softwareHandoverId;
        $this->invoiceNo = $invoiceNo;
        $this->paramTotal = $total;
        $this->paramCurrency = $currency;
        $this->paramStatus = $status;
        $this->paramInvoiceDate = $invoiceDate;
        $this->paramDueDate = $dueDate;
        $this->from = $from;

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
                $period = null;
                if ($item->license_start_date && $item->license_end_date) {
                    $period = date('d/m/Y', strtotime($item->license_start_date)) . ' - ' . date('d/m/Y', strtotime($item->license_end_date));
                }

                $itemData = [
                    'description' => $item->description,
                    'quantity' => $item->quantity ?? 0,
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'subscription_period' => $item->subscription_period ?? 1,
                    'discount' => (float) ($item->discount ?? 0),
                    'total_before_tax' => (float) ($item->total_before_tax ?? 0),
                    'taxation' => (float) ($item->taxation ?? 0),
                    'total_after_tax' => (float) ($item->total_after_tax ?? 0),
                    'period' => $period,
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
        if ($this->from === 'expiring-invoices') {
            $this->redirect(url('/admin/hr-billing-expiring-invoices'), navigate: false);
        } elseif ($this->from === 'official-receipt') {
            $this->redirect(url('/admin/hr-billing-official-receipt'), navigate: false);
        } elseif ($this->from === 'payment') {
            $this->redirect(url('/admin/hr-billing-payment'), navigate: false);
        } elseif ($this->from === 'auto-renewal') {
            $this->redirect(url('/admin/hr-billing-auto-renewal'), navigate: false);
        } elseif ($this->from === 'billing') {
            $this->redirect(url('/admin/hr-billing-sales-invoice'), navigate: false);
        } elseif ($this->softwareHandoverId) {
            $tab = $this->from === 'products' ? 'products' : 'invoice';
            $this->redirect(
                url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=' . $tab),
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
                // Try loading from hr_sales_invoices table
                $salesInvoice = HrSalesInvoice::where('invoice_no', $this->invoiceNo)->first();
                if ($salesInvoice) {
                    $this->buildInvoiceFromSalesRecord($salesInvoice, $companyName, $companyAddress, $email);
                    return;
                }

                // If we have params from the invoice tab, build a simple invoice view
                if ($this->paramTotal !== null) {
                    $this->buildInvoiceFromParams($companyName, $companyAddress, $email);
                    return;
                }

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
                    'year' => (int) date('Y', strtotime($startDate)),
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
        // Mock license records - same data as CompanyProductsTab (3 years x 4 products)
        $allRecords = [
            // Year 2025
            [
                'no' => 5,
                'type' => 'PAID',
                'invoice_no' => 'TTC2412000246',
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
                'invoice_no' => 'TTC2412000246',
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
                'invoice_no' => 'TTC2412000246',
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
                'invoice_no' => 'TTC2412000246',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2025-01-24',
                'end_date' => '2026-01-23',
            ],
            // Year 2026
            [
                'no' => 9,
                'type' => 'PAID',
                'invoice_no' => 'TTC2412000246',
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
                'invoice_no' => 'TTC2412000246',
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
                'invoice_no' => 'TTC2412000246',
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
                'invoice_no' => 'TTC2412000246',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2026-01-24',
                'end_date' => '2027-01-23',
            ],
            // Year 2027
            [
                'no' => 13,
                'type' => 'PAID',
                'invoice_no' => 'TTC2412000246',
                'license_type' => 'TimeTec TA',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2027-01-24',
                'end_date' => '2028-01-23',
            ],
            [
                'no' => 14,
                'type' => 'PAID',
                'invoice_no' => 'TTC2412000246',
                'license_type' => 'TimeTec Leave',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2027-01-24',
                'end_date' => '2028-01-23',
            ],
            [
                'no' => 15,
                'type' => 'PAID',
                'invoice_no' => 'TTC2412000246',
                'license_type' => 'TimeTec Claim',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2027-01-24',
                'end_date' => '2028-01-23',
            ],
            [
                'no' => 16,
                'type' => 'PAID',
                'invoice_no' => 'TTC2412000246',
                'license_type' => 'TimeTec Payroll',
                'unit' => 28,
                'user_limit' => 10,
                'total_user' => 28,
                'month' => 12,
                'start_date' => '2027-01-24',
                'end_date' => '2028-01-23',
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

    protected function buildInvoiceFromSalesRecord(HrSalesInvoice $record, string $companyName, string $companyAddress, string $email): void
    {
        $total = (float) ($record->invoice_amount ?? $record->sales_amount ?? 0);
        $currency = $record->currency ?? 'MYR';
        $status = strtolower($record->status ?? 'pending');
        $invoiceDate = $record->invoice_date?->format('Y-m-d') ?? date('Y-m-d');

        $lineItems = $record->line_items;

        if (!empty($lineItems)) {
            // Build items from stored line_items JSON
            $this->items = [];
            $subtotal = 0;

            foreach ($lineItems as $item) {
                $qty = $item['total_user'] ?? 1;
                $month = $item['month'] ?? 12;
                $unitPrice = $item['unit_price'] ?? $this->getLicensePrice($item['license_type'] ?? '');
                $amount = $qty * $unitPrice * $month;
                $subtotal += $amount;
                $userLimit = $item['user_limit'] ?? 1;
                $startDate = $item['start_date'] ?? $invoiceDate;
                $endDate = $item['end_date'] ?? date('Y-m-d', strtotime($startDate . ' +1 year'));
                $period = date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));

                $this->items[] = [
                    'year' => (int) date('Y', strtotime($startDate)),
                    'description' => ($item['license_type'] ?? 'TimeTec License') . ' (' . $userLimit . ' User License)',
                    'period' => $period,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subscription_period' => $month,
                    'discount' => 0,
                    'total_before_tax' => $amount,
                ];
            }

            $total = $subtotal;
        } else {
            // Fallback: single generic item
            $this->items = [
                [
                    'description' => 'TimeTec License Purchase',
                    'period' => date('d/m/Y', strtotime($invoiceDate)) . ' - ' . date('d/m/Y', strtotime($invoiceDate . ' +1 year')),
                    'quantity' => 1,
                    'unit_price' => $total,
                    'subscription_period' => 1,
                    'discount' => 0,
                    'total_before_tax' => $total,
                ],
            ];
        }

        $this->invoice = [
            'id' => $record->id,
            'reference_no' => $record->invoice_no,
            'date' => date('d-m-Y', strtotime($invoiceDate)),
            'type' => 'product',
            'status' => $status === 'paid' ? 'paid' : ($status === 'cancel' ? 'cancelled' : 'pending'),
            'currency' => $currency,
            'tax_rate' => 0,
            'trx_rate' => $currency === 'USD' ? '4.1765' : '1.0000',
            'customer' => $record->company_name ?? $companyName,
            'address' => $companyAddress,
            'email' => $email,
            'subtotal' => round($total, 2),
            'discount' => 0,
            'taxable_amount' => round($total, 2),
            'tax_amount' => 0,
            'grand_total' => round($total, 2),
        ];

        $this->isLoading = false;
    }

    protected function buildInvoiceFromParams(string $companyName, string $companyAddress, string $email): void
    {
        $total = $this->paramTotal ?? 0;
        $currency = $this->paramCurrency ?? 'MYR';
        $status = strtolower($this->paramStatus ?? 'pending');
        $invoiceDate = $this->paramInvoiceDate ?? date('Y-m-d');

        $this->items = [
            [
                'description' => 'TimeTec Attendance (1 User License)',
                'period' => $invoiceDate
                    ? date('d/m/Y', strtotime($invoiceDate)) . ' - ' . ($this->paramDueDate ? date('d/m/Y', strtotime($this->paramDueDate)) : date('d/m/Y', strtotime($invoiceDate . ' +1 year')))
                    : '',
                'quantity' => 1,
                'unit_price' => $total,
                'subscription_period' => 1,
                'discount' => 0,
                'total_before_tax' => $total,
            ],
        ];

        $this->invoice = [
            'id' => null,
            'reference_no' => $this->invoiceNo,
            'date' => date('d-m-Y', strtotime($invoiceDate)),
            'type' => 'product',
            'status' => $status === 'paid' ? 'paid' : ($status === 'cancel' ? 'cancelled' : 'pending'),
            'currency' => $currency,
            'tax_rate' => 0,
            'trx_rate' => $currency === 'USD' ? '4.1765' : '1.0000',
            'customer' => $companyName,
            'address' => $companyAddress,
            'email' => $email,
            'subtotal' => round($total, 2),
            'discount' => 0,
            'taxable_amount' => round($total, 2),
            'tax_amount' => 0,
            'grand_total' => round($total, 2),
        ];

        $this->isLoading = false;
    }

    public function openPaymentModal(): void
    {
        // Build company display string
        $companyDisplay = $this->invoice['customer'] ?? 'Unknown Company';
        if ($this->softwareHandoverId) {
            $hrLicense = HrLicense::where('software_handover_id', $this->softwareHandoverId)->first();
            $hrAccountId = $hrLicense?->softwareHandover?->hr_account_id ?? '';
            if ($hrAccountId) {
                $companyDisplay = $hrAccountId . '-' . ($this->invoice['customer'] ?? 'Unknown Company');
            }
        }

        $this->paymentForm = [
            'company' => $companyDisplay,
            'currency' => $this->invoice['currency'] ?? 'MYR',
            'amount' => $this->invoice['grand_total'] ?? 0,
            'license_number' => $this->invoice['reference_no'] ?? '',
            'autocount_invoice' => '',
        ];

        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function submitPayment(): void
    {
        $this->validate([
            'paymentForm.company' => 'required',
            'paymentForm.currency' => 'required',
            'paymentForm.amount' => 'required|numeric|min:0.01',
            'paymentForm.license_number' => 'required',
            'paymentForm.autocount_invoice' => 'required|max:13',
        ], [
            'paymentForm.amount.required' => 'Total amount is required.',
            'paymentForm.amount.min' => 'Total amount must be greater than 0.',
            'paymentForm.license_number.required' => 'License number is required.',
            'paymentForm.autocount_invoice.required' => 'Autocount invoice is required.',
            'paymentForm.autocount_invoice.max' => 'Autocount invoice must not exceed 13 characters.',
        ]);

        // For now, just show success notification (no database persistence yet)
        $this->showPaymentModal = false;
        session()->flash('success', 'Payment receipt created successfully.');
    }

    public function openCancelModal(): void
    {
        $this->cancelForm = [
            'doc_no' => $this->invoice['reference_no'] ?? $this->invoiceNo ?? '',
            'status' => 'cancelled',
            'remark' => '',
        ];
        $this->cancelRemarks = [];
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function submitCancelInvoice(): void
    {
        $this->validate([
            'cancelForm.status' => 'required',
            'cancelForm.remark' => 'required|min:3',
        ], [
            'cancelForm.status.required' => 'Status is required.',
            'cancelForm.remark.required' => 'Please provide a reason for cancellation.',
            'cancelForm.remark.min' => 'Remark must be at least 3 characters.',
        ]);

        // For now, just show success notification (no database persistence yet)
        $this->showCancelModal = false;
        session()->flash('success', 'Invoice ' . ($this->cancelForm['doc_no'] ?? '') . ' has been cancelled successfully.');
    }

    public function copyPaymentLink(): void
    {
        $invoiceNo = $this->invoice['reference_no'] ?? $this->invoiceNo ?? '';

        if (empty($invoiceNo)) {
            return;
        }

        $aesKey = 'Epicamera@99';

        try {
            $invoiceDetail = \App\Models\CrmInvoiceDetail::where('f_invoice_no', $invoiceNo)->first();

            if ($invoiceDetail && $invoiceDetail->f_id) {
                $encrypted = openssl_encrypt($invoiceDetail->f_id, 'AES-128-ECB', $aesKey);
            } else {
                // Fallback for dummy invoices: encrypt the invoice reference number
                $encrypted = openssl_encrypt($invoiceNo, 'AES-128-ECB', $aesKey);
            }

            $encryptedBase64 = base64_encode($encrypted);
            $url = 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;

            $this->dispatch('payment-link-copied', url: $url);
        } catch (\Exception $e) {
            $this->dispatch('payment-link-copied', url: '', error: 'Failed to generate payment link.');
        }
    }

    public function editInvoice(): void
    {
        // Param-based (dummy) invoice: pass viewed data as prefill params
        if (!$this->quotationId && $this->paramTotal !== null) {
            // Build return URL so the Back button can navigate back here
            $returnParams = array_filter([
                'invoiceNo' => $this->invoiceNo,
                'softwareHandoverId' => $this->softwareHandoverId,
                'total' => $this->paramTotal,
                'currency' => $this->paramCurrency,
                'status' => $this->paramStatus,
                'invoiceDate' => $this->paramInvoiceDate,
                'dueDate' => $this->paramDueDate,
                'from' => $this->from,
            ]);

            $params = [
                'softwareHandoverId' => $this->softwareHandoverId,
                'prefillInvoiceNo' => $this->invoice['reference_no'] ?? $this->invoiceNo,
                'prefillTotal' => $this->invoice['grand_total'] ?? $this->paramTotal,
                'prefillCurrency' => $this->invoice['currency'] ?? $this->paramCurrency ?? 'MYR',
                'prefillInvoiceDate' => $this->paramInvoiceDate ?? date('Y-m-d'),
                'prefillTaxRate' => $this->invoice['tax_rate'] ?? 0,
                'prefillDescription' => $this->items[0]['description'] ?? 'TimeTec License Purchase',
                'returnUrl' => url('/admin/view-sales-invoice?' . http_build_query($returnParams)),
            ];

            $this->redirect(
                url('/admin/add-sales-invoice?' . http_build_query($params)),
                navigate: false
            );
            return;
        }

        // Invoice loaded from hr_sales_invoices (via buildInvoiceFromSalesRecord)
        if (!$this->quotationId && !empty($this->invoice['grand_total'])) {
            $returnParams = array_filter([
                'invoiceNo' => $this->invoiceNo,
                'softwareHandoverId' => $this->softwareHandoverId,
                'from' => $this->from,
            ]);

            $params = [
                'softwareHandoverId' => $this->softwareHandoverId,
                'prefillInvoiceNo' => $this->invoice['reference_no'] ?? $this->invoiceNo,
                'prefillTotal' => $this->invoice['grand_total'],
                'prefillCurrency' => $this->invoice['currency'] ?? 'MYR',
                'prefillInvoiceDate' => $this->invoice['date']
                    ? date('Y-m-d', strtotime($this->invoice['date']))
                    : date('Y-m-d'),
                'prefillTaxRate' => $this->invoice['tax_rate'] ?? 0,
                'prefillDescription' => $this->items[0]['description'] ?? 'TimeTec License Purchase',
                'returnUrl' => url('/admin/view-sales-invoice?' . http_build_query($returnParams)),
            ];

            $this->redirect(
                url('/admin/add-sales-invoice?' . http_build_query($params)),
                navigate: false
            );
            return;
        }

        // Try to resolve quotationId if not set (e.g. when loaded via invoiceNo from Invoice tab)
        if (!$this->quotationId && $this->softwareHandoverId) {
            $sw = SoftwareHandover::find($this->softwareHandoverId);
            if ($sw) {
                $query = Quotation::where('quotation_type', 'product');

                if ($sw->lead_id) {
                    $query->where('lead_id', $sw->lead_id);
                } else {
                    $query->whereNull('lead_id');
                }

                $quotation = $query->latest()->first();
                if ($quotation) {
                    $this->quotationId = $quotation->id;
                }
            }
        }

        // If still no quotation found, redirect to Add Sales Invoice (create new)
        if (!$this->quotationId) {
            $this->redirect(
                url('/admin/add-sales-invoice?softwareHandoverId=' . $this->softwareHandoverId),
                navigate: false
            );
            return;
        }

        $url = '/admin/edit-sales-invoice?quotationId=' . $this->quotationId;
        if ($this->softwareHandoverId) {
            $url .= '&softwareHandoverId=' . $this->softwareHandoverId;
        }

        $this->redirect(url($url), navigate: false);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.view-sales-invoice');
    }
}
