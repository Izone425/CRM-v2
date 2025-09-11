<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\DebtorAging;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class InvoiceSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoice Summary';
    protected static ?string $title = 'Invoice Summary';
    protected static ?int $navigationSort = 19;
    protected static string $view = 'filament.pages.invoice-summary';
    protected static ?string $slug = 'invoice-summary';

    public int $selectedYear;
    public string $selectedSalesPerson = 'All';

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
    }

    public function getYearsOptions(): array
    {
        $currentYear = (int) date('Y');
        $years = [];

        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
            $years[$i] = (string) $i;
        }

        return $years;
    }

    public function getSalesPersonOptions(): array
    {
        $salespeople = Invoice::select('salesperson')
            ->whereNotNull('salesperson')
            ->distinct()
            ->orderBy('salesperson')
            ->pluck('salesperson')
            ->toArray();

        // Add "All" option at the beginning
        return array_merge(['All' => 'All'], array_combine($salespeople, $salespeople));
    }

    public function getMonthlyData(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfDay();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfDay();

        $query = Invoice::whereBetween('invoice_date', [$startOfYear, $endOfYear]);

        // Filter by salesperson if selected
        if ($this->selectedSalesPerson !== 'All') {
            $query->where('salesperson', $this->selectedSalesPerson);
        }

        // Get all debtor aging records for faster lookup
        $debtorAgingRecords = DebtorAging::pluck('outstanding', 'invoice_number')
            ->toArray();

        $invoices = $query->get();

        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthInvoices = $invoices->filter(function ($invoice) use ($month) {
                return Carbon::parse($invoice->invoice_date)->month === $month;
            });

            // Initialize values
            $fullyPaid = 0;
            $partiallyPaid = 0;
            $unpaid = 0;

            foreach ($monthInvoices as $invoice) {
                $status = $this->determinePaymentStatus($invoice, $debtorAgingRecords);
                $amount = $invoice->invoice_amount;

                if ($status === 'Full Payment') {
                    $fullyPaid += $amount;
                } elseif ($status === 'Partial Payment') {
                    $partiallyPaid += $amount;
                } elseif ($status === 'UnPaid') {
                    $unpaid += $amount;
                }
            }

            $total = $fullyPaid + $partiallyPaid + $unpaid;

            $monthlyData[$month] = [
                'month_name' => Carbon::createFromDate($this->selectedYear, $month, 1)->format('F'),
                'fully_paid' => $fullyPaid,
                'partially_paid' => $partiallyPaid,
                'unpaid' => $unpaid,
                'total' => $total,
            ];
        }

        return $monthlyData;
    }

    public function getYearToDateTotal(): float
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfDay();
        $today = Carbon::now()->endOfDay();

        $query = Invoice::whereBetween('invoice_date', [$startOfYear, $today]);

        if ($this->selectedSalesPerson !== 'All') {
            $query->where('salesperson', $this->selectedSalesPerson);
        }

        return $query->sum('invoice_amount');
    }

    public function updatedSelectedYear()
    {
        $this->dispatch('refresh');
    }

    public function updatedSelectedSalesPerson()
    {
        $this->dispatch('refresh');
    }

    // Helper methods for formatting
    public function formatCurrency(float $amount): string
    {
        return 'RM ' . number_format($amount, 2);
    }

    // Define colors for different payment statuses
    public function getColorForStatus(string $status): string
    {
        return match ($status) {
            'fully_paid' => 'bg-green-100 text-green-800',
            'partially_paid' => 'bg-yellow-100 text-yellow-800',
            'unpaid' => 'bg-red-100 text-red-800',
            'total' => 'highlight',
            default => '',
        };
    }

    /**
     * Determine payment status using the debtor aging table
     *
     * @param Invoice $invoice The invoice record
     * @param array $debtorAgingRecords Array of debtor aging records keyed by invoice_no
     * @return string Payment status
     */
    protected function determinePaymentStatus($invoice, array $debtorAgingRecords): string
    {
        $invoiceNo = $invoice->invoice_no;
        $invoiceAmount = (float) $invoice->invoice_amount;

        // If invoice is not found in debtor_aging, it's fully paid
        if (!isset($debtorAgingRecords[$invoiceNo])) {
            return 'Full Payment';
        }

        $outstanding = (float) $debtorAgingRecords[$invoiceNo];

        // If outstanding is 0, it's fully paid
        if ($outstanding === 0.0) {
            return 'Full Payment';
        }

        // If outstanding equals invoice amount, it's unpaid
        if (abs($outstanding - $invoiceAmount) < 0.01) { // Using small epsilon for float comparison
            return 'UnPaid';
        }

        // If outstanding is less than invoice amount but greater than 0, it's partially paid
        if ($outstanding < $invoiceAmount && $outstanding > 0) {
            return 'Partial Payment';
        }

        // Fallback
        return 'UnPaid';
    }

    public function getLastUpdatedTimestamp(): string
    {
        $now = Carbon::now();
        $formattedDate = $now->format('F j, Y');
        $formattedTime = $now->format('g:i A');

        return "Last updated: {$formattedDate} at {$formattedTime}";
    }
}
