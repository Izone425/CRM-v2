<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\CrmHrdfInvoice;
use App\Models\HrLicense;
use App\Models\HrSalesInvoice;
use App\Models\Quotation;
use App\Models\SoftwareHandover;
use App\Services\CRMApiService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CompanyInvoiceTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];

    // State properties
    public bool $isLoading = true;
    public bool $hasError = false;
    public string $errorMessage = '';
    public bool $isLocalData = false;

    // Data properties
    public array $invoices = [];
    public int $totalRecords = 0;

    // Search & Pagination
    public string $search = '';
    public int $perPage = 10;
    public int $currentPage = 1;

    public array $perPageOptions = [10, 25, 50];

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadInvoices();
    }

    public function loadInvoices(): void
    {
        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';
        $this->isLocalData = false;

        try {
            $accountId = $this->companyData['hr_account_id'] ?? null;
            $companyId = $this->companyData['hr_company_id'] ?? null;

            if (!$accountId || !$companyId) {
                $this->loadInvoicesFromLocalData();
                return;
            }

            $crmService = app(CRMApiService::class);

            $params = [
                'page' => $this->currentPage,
                'limit' => $this->perPage,
            ];

            if (!empty($this->search)) {
                $params['search'] = $this->search;
            }

            $response = $crmService->getCompanyInvoices($accountId, $companyId, $params);

            if ($response['success']) {
                $this->invoices = $response['data']['invoices'] ?? [];
                $this->totalRecords = $response['data']['total_records'] ?? count($this->invoices);
            } else {
                $this->hasError = true;
                $this->errorMessage = $response['error'] ?? 'Failed to fetch invoices from backend.';
                Log::error('CRM API: Failed to fetch invoices', [
                    'account_id' => $accountId,
                    'company_id' => $companyId,
                    'error' => $response['error'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while fetching invoices: ' . $e->getMessage();
            Log::error('CRM API Exception: Failed to fetch invoices', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function searchInvoices(): void
    {
        $this->currentPage = 1;
        $this->loadInvoices();
    }

    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
        $this->loadInvoices();
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
        $this->loadInvoices();
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadInvoices();
        }
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->totalPages()) {
            $this->currentPage++;
            $this->loadInvoices();
        }
    }

    public function totalPages(): int
    {
        return max(1, ceil($this->totalRecords / $this->perPage));
    }

    public function refreshInvoices(): void
    {
        $this->loadInvoices();
    }

    public function getStatusColor(string $status): string
    {
        return match (strtolower($status)) {
            'paid' => 'text-green-600',
            'cancel', 'cancelled' => 'text-red-600',
            'pending' => 'text-yellow-600',
            default => 'text-gray-600',
        };
    }

    public function formatCurrency(float $amount, string $currency = 'MYR'): string
    {
        return number_format($amount, 2) . ' ' . $currency;
    }

    protected function loadInvoicesFromLocalData(): void
    {
        $this->isLocalData = true;

        try {
            $allInvoices = [];

            // First, load quotations (sales invoices created from AddSalesInvoiceForm)
            $quotationInvoices = $this->getQuotationInvoices();
            $allInvoices = array_merge($allInvoices, $quotationInvoices);

            // Load sales invoices from hr_sales_invoices table
            $hrSalesInvoices = $this->getHrSalesInvoices();
            $allInvoices = array_merge($allInvoices, $hrSalesInvoices);

            // Then try CrmHrdfInvoice table
            $query = CrmHrdfInvoice::where('handover_id', $this->softwareHandoverId)
                ->where('handover_type', 'SW');

            // Apply search filter if provided
            if (!empty($this->search)) {
                $query->where(function ($q) {
                    $q->where('invoice_no', 'like', '%' . $this->search . '%')
                      ->orWhere('company_name', 'like', '%' . $this->search . '%');
                });
            }

            $crmHrdfCount = $query->count();

            if ($crmHrdfCount > 0) {
                $localInvoices = $query->orderBy('invoice_date', 'desc')->get();

                // Map to expected format
                $crmInvoices = $localInvoices->map(function ($invoice) {
                    return [
                        'invoice_no' => $invoice->invoice_no,
                        'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                        'due_date' => null,
                        'description' => $invoice->company_name ?? 'TimeTec License',
                        'total' => (float) ($invoice->total_amount ?? 0),
                        'currency' => 'MYR',
                        'status' => 'Paid',
                        'quotation_id' => null,
                    ];
                })->toArray();

                $allInvoices = array_merge($allInvoices, $crmInvoices);
            }

            // If still no invoices, try HrLicense table
            if (empty($allInvoices)) {
                $hrLicenseInvoices = $this->getHrLicenseInvoices();
                $allInvoices = array_merge($allInvoices, $hrLicenseInvoices);
            }

            // Apply search filter to quotation invoices if needed
            if (!empty($this->search)) {
                $searchLower = strtolower($this->search);
                $allInvoices = array_filter($allInvoices, function ($invoice) use ($searchLower) {
                    return str_contains(strtolower($invoice['invoice_no'] ?? ''), $searchLower)
                        || str_contains(strtolower($invoice['description'] ?? ''), $searchLower);
                });
                $allInvoices = array_values($allInvoices);
            }

            // Sort by invoice_date descending
            usort($allInvoices, function ($a, $b) {
                return strtotime($b['invoice_date'] ?? '1970-01-01') - strtotime($a['invoice_date'] ?? '1970-01-01');
            });

            $this->totalRecords = count($allInvoices);

            // Apply pagination
            $offset = ($this->currentPage - 1) * $this->perPage;
            $this->invoices = array_slice($allInvoices, $offset, $this->perPage);

            if (empty($this->invoices)) {
                $this->hasError = true;
                $this->errorMessage = 'No invoice records found for this company.';
            }
        } catch (\Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load local invoice data: ' . $e->getMessage();
            Log::error('Failed to load local invoices', [
                'handover_id' => $this->softwareHandoverId,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    protected function getQuotationInvoices(): array
    {
        $invoices = [];

        if (!$this->softwareHandoverId) {
            return $invoices;
        }

        // Get lead_id from SoftwareHandover
        $sw = SoftwareHandover::find($this->softwareHandoverId);
        if (!$sw || !$sw->lead_id) {
            return $invoices;
        }

        // Get quotations for this lead
        $quotations = Quotation::with('items')
            ->where('lead_id', $sw->lead_id)
            ->orderBy('quotation_date', 'desc')
            ->get();

        foreach ($quotations as $quotation) {
            $descriptions = $quotation->items->pluck('description')->filter()->unique()->implode(', ');
            $totalAfterTax = $quotation->items->sum('total_after_tax');

            $invoices[] = [
                'invoice_no' => $quotation->quotation_reference_no ?? 'INV-' . str_pad($quotation->id, 6, '0', STR_PAD_LEFT),
                'invoice_date' => $quotation->quotation_date?->format('Y-m-d'),
                'due_date' => null,
                'description' => $descriptions ?: 'TimeTec License Purchase',
                'total' => round((float) $totalAfterTax, 2),
                'currency' => $quotation->currency ?? 'MYR',
                'status' => ucfirst($quotation->status?->value ?? 'new'),
                'quotation_id' => $quotation->id,
            ];
        }

        return $invoices;
    }

    protected function getHrSalesInvoices(): array
    {
        if (!$this->softwareHandoverId) {
            return [];
        }

        $records = HrSalesInvoice::where('software_handover_id', $this->softwareHandoverId)
            ->orderBy('invoice_date', 'desc')
            ->get();

        return $records->map(function ($record) {
            return [
                'invoice_no' => $record->invoice_no,
                'invoice_date' => $record->invoice_date?->format('Y-m-d'),
                'due_date' => null,
                'description' => 'TimeTec License Purchase',
                'total' => (float) ($record->invoice_amount ?? $record->sales_amount ?? 0),
                'currency' => $record->currency ?? 'MYR',
                'status' => ucfirst(strtolower($record->status)),
                'quotation_id' => null,
            ];
        })->toArray();
    }

    protected function getHrLicenseInvoices(): array
    {
        $allInvoices = [];

        // Get all licenses with invoice_no for this handover
        $query = HrLicense::where('software_handover_id', $this->softwareHandoverId)
            ->whereNotNull('invoice_no')
            ->where('invoice_no', '!=', '');

        $licenses = $query->get();
        $grouped = $licenses->groupBy('invoice_no');

        foreach ($grouped as $invoiceNo => $licenseGroup) {
            $firstLicense = $licenseGroup->first();
            $totalAmount = 0;
            $descriptions = [];

            foreach ($licenseGroup as $license) {
                $qty = $license->total_user ?? $license->unit ?? 0;
                $month = $license->month ?? 12;
                $pricePerUser = $this->getLicensePrice($license->license_type ?? '');
                $amount = $qty * $pricePerUser * $month;
                $totalAmount += $amount;
                $descriptions[] = $license->license_type;
            }

            // Add SST 8%
            $totalWithSst = $totalAmount * 1.08;

            $allInvoices[] = [
                'invoice_no' => $invoiceNo,
                'invoice_date' => $firstLicense->start_date?->format('Y-m-d') ?? $firstLicense->created_at?->format('Y-m-d'),
                'due_date' => null,
                'description' => implode(', ', array_unique($descriptions)),
                'total' => round($totalWithSst, 2),
                'currency' => 'MYR',
                'status' => 'Paid',
                'quotation_id' => null,
            ];
        }

        return $allInvoices;
    }

    public function viewInvoice(int $quotationId): void
    {
        $this->redirect(
            url('/admin/view-sales-invoice?quotationId=' . $quotationId . '&softwareHandoverId=' . $this->softwareHandoverId . '&from=invoice'),
            navigate: false
        );
    }

    public function viewInvoiceByNo(string $invoiceNo, float $total, string $currency, string $status, string $invoiceDate, ?string $dueDate = null): void
    {
        $this->redirect(
            url('/admin/view-sales-invoice?' . http_build_query([
                'invoiceNo' => $invoiceNo,
                'softwareHandoverId' => $this->softwareHandoverId,
                'total' => $total,
                'currency' => $currency,
                'status' => $status,
                'invoiceDate' => $invoiceDate,
                'dueDate' => $dueDate,
                'from' => 'invoice',
            ])),
            navigate: false
        );
    }

    protected function getLicensePrice(string $licenseType): float
    {
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

        return 1.00;
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-invoice-tab');
    }
}
