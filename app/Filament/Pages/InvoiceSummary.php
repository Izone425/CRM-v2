<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\DebtorAging;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoice Summary';
    protected static ?string $title = 'Invoice Summary';
    protected static ?int $navigationSort = 19;
    protected static string $view = 'filament.pages.invoice-summary';
    protected static ?string $slug = 'invoice-summary';

    // Define excluded salespeople
    protected array $excludedSalespeople = ['WIRSON', 'TTCP'];

    public int $selectedYear;
    public string $selectedSalesPerson = 'All';

    // Add property to store detailed invoice data for logging
    protected array $invoiceDetailLog = [];

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
            ->whereNotIn('salesperson', $this->excludedSalespeople)
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

        // Get invoice data
        $query = Invoice::whereBetween('invoice_date', [$startOfYear, $endOfYear])
            ->where(function ($query) {
                $query->whereNull('salesperson')
                    ->orWhereNotIn('salesperson', $this->excludedSalespeople);
            });

        // Filter by salesperson if selected
        if ($this->selectedSalesPerson !== 'All') {
            $query->where('salesperson', $this->selectedSalesPerson);
        }

        $invoices = $query->get();

        // Get credit notes for the same period
        $creditNotesQuery = DB::table('credit_notes')
            ->whereBetween('credit_note_date', [$startOfYear, $endOfYear])
            ->where(function ($query) {
                $query->whereNull('salesperson')
                    ->orWhereNotIn('salesperson', $this->excludedSalespeople);
            })
            ->select('invoice_number', 'amount', 'currency_code', 'exchange_rate', 'credit_note_date');

        if ($this->selectedSalesPerson !== 'All') {
            $creditNotesQuery->where('salesperson', $this->selectedSalesPerson);
        }

        $creditNotes = $creditNotesQuery->get();

        // Group credit notes by month for easier processing
        $creditNotesByMonth = [];
        foreach ($creditNotes as $creditNote) {
            $month = Carbon::parse($creditNote->credit_note_date)->month;
            if (!isset($creditNotesByMonth[$month])) {
                $creditNotesByMonth[$month] = [];
            }
            $creditNotesByMonth[$month][] = $creditNote;
        }

        // Get all debtor aging records for faster lookup
        $debtorAgingRecords = DebtorAging::pluck('outstanding', 'invoice_number')
            ->toArray();

        $monthlyData = [];
        $this->invoiceDetailLog = []; // Reset invoice detail log

        for ($month = 1; $month <= 12; $month++) {
            $monthInvoices = $invoices->filter(function ($invoice) use ($month) {
                return Carbon::parse($invoice->invoice_date)->month === $month;
            });

            // Initialize values
            $fullyPaid = 0;
            $partiallyPaid = 0;
            $unpaid = 0;

            // Initialize arrays to store invoice numbers for each category
            $fullyPaidInvoices = [];
            $partiallyPaidInvoices = [];
            $unpaidInvoices = [];

            // Calculate monthly credit note total for this month
            $monthCreditNoteTotal = 0;
            if (isset($creditNotesByMonth[$month])) {
                foreach ($creditNotesByMonth[$month] as $creditNote) {
                    // Apply currency conversion if not MYR
                    if ($creditNote->currency_code !== 'MYR' && $creditNote->exchange_rate) {
                        $monthCreditNoteTotal += $creditNote->amount * $creditNote->exchange_rate;
                    } else {
                        $monthCreditNoteTotal += $creditNote->amount;
                    }
                }
            }

            foreach ($monthInvoices as $invoice) {
                // Get the original invoice amount
                $originalAmount = $invoice->invoice_amount;

                // Adjust the invoice amount by subtracting a proportional amount of credit notes
                // based on this invoice's amount relative to the total invoiced amount for the month
                $invoiceNumber = $invoice->invoice_no;

                // Determine payment status with adjusted amount
                $status = $this->determinePaymentStatus($invoice, $debtorAgingRecords, $originalAmount);

                // Add to appropriate category and store invoice number
                if ($status === 'Full Payment') {
                    $fullyPaid += $originalAmount;
                    $fullyPaidInvoices[] = [
                        'invoice_no' => $invoiceNumber,
                        'amount' => $originalAmount,
                        'customer' => $invoice->customer_name ?? 'Unknown'
                    ];
                } elseif ($status === 'Partial Payment') {
                    $partiallyPaid += $originalAmount;
                    $partiallyPaidInvoices[] = [
                        'invoice_no' => $invoiceNumber,
                        'amount' => $originalAmount,
                        'outstanding' => $debtorAgingRecords[$invoiceNumber] ?? 0,
                        'customer' => $invoice->customer_name ?? 'Unknown'
                    ];
                } elseif ($status === 'UnPaid') {
                    $unpaid += $originalAmount;
                    $unpaidInvoices[] = [
                        'invoice_no' => $invoiceNumber,
                        'amount' => $originalAmount,
                        'customer' => $invoice->customer_name ?? 'Unknown'
                    ];
                }
            }

            // Adjust the total by subtracting credit notes for this month
            $total = $fullyPaid + $partiallyPaid + $unpaid - $monthCreditNoteTotal;

            $monthlyData[$month] = [
                'month_name' => Carbon::createFromDate($this->selectedYear, $month, 1)->format('F'),
                'fully_paid' => $fullyPaid,
                'partially_paid' => $partiallyPaid,
                'unpaid' => $unpaid,
                'credit_notes' => $monthCreditNoteTotal,
                'total' => $total,
            ];

            // Store detailed invoice data for logging
            $this->invoiceDetailLog[$month] = [
                'month_name' => Carbon::createFromDate($this->selectedYear, $month, 1)->format('F'),
                'fully_paid' => $fullyPaidInvoices,
                'partially_paid' => $partiallyPaidInvoices,
                'unpaid' => $unpaidInvoices
            ];
        }

        // Log the detailed invoice data
        $this->logDetailedInvoiceData();

        return $monthlyData;
    }

    /**
     * Log detailed invoice data for each month and category
     */
    protected function logDetailedInvoiceData(): void
    {
        $salesperson = $this->selectedSalesPerson;
        $year = $this->selectedYear;

        Log::info("--- Invoice Summary Report for Year: $year, Salesperson: $salesperson ---");

        foreach ($this->invoiceDetailLog as $month => $data) {
            $monthName = $data['month_name'];
            Log::info("Month: $monthName");

            // Log fully paid invoices
            $fullyPaidCount = count($data['fully_paid']);
            Log::info("Fully Paid Invoices ($fullyPaidCount):");
            foreach ($data['fully_paid'] as $invoice) {
                Log::info("  - {$invoice['invoice_no']} | {$this->formatCurrency($invoice['amount'])} | {$invoice['customer']}");
            }

            // Log partially paid invoices
            $partiallyPaidCount = count($data['partially_paid']);
            Log::info("Partially Paid Invoices ($partiallyPaidCount):");
            foreach ($data['partially_paid'] as $invoice) {
                $outstandingAmt = $this->formatCurrency($invoice['outstanding']);
                Log::info("  - {$invoice['invoice_no']} | {$this->formatCurrency($invoice['amount'])} | Outstanding: $outstandingAmt | {$invoice['customer']}");
            }

            // Log unpaid invoices
            $unpaidCount = count($data['unpaid']);
            Log::info("Unpaid Invoices ($unpaidCount):");
            foreach ($data['unpaid'] as $invoice) {
                Log::info("  - {$invoice['invoice_no']} | {$this->formatCurrency($invoice['amount'])} | {$invoice['customer']}");
            }

            Log::info("--------------------------------------------------");
        }

        Log::info("--- End of Invoice Summary Report ---");
    }

    public function getYearToDateTotal(): float
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfDay();
        $today = Carbon::now()->endOfDay();

        // Get invoice total
        $query = Invoice::whereBetween('invoice_date', [$startOfYear, $today])
            ->where(function ($query) {
                $query->whereNull('salesperson')
                    ->orWhereNotIn('salesperson', $this->excludedSalespeople);
            });

        if ($this->selectedSalesPerson !== 'All') {
            $query->where('salesperson', $this->selectedSalesPerson);
        }

        $invoiceTotal = $query->sum('invoice_amount');

        // Get credit note total
        $creditNoteQuery = DB::table('credit_notes')
            ->whereBetween('credit_note_date', [$startOfYear, $today])
            ->where(function ($query) {
                $query->whereNull('salesperson')
                    ->orWhereNotIn('salesperson', $this->excludedSalespeople);
            });

        if ($this->selectedSalesPerson !== 'All') {
            $creditNoteQuery->where('salesperson', $this->selectedSalesPerson);
        }

        $creditNoteTotal = $creditNoteQuery->sum('amount');

        // Return net total
        return $invoiceTotal - $creditNoteTotal;
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
     * @param array $debtorAgingRecords Array of debtor aging records keyed by invoice_number
     * @param float|null $adjustedAmount The adjusted invoice amount after credit notes
     * @return string Payment status
     */
    protected function determinePaymentStatus($invoice, array $debtorAgingRecords, ?float $adjustedAmount = null): string
    {
        $invoiceNo = $invoice->invoice_no;
        $invoiceAmount = $adjustedAmount ?? (float) $invoice->invoice_amount;

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
