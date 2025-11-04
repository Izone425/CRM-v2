<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
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

    public array $excludedSalespeople = ['WIRSON', 'TTCP'];

    // Excluded item codes for invoice calculations
    protected array $excludedItemCodes = [
        'SHIPPING',
        'Not',
        'Rem Code',
        'In',
        'BANKCHG',
        'DEPOSIT-MYR',
        'F.COMMISSION',
        'L.COMMISSION',
        'L.ENTITLEMENT',
        'MGT FEES',
        'PG.COMMISSION'
    ];

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
            'MUIM',
            'YASMIN',
            'FARHANAH',
            'JOSHUA',
            'AZIZ',
            'BARI',
            'VINCE',
            'Others'  // For all other salespeople not in the list
        ];
    }

    protected function loadRevenueData(): void
    {
        // Get actual revenue data from invoices
        $this->revenueValues = $this->getInvoiceRevenue();
    }

    /**
     * Get actual revenue data from invoices table using new structure
     */
    protected function getInvoiceRevenue(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

        // Main salespeople (exclude 'Others')
        $mainSalespeople = array_slice($this->salespeople, 0, -1);

        // Initialize data structure
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $data[$month] = [];
            foreach ($this->salespeople as $person) {
                $data[$month][$person] = 0;
            }
        }

        // OPTIMIZED: Get invoice revenue using new structure with excluded item codes
        $placeholders = implode(',', array_fill(0, count($this->excludedItemCodes), '?'));
        $excludedPlaceholders = implode(',', array_fill(0, count($this->excludedSalespeople), '?'));

        $mainSalespersonPlaceholders = implode(',', array_fill(0, count($mainSalespeople), '?'));

        $invoiceParams = array_merge(
            $this->excludedItemCodes,
            [$startOfYear->format('Y-m-d'), $endOfYear->format('Y-m-d')],
            $this->excludedSalespeople
        );

        // Query for invoice revenue using new separated tables
        $invoiceRevenue = DB::select("
            SELECT
                UPPER(i.salesperson) as salesperson,
                MONTH(i.invoice_date) as month,
                COALESCE(SUM(id.local_sub_total), 0) as total_amount
            FROM invoices i
            INNER JOIN invoice_details id ON i.doc_key = id.doc_key
            WHERE id.item_code NOT IN ($placeholders)
                AND i.invoice_date BETWEEN ? AND ?
                AND i.invoice_status != 'V'
                AND (i.salesperson IS NULL OR UPPER(i.salesperson) NOT IN ($excludedPlaceholders))
            GROUP BY UPPER(i.salesperson), MONTH(i.invoice_date)
        ", $invoiceParams);

        // Get credit note revenue
        $creditNoteParams = array_merge(
            [$startOfYear->format('Y-m-d'), $endOfYear->format('Y-m-d')],
            $this->excludedSalespeople
        );

        $creditNoteRevenue = DB::select("
            SELECT
                UPPER(salesperson) as salesperson,
                MONTH(credit_note_date) as month,
                SUM(amount) as total_amount
            FROM credit_notes
            WHERE credit_note_date BETWEEN ? AND ?
                AND (salesperson IS NULL OR UPPER(salesperson) NOT IN ($excludedPlaceholders))
            GROUP BY UPPER(salesperson), MONTH(credit_note_date)
        ", $creditNoteParams);

        // Process invoices
        foreach ($invoiceRevenue as $invoice) {
            $month = (int) $invoice->month;
            $salesperson = $invoice->salesperson;
            $amount = (float) $invoice->total_amount;

            // Handle main salespeople for all months
            if (in_array($salesperson, $mainSalespeople)) {
                $data[$month][$salesperson] += $amount;
            }
            // For "Others", only process September onwards from DB
            // (January-August will be overridden with fixed values later)
            elseif ($month >= 9) {
                $data[$month]['Others'] += $amount;
            }
        }

        // Process credit notes
        foreach ($creditNoteRevenue as $creditNote) {
            $month = (int) $creditNote->month;
            $salesperson = $creditNote->salesperson;
            $amount = (float) $creditNote->total_amount;

            // Handle main salespeople for all months
            if (in_array($salesperson, $mainSalespeople)) {
                $data[$month][$salesperson] -= $amount;
            }
            // For "Others", only process September onwards from DB
            elseif ($month >= 9) {
                $data[$month]['Others'] -= $amount;
            }
        }

        // Hard-coded values for "Others" column (January-August of current year)
        if ($this->selectedYear == date('Y')) {
            // Set the fixed values for "Others" column for Jan-Aug
            $othersValues = [
                1 => 581675.55,  // January
                2 => 369221.61,  // February
                3 => 432626.93,  // March
                4 => 262396.86,  // April
                5 => 469012.35,  // May
                6 => 412398.51,  // June
                7 => 347908.97,  // July
                8 => 493526.84,  // August
                // September to December will use DB values
            ];

            // Apply the fixed values to the "Others" column for Jan-Aug
            foreach ($othersValues as $month => $value) {
                $data[$month]['Others'] = $value;
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
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
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
