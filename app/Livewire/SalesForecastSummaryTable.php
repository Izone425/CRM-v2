<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\Lead;
use App\Models\SalesTarget;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Livewire\Component;
use Carbon\Carbon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class SalesForecastSummaryTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable, InteractsWithForms;

    public $selectedYear;
    public $selectedMonth;
    public $salesSummary = [];

    // Define IDs for special processing
    protected $adminRenewalId = 15; // The salesperson ID to use for Admin Renewal
    protected $adminLeadOwners = ['Fatimah Nurnabilah', 'Norhaiyati']; // Lead owners to include in Admin Renewal

    public function mount()
    {
        $now = now();

        $this->selectedMonth ??= $now->month;
        $this->selectedYear ??= $now->year;

        $this->loadSalesSummary();
    }

    public function loadSalesSummary()
    {
        $salespeople = $this->getTableQuery()->get();

        $this->salesSummary = $salespeople->map(function ($salesperson) {
            return [
                'id' => $salesperson->id,
                'salesperson' => $salesperson->name,
                'invoice' => $this->getInvoiceTotal($salesperson),
                'proforma_inv' => $this->getProformaTotal($salesperson),
                'inv_pi' => $this->getInvoiceTotal($salesperson) + $this->getProformaTotal($salesperson),
                'forecast_hot' => $this->getForecastHot($salesperson),
                'grand_total' => $this->getInvoiceTotal($salesperson) + $this->getProformaTotal($salesperson) + $this->getForecastHot($salesperson),
                'sales_target' => $salesperson->sales_target,
                'difference' => ($this->getInvoiceTotal($salesperson) + $this->getProformaTotal($salesperson) + $this->getForecastHot($salesperson)) - $salesperson->sales_target,
            ];
        })->toArray();
    }

    protected function getTableQuery()
    {
        // First, get the demo rankings to determine the order
        $demoRankings = DB::table('demo_rankings')
            ->select('user_id', 'rank')
            ->orderBy('rank')
            ->get()
            ->pluck('user_id')
            ->toArray();

        // Start with the base query to get salespersons, but now include ID 15
        $query = User::where('role_id', 2)
            ->whereNotIn('id', [18, 21, 25]); // ID 15 is now included

        // If we have demo rankings, use them to order the results
        if (!empty($demoRankings)) {
            // Use FIELD function in MySQL to order by the ranking position
            $query->orderByRaw('FIELD(id, ' . implode(',', $demoRankings) . ')');
        } else {
            // Default sorting by name if no rankings are available
            $query->orderBy('name');
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('name')
            ->heading('Sales Forecast Summary')
            ->headerActions([
                \Filament\Tables\Actions\Action::make('setSalesTarget')
                ->label('Update New Data')
                ->modalHeading('Set Sales Target for Salespersons')
                ->form(function () {
                    // First, get the demo rankings to determine the order, same as in getTableQuery()
                    $demoRankings = DB::table('demo_rankings')
                        ->select('user_id', 'rank')
                        ->orderBy('rank')
                        ->get()
                        ->pluck('user_id')
                        ->toArray();

                    // Get salespersons with the same filter as in getTableQuery()
                    $query = User::where('role_id', 2)
                        ->whereNotIn('id', [18, 21, 25]); // Include ID 15 for Admin Renewal

                    // Apply the same ordering as in getTableQuery()
                    if (!empty($demoRankings)) {
                        $query->orderByRaw('FIELD(id, ' . implode(',', $demoRankings) . ')');
                    } else {
                        $query->orderBy('name');
                    }

                    // Get the salespeople in the same order as the table
                    $salespeople = $query->get();
                    $components = [];

                    $month = now()->month;
                    $year = now()->year;

                    foreach ($salespeople as $salesperson) {
                        // Get the latest target record
                        $latestTarget = \App\Models\SalesTarget::where('salesperson', $salesperson->id)
                            ->orderByDesc('year')
                            ->orderByDesc('month')
                            ->first();

                        $latestAmount = optional($latestTarget)->target_amount;
                        $latestMonth = optional($latestTarget)->month;
                        $latestYear = optional($latestTarget)->year;

                        // Get current invoice amount
                        if ($salesperson->id === $this->adminRenewalId) {
                            // For Admin Renewal
                            $invoiceAmount = Invoice::whereNull('salesperson')
                                ->whereYear('invoice_date', $year)
                                ->whereMonth('invoice_date', $month)
                                ->sum('amount');

                            // Add special handling for Admin Renewal
                            $components[] = \Filament\Forms\Components\Grid::make(3)
                                ->schema([
                                    TextInput::make("targets.{$salesperson->id}")
                                        ->label('ADMIN RENEWAL Target')
                                        ->numeric()
                                        ->placeholder($latestAmount
                                            ? 'Latest: RM ' . number_format($latestAmount, 2) . " ({$latestMonth}/{$latestYear})"
                                            : 'No previous target set'),

                                    TextInput::make("invoice_amount.{$salesperson->id}")
                                        ->label('ADMIN RENEWAL Invoice')
                                        ->numeric()
                                        ->placeholder('Manual invoice amount')
                                        ->helperText('Current: RM ' . number_format($invoiceAmount, 2)),

                                    TextInput::make("forecast_hot.{$salesperson->id}")
                                        ->label('ADMIN RENEWAL Forecast Hot')
                                        ->numeric()
                                        ->placeholder('Manual forecast hot value')
                                        ->helperText('Enter additional forecast amount')
                                ]);
                        } else {
                            // For regular salespeople
                            $invoiceAmount = Invoice::where('salesperson', $salesperson->id)
                                ->whereYear('invoice_date', $year)
                                ->whereMonth('invoice_date', $month)
                                ->sum('amount');

                            // Add target and invoice amount fields for regular salespeople
                            $components[] = \Filament\Forms\Components\Grid::make(2)
                                ->schema([
                                    TextInput::make("targets.{$salesperson->id}")
                                        ->label($salesperson->name . ' Target')
                                        ->numeric()
                                        ->placeholder($latestAmount
                                            ? 'Latest: RM ' . number_format($latestAmount, 2) . " ({$latestMonth}/{$latestYear})"
                                            : 'No previous target set'),

                                    TextInput::make("invoice_amount.{$salesperson->id}")
                                        ->label($salesperson->name . ' Invoice')
                                        ->numeric()
                                        ->placeholder('Manual invoice amount')
                                        ->helperText('Current: RM ' . number_format($invoiceAmount, 2))
                                ]);
                        }
                    }

                    return $components;
                })
                ->action(function ($data) {
                    $now = now();

                    // Process sales targets
                    foreach ($data['targets'] as $salespersonId => $amount) {
                        if (is_null($amount) || $amount === '') {
                            continue; // Skip empty entries
                        }

                        $updateData = [
                            'target_amount' => $amount,
                        ];

                        // Add invoice amount if provided
                        if (isset($data['invoice_amount'][$salespersonId]) &&
                            !is_null($data['invoice_amount'][$salespersonId]) &&
                            $data['invoice_amount'][$salespersonId] !== '') {
                            $updateData['invoice_amount'] = $data['invoice_amount'][$salespersonId];
                        }

                        // For Admin Renewal, also add forecast hot if provided
                        if ($salespersonId == $this->adminRenewalId &&
                            isset($data['forecast_hot'][$salespersonId]) &&
                            !is_null($data['forecast_hot'][$salespersonId]) &&
                            $data['forecast_hot'][$salespersonId] !== '') {
                            $updateData['forecast_hot_amount'] = $data['forecast_hot'][$salespersonId];
                        }

                        // Update or create the sales target record
                        \App\Models\SalesTarget::updateOrCreate(
                            [
                                'salesperson' => $salespersonId,
                                'year' => $now->year,
                                'month' => $now->month,
                            ],
                            $updateData
                        );
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Sales targets updated successfully!')
                        ->success()
                        ->send();
                })
                ->modalSubmitActionLabel('Save Targets')
            ])
            ->filters([
                Filter::make('selectedMonth')
                ->form([
                    TextInput::make('selectedMonth')
                        ->type('month')
                        ->label('Month')
                        ->default(Carbon::now()->format('Y-m'))
                        ->reactive()
                        ->afterStateUpdated(function ($state, $livewire) {
                            $parsed = Carbon::parse($state);
                            $livewire->selectedMonth = $parsed->month;
                            $livewire->selectedYear = $parsed->year;
                        }),
                ]),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('SALESPERSON')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        return $record->id === $this->adminRenewalId ? 'ADMIN RENEWAL' : $state;
                    }),

                TextColumn::make('invoice')
                    ->label('INVOICE')
                    ->getStateUsing(function ($record) {
                        // Use the getInvoiceTotal method for all salespeople
                        $total = $this->getInvoiceTotal($record);
                        return 'RM ' . number_format($total, 2);
                    }),

                TextColumn::make('forecast_hot')
                    ->label('FORECAST - HOT')
                    ->getStateUsing(function ($record) {
                        if ($record->id === $this->adminRenewalId) {
                            // For Admin Renewal, get hot leads owned by specified people with null salesperson
                            $total = Lead::whereIn('lead_owner', $this->adminLeadOwners)
                                ->whereNull('salesperson')
                                ->where('lead_status', 'Hot')
                                ->sum('deal_amount');
                        } else {
                            // For regular salespeople
                            $total = $this->getForecastHot($record);
                        }
                        return 'RM ' . number_format($total, 2);
                    }),

                TextColumn::make('grand_total')
                    ->label('GRAND TOTAL')
                    ->getStateUsing(function ($record) {
                        $month = $this->selectedMonth;
                        $year = $this->selectedYear;

                        // Use consistent methods for all calculations
                        $invoiceTotal = $this->getInvoiceTotal($record, $month, $year);
                        $forecastTotal = $this->getForecastHot($record, $month, $year);

                        $total = $invoiceTotal + $forecastTotal;
                        return 'RM ' . number_format($total, 2);
                    }),

                TextColumn::make('sales_target')
                    ->label('SALES TARGET')
                    ->getStateUsing(function ($record) {
                        $now = now(); // fallback
                        $month = $this->selectedMonth ?? $now->month;
                        $year = $this->selectedYear ?? $now->year;

                        $target = \App\Models\SalesTarget::where('salesperson', $record->id)
                            ->where('month', $month)
                            ->where('year', $year)
                            ->value('target_amount') ?? 0;

                        return 'RM ' . number_format($target, 2);
                    }),

                TextColumn::make('difference')
                    ->label('DIFFERENCE')
                    ->getStateUsing(function ($record) {
                        $month = $this->selectedMonth ?? now()->month;
                        $year = $this->selectedYear ?? now()->year;

                        // Use consistent methods for all calculations
                        $invoiceTotal = $this->getInvoiceTotal($record, $month, $year);
                        $proformaTotal = $this->getProformaTotal($record, $month, $year);
                        $forecastTotal = $this->getForecastHot($record, $month, $year);
                        $actualTotal = $invoiceTotal + $proformaTotal + $forecastTotal;

                        $target = $this->getSalesTarget($record, $month, $year);
                        $difference = $actualTotal - $target;

                        return 'RM ' . number_format($difference, 2);
                    })
                    ->color(function ($record) {
                        $month = $this->selectedMonth ?? now()->month;
                        $year = $this->selectedYear ?? now()->year;

                        // Use consistent methods for all calculations
                        $invoiceTotal = $this->getInvoiceTotal($record, $month, $year);
                        $proformaTotal = $this->getProformaTotal($record, $month, $year);
                        $forecastTotal = $this->getForecastHot($record, $month, $year);
                        $actualTotal = $invoiceTotal + $proformaTotal + $forecastTotal;

                        $target = $this->getSalesTarget($record, $month, $year);

                        return $actualTotal >= $target ? 'success' : 'danger';
                    })
            ]);
    }

    private function getInvoiceTotal($salesperson, $month = null, $year = null)
    {
        $month = $month ?? $this->selectedMonth;
        $year = $year ?? $this->selectedYear;

        // First check if there's a manual invoice amount (for any salesperson)
        $manualInvoice = \App\Models\SalesTarget::where('salesperson', $salesperson->id)
            ->where('month', $month)
            ->where('year', $year)
            ->value('invoice_amount');

        if (!is_null($manualInvoice)) {
            return $manualInvoice;
        }

        // If no manual amount, check actual invoices based on salesperson
        if ($salesperson->id === $this->adminRenewalId) {
            // For Admin Renewal
            return Invoice::whereNull('salesperson')
                ->whereYear('invoice_date', $year)
                ->whereMonth('invoice_date', $month)
                ->sum('amount');
        } else {
            // For regular salespeople
            return Invoice::where('salesperson', $salesperson->id)
                ->whereYear('invoice_date', $year)
                ->whereMonth('invoice_date', $month)
                ->sum('amount');
        }
    }

    private function getProformaTotal($salesperson)
    {
        $total = ProformaInvoice::where('salesperson', $salesperson->id)
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->sum('amount');

        return $total;
    }

    private function getForecastHot($salesperson, $month = null, $year = null)
    {
        $month = $month ?? $this->selectedMonth;
        $year = $year ?? $this->selectedYear;

        if ($salesperson->id === $this->adminRenewalId) {
            // First check if there's a manual forecast amount
            $manualForecast = \App\Models\SalesTarget::where('salesperson', $salesperson->id)
                ->where('month', $month)
                ->where('year', $year)
                ->value('forecast_hot_amount');

            if (!is_null($manualForecast)) {
                return $manualForecast;
            }

            // Fall back to calculated value if no manual forecast
            return Lead::whereIn('lead_owner', $this->adminLeadOwners)
                ->whereNull('salesperson')
                ->where('lead_status', 'Hot')
                ->sum('deal_amount');
        }

        // For regular salespeople, use the existing logic
        return Lead::where('salesperson', $salesperson->id)
            ->where('lead_status', 'Hot')
            ->sum('deal_amount');
    }

    private function getSalesTarget($record, $month, $year)
    {
        return \App\Models\SalesTarget::where('salesperson', $record->id)
            ->where('month', $month)
            ->where('year', $year)
            ->value('target_amount') ?? 0;
    }

    public function render()
    {
        return view('livewire.sales-forecast-summary-table');
    }
}
