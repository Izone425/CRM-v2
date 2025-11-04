<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
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
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class SalesForecastSummaryTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable, InteractsWithForms;

    public $selectedYear;
    public $selectedMonth;
    public $salesSummary = [];

    // Define IDs for special processing
    protected $adminRenewalId = 15;
    protected $adminLeadOwners = ['Fatimah Nurnabilah', 'Norhaiyati'];

    // Map of salesperson names to their user IDs
    protected $salespersonUserIds = [
        'MUIM' => 6,
        'YASMIN' => 7,
        'FARHANAH' => 8,
        'JOSHUA' => 9,
        'AZIZ' => 10,
        'BARI' => 11,
        'VINCE' => 12,
    ];

    // Excluded item codes for invoice calculations
    protected $excludedItemCodes = [
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
        $demoRankings = DB::table('demo_rankings')
            ->select('user_id', 'rank')
            ->orderBy('rank')
            ->get()
            ->pluck('user_id')
            ->toArray();

        $query = User::where('role_id', 2)
            ->whereNotIn('id', [18, 21, 25]);

        if (!empty($demoRankings)) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $demoRankings) . ')');
        } else {
            $query->orderBy('name');
        }

        return $query;
    }

    /**
     * Get total invoice amount from invoice_details (excluding certain item codes)
     */
    private function getTotalInvoiceAmountByDocKey(string $docKey): float
    {
        return InvoiceDetail::where('doc_key', $docKey)
            ->whereNotIn('item_code', $this->excludedItemCodes)
            ->sum('local_sub_total');
    }

    /**
     * Get salesperson name from user ID
     */
    private function getSalespersonNameById(int $userId): ?string
    {
        return array_search($userId, $this->salespersonUserIds) ?: null;
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
                    $demoRankings = DB::table('demo_rankings')
                        ->select('user_id', 'rank')
                        ->orderBy('rank')
                        ->get()
                        ->pluck('user_id')
                        ->toArray();

                    $query = User::where('role_id', 2)
                        ->whereNotIn('id', [18, 21, 25]);

                    if (!empty($demoRankings)) {
                        $query->orderByRaw('FIELD(id, ' . implode(',', $demoRankings) . ')');
                    } else {
                        $query->orderBy('name');
                    }

                    $salespeople = $query->get();
                    $components = [];

                    $month = now()->month;
                    $year = now()->year;

                    foreach ($salespeople as $salesperson) {
                        $latestTarget = \App\Models\SalesTarget::where('salesperson', $salesperson->id)
                            ->orderByDesc('year')
                            ->orderByDesc('month')
                            ->first();

                        $latestAmount = optional($latestTarget)->target_amount;
                        $latestMonth = optional($latestTarget)->month;
                        $latestYear = optional($latestTarget)->year;

                        // Get current invoice amount using new structure
                        $invoiceAmount = $this->getInvoiceTotal($salesperson, $month, $year);

                        if ($salesperson->id === $this->adminRenewalId) {
                            $components[] = \Filament\Forms\Components\Grid::make(7)
                                ->schema([
                                    Placeholder::make("salesperson_name_{$salesperson->id}")
                                        ->hiddenLabel()
                                        ->content('Admin Renewal')
                                        ->columnSpan(1),

                                    TextInput::make("targets.{$salesperson->id}")
                                        ->hiddenLabel()
                                        ->numeric()
                                        ->placeholder($latestAmount
                                            ? 'Latest: RM ' . number_format($latestAmount, 2) . " ({$latestMonth}/{$latestYear})"
                                            : 'No previous target set')
                                        ->columnSpan(2),

                                    TextInput::make("invoice_amount.{$salesperson->id}")
                                        ->hiddenLabel()
                                        ->numeric()
                                        ->placeholder('Invoice amount')
                                        ->columnSpan(2),

                                    TextInput::make("forecast_hot.{$salesperson->id}")
                                        ->hiddenLabel()
                                        ->numeric()
                                        ->placeholder('Manual forecast hot value')
                                        ->columnSpan(2),
                                ]);
                        } else {
                            $components[] = \Filament\Forms\Components\Grid::make(7)
                                ->schema([
                                    Placeholder::make("salesperson_name_{$salesperson->id}")
                                        ->hiddenLabel()
                                        ->content($salesperson->name)
                                        ->columnSpan(1),

                                    TextInput::make("targets.{$salesperson->id}")
                                        ->hiddenLabel()
                                        ->numeric()
                                        ->placeholder($latestAmount
                                            ? 'Latest: RM ' . number_format($latestAmount, 2) . " ({$latestMonth}/{$latestYear})"
                                            : 'No previous target set')
                                        ->columnSpan(3),

                                    TextInput::make("invoice_amount.{$salesperson->id}")
                                        ->hiddenLabel()
                                        ->numeric()
                                        ->placeholder('Invoice amount')
                                        ->columnSpan(3),
                                ]);
                        }
                    }

                    return $components;
                })
                ->action(function ($data) {
                    $now = now();

                    foreach ($data['targets'] as $salespersonId => $amount) {
                        if (is_null($amount) || $amount === '') {
                            continue;
                        }

                        $updateData = [
                            'target_amount' => $amount,
                        ];

                        if (isset($data['invoice_amount'][$salespersonId]) &&
                            !is_null($data['invoice_amount'][$salespersonId]) &&
                            $data['invoice_amount'][$salespersonId] !== '') {
                            $updateData['invoice_amount'] = $data['invoice_amount'][$salespersonId];
                        }

                        if ($salespersonId == $this->adminRenewalId &&
                            isset($data['forecast_hot'][$salespersonId]) &&
                            !is_null($data['forecast_hot'][$salespersonId]) &&
                            $data['forecast_hot'][$salespersonId] !== '') {
                            $updateData['forecast_hot_amount'] = $data['forecast_hot'][$salespersonId];
                        }

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
                        return $record->id === $this->adminRenewalId ? 'Admin Renewal' : $state;
                    }),

                TextColumn::make('invoice')
                    ->label('INVOICE')
                    ->getStateUsing(function ($record) {
                        $total = $this->getInvoiceTotal($record);
                        return 'RM ' . number_format($total, 2);
                    })
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('')
                            ->using(function () {
                                $totals = $this->calculateColumnTotals();
                                return 'RM ' . number_format($totals['invoice'], 2);
                            }),
                    ]),

                TextColumn::make('forecast_hot')
                    ->label('FORECAST - HOT')
                    ->getStateUsing(function ($record) {
                        $total = $this->getForecastHot($record);
                        return 'RM ' . number_format($total, 2);
                    })
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('')
                            ->using(function () {
                                $totals = $this->calculateColumnTotals();
                                return 'RM ' . number_format($totals['forecast_hot'], 2);
                            }),
                    ]),

                TextColumn::make('grand_total')
                    ->label('GRAND TOTAL')
                    ->getStateUsing(function ($record) {
                        $month = $this->selectedMonth;
                        $year = $this->selectedYear;

                        $invoiceTotal = $this->getInvoiceTotal($record, $month, $year);
                        $forecastTotal = $this->getForecastHot($record, $month, $year);

                        $total = $invoiceTotal + $forecastTotal;
                        return 'RM ' . number_format($total, 2);
                    })
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('')
                            ->using(function () {
                                $totals = $this->calculateColumnTotals();
                                return 'RM ' . number_format($totals['grand_total'], 2);
                            }),
                    ]),

                TextColumn::make('sales_target')
                    ->label('SALES TARGET')
                    ->getStateUsing(function ($record) {
                        $now = now();
                        $month = $this->selectedMonth ?? $now->month;
                        $year = $this->selectedYear ?? $now->year;

                        $target = \App\Models\SalesTarget::where('salesperson', $record->id)
                            ->where('month', $month)
                            ->where('year', $year)
                            ->value('target_amount') ?? 0;

                        return 'RM ' . number_format($target, 2);
                    })
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('')
                            ->using(function () {
                                $totals = $this->calculateColumnTotals();
                                return 'RM ' . number_format($totals['sales_target'], 2);
                            }),
                    ]),

                TextColumn::make('difference')
                    ->label('DIFFERENCE')
                    ->getStateUsing(function ($record) {
                        $month = $this->selectedMonth ?? now()->month;
                        $year = $this->selectedYear ?? now()->year;

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

                        $invoiceTotal = $this->getInvoiceTotal($record, $month, $year);
                        $proformaTotal = $this->getProformaTotal($record, $month, $year);
                        $forecastTotal = $this->getForecastHot($record, $month, $year);
                        $actualTotal = $invoiceTotal + $proformaTotal + $forecastTotal;

                        $target = $this->getSalesTarget($record, $month, $year);

                        return $actualTotal >= $target ? 'success' : 'danger';
                    })
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('')
                            ->using(function () {
                                $totals = $this->calculateColumnTotals();
                                return 'RM ' . number_format($totals['difference'], 2);
                            })
                    ])
            ]);
    }

    /**
     * Get invoice total using new separated tables structure
     */
    private function getInvoiceTotal($salesperson, $month = null, $year = null)
    {
        $month = $month ?? $this->selectedMonth;
        $year = $year ?? $this->selectedYear;

        // Check if there's a manual invoice amount in SalesTarget table
        $manualInvoice = \App\Models\SalesTarget::where('salesperson', $salesperson->id)
            ->where('month', $month)
            ->where('year', $year)
            ->value('invoice_amount');

        if (!is_null($manualInvoice)) {
            return $manualInvoice;
        }

        // Get salesperson name for the query
        $salespersonName = $this->getSalespersonNameById($salesperson->id);

        // Use optimized SQL query with new structure
        $placeholders = implode(',', array_fill(0, count($this->excludedItemCodes), '?'));
        $params = array_merge($this->excludedItemCodes, [$year, $month]);

        if ($salesperson->id === $this->adminRenewalId) {
            // For Admin Renewal (null salesperson)
            $sql = "
                SELECT COALESCE(SUM(id.local_sub_total), 0) as total
                FROM invoices i
                INNER JOIN invoice_details id ON i.doc_key = id.doc_key
                WHERE id.item_code NOT IN ($placeholders)
                    AND YEAR(i.invoice_date) = ?
                    AND MONTH(i.invoice_date) = ?
                    AND i.invoice_status != 'V'
                    AND i.salesperson IS NULL
            ";
        } elseif ($salespersonName) {
            // For regular salespeople using salesperson name
            $sql = "
                SELECT COALESCE(SUM(id.local_sub_total), 0) as total
                FROM invoices i
                INNER JOIN invoice_details id ON i.doc_key = id.doc_key
                WHERE id.item_code NOT IN ($placeholders)
                    AND YEAR(i.invoice_date) = ?
                    AND MONTH(i.invoice_date) = ?
                    AND i.invoice_status != 'V'
                    AND i.salesperson = ?
            ";
            $params[] = $salespersonName;
        } else {
            return 0;
        }

        $result = DB::selectOne($sql, $params);
        return (float) $result->total;
    }

    private function getProformaTotal($salesperson, $month = null, $year = null)
    {
        $month = $month ?? $this->selectedMonth;
        $year = $year ?? $this->selectedYear;

        $total = ProformaInvoice::where('salesperson', $salesperson->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
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

        // For regular salespeople
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

    private function calculateColumnTotals()
    {
        $month = $this->selectedMonth ?? now()->month;
        $year = $this->selectedYear ?? now()->year;

        $totals = [
            'invoice' => 0,
            'forecast_hot' => 0,
            'grand_total' => 0,
            'sales_target' => 0,
            'difference' => 0
        ];

        foreach ($this->getTableQuery()->get() as $record) {
            $invoiceTotal = $this->getInvoiceTotal($record, $month, $year);
            $totals['invoice'] += $invoiceTotal;

            $forecastTotal = $this->getForecastHot($record, $month, $year);
            $totals['forecast_hot'] += $forecastTotal;

            $proformaTotal = $this->getProformaTotal($record, $month, $year);

            $recordGrandTotal = $invoiceTotal + $forecastTotal + $proformaTotal;
            $totals['grand_total'] += $recordGrandTotal;

            $targetAmount = $this->getSalesTarget($record, $month, $year);
            $totals['sales_target'] += $targetAmount;

            $totals['difference'] += ($recordGrandTotal - $targetAmount);
        }

        return $totals;
    }

    public function render()
    {
        return view('livewire.sales-forecast-summary-table');
    }
}
