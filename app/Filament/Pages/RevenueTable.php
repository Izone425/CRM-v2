<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\RevenueTarget;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class RevenueTable extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Revenue Table';
    protected static ?int $navigationSort = 18;
    protected static string $view = 'filament.pages.revenue-table';

    public int $selectedYear;
    public array $salespeople = [];
    public bool $editMode = false;
    public array $revenueValues = [];

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
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
            'RENEWAL',
            'OTHERS'  // For all other salespeople not in the list
        ];
    }

    protected function loadRevenueData(): void
    {
        // First check if we have saved values in the database
        $savedValues = RevenueTarget::where('year', $this->selectedYear)
            ->get()
            ->groupBy(['month', 'salesperson'])
            ->toArray();

        // Initialize with existing values from DB or fallback to calculated values
        $data = $this->getRevenueData();

        // Initialize the revenue values array
        foreach ($data as $month => $monthData) {
            foreach ($this->salespeople as $person) {
                // First check if we have a saved value
                if (isset($savedValues[$month][$person][0]['target_amount'])) {
                    $this->revenueValues[$month][$person] = (float)$savedValues[$month][$person][0]['target_amount'];
                } else {
                    // Otherwise use calculated/default value
                    $value = $monthData['salespeople'][$person] > 0 ?
                        $monthData['salespeople'][$person] : 0;
                    $this->revenueValues[$month][$person] = $value;
                }
            }
        }
    }

    public function updatedSelectedYear()
    {
        $this->loadRevenueData();
        $this->dispatch('refresh');
    }

    public function toggleEditMode(): void
    {
        $this->editMode = !$this->editMode;
    }

    public function updateRevenueValue(int $month, string $person, $value): void
    {
        $this->revenueValues[$month][$person] = (float) $value;
    }

    public function saveRevenueValues(): void
    {
        // Save values to the database
        foreach ($this->revenueValues as $month => $personValues) {
            foreach ($personValues as $person => $amount) {
                RevenueTarget::updateOrCreate(
                    [
                        'year' => $this->selectedYear,
                        'month' => $month,
                        'salesperson' => $person,
                    ],
                    [
                        'target_amount' => $amount,
                    ]
                );
            }
        }

        $this->editMode = false;

        Notification::make()
            ->title('Revenue values saved successfully')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'years' => $this->getAvailableYears(),
            'revenueData' => $this->getRevenueData(),
        ];
    }

    protected function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        return [
            $currentYear => (string) $currentYear,
            $currentYear + 1 => (string) ($currentYear + 1),
            $currentYear + 2 => (string) ($currentYear + 2),
        ];
    }

    protected function getRevenueData(): array
    {
        $startOfYear = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
        $endOfYear = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();

        // Define our main salespeople list (without RENEWAL and OTHERS)
        $mainSalespeople = array_slice($this->salespeople, 0, -2);

        // Get all invoices for the selected year
        $invoices = Invoice::whereBetween('invoice_date', [$startOfYear, $endOfYear])
            ->select(
                'salesperson',
                'doc_key',
                DB::raw('MONTH(invoice_date) as month'),
                DB::raw('SUM(invoice_amount) as total_amount')
            )
            ->groupBy('salesperson', 'doc_key', 'month')
            ->get();

        // Initialize the revenue data structure
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

        // Initialize with zeros
        foreach ($months as $monthNum => $monthName) {
            $revenueData[$monthNum] = [
                'month_name' => $monthName,
                'salespeople' => [],
                'total' => 0,
            ];

            foreach ($this->salespeople as $salesperson) {
                $revenueData[$monthNum]['salespeople'][$salesperson] = 0;
            }
        }

        // Fill in the revenue data from invoices
        foreach ($invoices as $invoice) {
            $month = (int) $invoice->month;
            $salesperson = strtoupper($invoice->salesperson);
            $amount = (float) $invoice->total_amount;

            // Skip if month is invalid
            if (!isset($revenueData[$month])) {
                continue;
            }

            // Check if this is a renewal based on doc_key
            if (strpos(strtoupper($invoice->doc_key ?? ''), 'RENEWAL') !== false) {
                $revenueData[$month]['salespeople']['RENEWAL'] += $amount;
            }
            // Check if salesperson is in our main list
            elseif (in_array($salesperson, $mainSalespeople)) {
                $revenueData[$month]['salespeople'][$salesperson] += $amount;
            }
            // Otherwise, add to OTHERS
            else {
                $revenueData[$month]['salespeople']['OTHERS'] += $amount;
            }

            // Add to month total
            $revenueData[$month]['total'] += $amount;
        }

        return $revenueData;
    }
}
