<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrOfficialReceipt;
use App\Models\HrSalesInvoice;
use Livewire\Component;

class ViewOfficialReceipt extends Component
{
    public ?string $orNo = null;
    public ?string $from = null;

    // Receipt data
    public array $receipt = [];
    public array $invoice = [];
    public bool $isLoading = true;
    public bool $hasError = false;
    public string $errorMessage = '';

    public function mount(?string $orNo = null, ?string $from = null): void
    {
        $this->orNo = $orNo;
        $this->from = $from;

        if ($this->orNo) {
            $this->loadReceipt();
        } else {
            $this->hasError = true;
            $this->errorMessage = 'No receipt specified.';
            $this->isLoading = false;
        }
    }

    public function loadReceipt(): void
    {
        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            $record = HrOfficialReceipt::where('or_no', $this->orNo)->first();

            if (!$record) {
                $this->hasError = true;
                $this->errorMessage = 'Receipt not found: ' . $this->orNo;
                $this->isLoading = false;
                return;
            }

            $this->receipt = [
                'or_no' => $record->or_no,
                'receipt_date' => $record->receipt_date?->format('d-m-Y') ?? '-',
                'company_name' => $record->company_name ?? '-',
                'description' => $record->description ?? '-',
                'currency' => $record->currency ?? 'MYR',
                'amount' => (float) ($record->amount ?? 0),
                'status' => $record->status ?? 'PAID',
                'created_by' => $record->created_by ?? '-',
                'invoice_no' => $record->invoice_no,
                'payment_method' => $record->payment_method ?? 'BANK TRANSFER',
                'software_handover_id' => $record->software_handover_id,
                'handover_id' => $record->handover_id,
            ];

            // Load linked invoice data
            if ($record->invoice_no) {
                $salesInvoice = HrSalesInvoice::where('invoice_no', $record->invoice_no)->first();
                if ($salesInvoice) {
                    $this->invoice = [
                        'invoice_no' => $salesInvoice->invoice_no,
                        'invoice_date' => $salesInvoice->invoice_date?->format('d-m-Y') ?? '-',
                        'invoice_amount' => (float) ($salesInvoice->invoice_amount ?? $salesInvoice->sales_amount ?? 0),
                        'currency' => $salesInvoice->currency ?? 'MYR',
                    ];
                }
            }

        } catch (\Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load receipt: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function goBack(): void
    {
        $this->redirect(url('/admin/hr-billing-official-receipt'), navigate: false);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.view-official-receipt');
    }
}
