<?php

namespace App\Livewire\HrAdminDashboard;

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

        try {
            $accountId = $this->companyData['hr_account_id'] ?? null;
            $companyId = $this->companyData['hr_company_id'] ?? null;

            if (!$accountId || !$companyId) {
                $this->hasError = true;
                $this->errorMessage = 'Company backend IDs not available. Please ensure the handover is completed.';
                $this->isLoading = false;
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

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-invoice-tab');
    }
}
