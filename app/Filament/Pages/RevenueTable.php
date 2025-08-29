<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class RevenueTable extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Revenue';
    protected static ?int $navigationSort = 18;
    protected static ?string $title = '';
     protected static ?string $slug = 'revenue';
    protected static string $view = 'filament.pages.revenue-table';

    public int $selectedYear;
    public array $salespeople = [];
    public array $revenueValues = [];
    public int $currentMonth;

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
        $this->currentMonth = (int) date('n');
        $this->loadSalespeople();
        $this->loadRevenueData();
    }

    protected function loadSalespeople(): void
    {
        // Define specific salespeople we want to show
        $this->salespeople = [
            'Muim',
            'Yasmin',
            'Farhanah',
            'Joshua',
            'Aziz',
            'Bari',
            'Vince',
            'Others'  // For all other salespeople not in the list
        ];
    }

    protected function loadRevenueData(): void
    {
        // Get actual revenue data from invoices
        $this->revenueValues = $this->getInvoiceRevenue();
    }

    /**
     * Get actual revenue data from invoices table
     */
    protected function getInvoiceRevenue(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

        // Convert main salespeople names to uppercase for comparison
        // Exclude the 'Others' entry which is the last one
        $mainSalespeople = array_map('strtoupper', array_slice($this->salespeople, 0, -1));

        // Get all invoices for the selected year
        $invoiceRevenue = Invoice::whereBetween('invoice_date', [$startOfYear, $endOfYear])
            ->select(
                DB::raw('UPPER(salesperson) as salesperson'),
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('SUM(invoice_amount) as total_amount')
            )
            ->groupBy(DB::raw('UPPER(salesperson)'), 'month')
            ->get();

        // Get all credit notes for the selected year
        $creditNoteRevenue = DB::table('credit_notes')
            ->whereBetween('credit_note_date', [$startOfYear, $endOfYear])
            ->select(
                DB::raw('UPPER(salesperson) as salesperson'),
                DB::raw('MONTH(credit_note_date) as month'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy(DB::raw('UPPER(salesperson)'), 'month')
            ->get();

        // Initialize data structure
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $data[$month] = [];
            foreach ($this->salespeople as $person) {
                $data[$month][$person] = 0;
            }
        }

        // Add invoice amounts
        foreach ($invoiceRevenue as $invoice) {
            $month = (int) $invoice->month;
            $salesperson = $invoice->salesperson;
            $amount = (float) $invoice->total_amount;

            // Check if this is one of our main salespeople
            if (in_array($salesperson, $mainSalespeople)) {
                // Find the original case version from salespeople array
                $originalCase = $this->findOriginalCase($salesperson);
                $data[$month][$originalCase] += $amount;
            } else {
                // Everyone else goes to "Others"
                $data[$month]['Others'] += $amount;
            }
        }

        // Subtract credit note amounts
        foreach ($creditNoteRevenue as $creditNote) {
            $month = (int) $creditNote->month;
            $salesperson = $creditNote->salesperson;
            $amount = (float) $creditNote->total_amount;

            // Check if this is one of our main salespeople
            if (in_array($salesperson, $mainSalespeople)) {
                // Find the original case version from salespeople array
                $originalCase = $this->findOriginalCase($salesperson);
                $data[$month][$originalCase] -= $amount;
            } else {
                // Everyone else goes to "Others"
                $data[$month]['Others'] -= $amount;
            }
        }

        return $data;
    }

    protected function findOriginalCase(string $uppercaseName): string
    {
        foreach ($this->salespeople as $person) {
            if (strtoupper($person) === $uppercaseName) {
                return $person;
            }
        }

        // Default to Others if no match found
        return 'Others';
    }

    public function updatedSelectedYear()
    {
        $this->loadRevenueData();
        $this->dispatch('refresh');
    }

    protected function getViewData(): array
    {
        return [
            'years' => $this->getAvailableYears(),
            'revenueData' => $this->getRevenueData(),
            'currentMonth' => $this->currentMonth,
            'isCurrentYear' => $this->selectedYear === (int) date('Y'),
        ];
    }

    protected function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        return [
            $currentYear - 2 => (string) ($currentYear - 2),
            $currentYear - 1 => (string) ($currentYear - 1),
            $currentYear => (string) $currentYear,
            $currentYear + 1 => (string) ($currentYear + 1),
        ];
    }

    protected function getRevenueData(): array
    {
        // Define month names
        $months = [
            1 => 'JAN',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'APR',
            5 => 'MAY',
            6 => 'JUN',
            7 => 'JUL',
            8 => 'AUG',
            9 => 'SEP',
            10 => 'OCT',
            11 => 'NOV',
            12 => 'DEC',
        ];

        $revenueData = [];

        // Create the revenue data structure
        foreach ($months as $monthNum => $monthName) {
            $revenueData[$monthNum] = [
                'month_name' => $monthName,
                'salespeople' => [],
                'total' => 0,
            ];

            // For each salesperson, get the actual invoice amount
            foreach ($this->salespeople as $salesperson) {
                $amount = $this->revenueValues[$monthNum][$salesperson] ?? 0;
                $revenueData[$monthNum]['salespeople'][$salesperson] = $amount;
                $revenueData[$monthNum]['total'] += $amount;
            }
        }

        return $revenueData;
    }
}
