<?php

namespace App\Filament\Pages;

use App\Models\DebtorAging;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class DebtorAgingRawData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Debtor Aging Raw Data';
    protected static ?string $title = 'Admin Debtor Dashboard';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.debtor-aging-raw-data';

    // NO1: Exclude these 7 salespeople
    protected array $excludedSalespeople = [
        'MUIM',
        'YASMIN',
        'FARHANAH',
        'JOSHUA',
        'AZIZ',
        'BARI',
        'VINCE'
    ];

    public $allDebtorStats;
    public $hrdfDebtorStats;
    public $productDebtorStats;
    public $unpaidDebtorStats;
    public $partialPaymentDebtorStats;

    public $filterSalesperson = [];
    public $filterInvoiceType = null;
    public $filterPaymentStatus = null;
    public $filterDebtorAging = null;
    public $filterInvoiceDateFrom = null;
    public $filterInvoiceDateUntil = null;
    public $filterYear = null;
    public $filterMonth = null;
    public $filterCurrency = [];
    public $filterSupport = [];
    public $filterDecimalPlaces = 2; // NO3: Default 2 decimal places
    public $filterInvoiceAgeDays = null;

    public function mount(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        // Get base query and apply all current filters
        $baseQuery = $this->getFilteredBaseQuery();

        // Load data for each box
        $this->allDebtorStats = $this->getAllDebtorStats($baseQuery);
        $this->hrdfDebtorStats = $this->getHrdfDebtorStats($baseQuery);
        $this->productDebtorStats = $this->getProductDebtorStats($baseQuery);
        $this->unpaidDebtorStats = $this->getUnpaidDebtorStats($baseQuery);
        $this->partialPaymentDebtorStats = $this->getPartialPaymentDebtorStats($baseQuery);
    }

    protected function getFilteredBaseQuery()
    {
        $query = DebtorAging::query();

        // Filter only for unpaid or partial payment debtors
        $query->where('outstanding', '>', 0);

        $query->where(function($q) {
            $q->where('debtor_code', 'like', 'ARM%')
            ->orWhere('debtor_code', 'like', 'ARU%');
        });

        // NO1: Exclude the 7 salespeople BUT keep NULL/blank salespeople
        $query->where(function($q) {
            $q->whereNotIn('salesperson', $this->excludedSalespeople)
            ->orWhereNull('salesperson')
            ->orWhere('salesperson', '');
        });

        // Apply salesperson filter if set (including blank/null values)
        if (!empty($this->filterSalesperson)) {
            $query->where(function($q) {
                $q->whereIn('salesperson', $this->filterSalesperson);
                // Include blank/null if selected
                if (in_array('', $this->filterSalesperson) || in_array('blank', $this->filterSalesperson)) {
                    $q->orWhereNull('salesperson')->orWhere('salesperson', '');
                }
            });
        }

        // Apply currency filter if set
        if (!empty($this->filterCurrency)) {
            $query->whereIn('currency_code', $this->filterCurrency);
        }

        // Apply support filter if set (including blank/null values)
        if (!empty($this->filterSupport)) {
            $query->where(function($q) {
                $q->whereIn('support', $this->filterSupport);
                // Include blank/null if selected
                if (in_array('', $this->filterSupport) || in_array('blank', $this->filterSupport)) {
                    $q->orWhereNull('support')->orWhere('support', '');
                }
            });
        }

        // Apply invoice type filter if set
        if ($this->filterInvoiceType === 'hrdf') {
            $query->where('invoice_number', 'like', 'EHIN%');
        } elseif ($this->filterInvoiceType === 'product') {
            $query->where('invoice_number', 'like', 'EPIN%');
        }

        // Apply payment status filter if set
        if ($this->filterPaymentStatus === 'unpaid') {
            $query->whereRaw('outstanding = invoice_amount');
        } elseif ($this->filterPaymentStatus === 'partial') {
            $query->whereRaw('outstanding < invoice_amount')
                ->where('outstanding', '>', 0);
        }

        // Apply debtor aging filter if set
        if ($this->filterDebtorAging) {
            $this->applyDebtorAgingFilter($query, $this->filterDebtorAging);
        }

        // Add invoice age days filter here
        if ($this->filterInvoiceAgeDays) {
            $this->applyInvoiceAgeDaysFilter($query, $this->filterInvoiceAgeDays);
        }

        // Apply date filters if set
        if ($this->filterInvoiceDateFrom) {
            $query->whereDate('invoice_date', '>=', $this->filterInvoiceDateFrom);
        }

        if ($this->filterInvoiceDateUntil) {
            $query->whereDate('invoice_date', '<=', $this->filterInvoiceDateUntil);
        }

        // Apply year filter if set
        if ($this->filterYear) {
            $query->whereYear('invoice_date', $this->filterYear);
        }

        // Apply month filter if set
        if ($this->filterMonth) {
            $query->whereMonth('invoice_date', $this->filterMonth);
        }

        return $query;
    }

    protected function applyDebtorAgingFilter($query, $agingFilter)
    {
        $now = Carbon::now();

        switch ($agingFilter) {
            case 'current':
                $query->where(function($q) use ($now) {
                    $q->where('aging_date', '>=', $now)
                      ->orWhere(function($subQ) use ($now) {
                          $subQ->where('aging_date', '<', $now)
                               ->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 0', [$now]);
                      });
                });
                break;
            case '1_month':
                $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 1', [$now]);
                break;
            case '2_months':
                $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 2', [$now]);
                break;
            case '3_months':
                $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 3', [$now]);
                break;
            case '4_months':
                $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 4', [$now]);
                break;
            case '5_plus_months':
                $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) >= 5', [$now]);
                break;
        }
    }

    protected function applyInvoiceAgeDaysFilter($query, $daysFilter)
    {
        $today = Carbon::now()->startOfDay();

        switch ($daysFilter) {
            case '30_days':
                // Show invoices MORE THAN 30 days old (before 30 days ago)
                $cutoffDate = $today->copy()->subDays(30);
                $query->where('invoice_date', '<', $cutoffDate);
                break;
            case '60_days':
                // Show invoices MORE THAN 60 days old (before 60 days ago)
                $cutoffDate = $today->copy()->subDays(60); // ✅ Fixed: was 90
                $query->where('invoice_date', '<', $cutoffDate);
                break;
            case '90_days':
                // Show invoices MORE THAN 90 days old (before 90 days ago)
                $cutoffDate = $today->copy()->subDays(90); // ✅ Fixed: was 120
                $query->where('invoice_date', '<', $cutoffDate);
                break;
            case '120_days':
                // Show invoices MORE THAN 120 days old (before 120 days ago)
                $cutoffDate = $today->copy()->subDays(120); // ✅ Fixed: was 150
                $query->where('invoice_date', '<', $cutoffDate);
                break;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DebtorAging::query()
                // ->where('outstanding', '>', 0)
                // ->where(function($q) {
                //     $q->where('debtor_code', 'like', 'ARM%')
                //     ->orWhere('debtor_code', 'like', 'ARU%');
                // })
                // ->where(function($q) {
                //     $q->whereNotIn('salesperson', $this->excludedSalespeople)
                //     ->orWhereNull('salesperson')
                //     ->orWhere('salesperson', '');
                // }))
                ->where('id', 0))
                ->columns([
                    TextColumn::make('company_name')
                        ->label('Company Name')
                        ->searchable()
                        ->sortable()
                        ->wrap(),

                    TextColumn::make('invoice_number')
                        ->label('Invoice Number')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('invoice_date')
                        ->label('Invoice Date')
                        ->date('d/m/Y')
                        ->sortable(),

                    BadgeColumn::make('aging')
                        ->label('Debtor Aging')
                        ->getStateUsing(function (DebtorAging $record): string {
                            return $this->calculateAgingText($record);
                        })
                        ->colors([
                            'success' => 'Current',
                            'info' => '1 Month',
                            'warning' => '2 Months',
                            'warning' => '3 Months',
                            'danger' => '4 Months',
                            'danger' => '5+ Months',
                        ])
                        ->sortable(),

                    TextColumn::make('salesperson')
                        ->label('SalesPerson')
                        ->searchable()
                        ->sortable()
                        ->formatStateUsing(fn ($state) => $state ?: 'N/A'),

                    TextColumn::make('invoice_type')
                        ->label('Invoice Type')
                        ->getStateUsing(function (DebtorAging $record): string {
                            if (strpos($record->invoice_number, 'EPIN') === 0) {
                                return 'Product';
                            } elseif (strpos($record->invoice_number, 'EHIN') === 0) {
                                return 'HRDF';
                            } else {
                                return 'Other';
                            }
                        })
                        ->sortable(),

                    BadgeColumn::make('payment_status')
                        ->label('Payment Status')
                        ->getStateUsing(function (DebtorAging $record): string {
                            return $this->determinePaymentStatus($record);
                        })
                        ->colors([
                            'danger' => 'UnPaid',
                            'warning' => 'Partial Payment',
                            'success' => 'Full Payment',
                        ]),

                    // NO1: Toggle column - Currency Type
                    TextColumn::make('currency_code')
                        ->label('Currency')
                        ->sortable(),

                    // NO2: Toggle column - Support
                    TextColumn::make('support')
                        ->label('Support')
                        ->searchable()
                        ->sortable()
                        ->formatStateUsing(fn ($state) => $state ?: 'N/A'),

                    TextColumn::make('outstanding_rm')
                        ->label('Outstanding')
                        ->getStateUsing(function (DebtorAging $record): float {
                            return $record->currency_code === 'MYR'
                                ? $record->outstanding
                                : ($record->outstanding * $record->exchange_rate);
                        })
                        ->numeric(
                            decimalPlaces: fn() => $this->filterDecimalPlaces, // NO3: Dynamic decimal places
                            decimalSeparator: '.',
                            thousandsSeparator: ','
                        )
                        ->alignRight(),
                ])
                ->filters([
                    // NO3: Filter 1 - Decimal Places
                    SelectFilter::make('decimal_places')
                        ->options([
                            2 => '2 Decimal Places',
                            4 => '4 Decimal Places',
                        ])
                        ->label('Decimal Places')
                        ->default(2)
                        ->query(function (Builder $query, array $data) {
                            $this->filterDecimalPlaces = $data['value'] ?? 2;
                            return $query; // This doesn't filter data, just changes display
                        }),

                    // NO3: Filter 2 - Currency Type
                    SelectFilter::make('currency_code')
                        ->options(function () {
                            return DebtorAging::query()
                                ->whereNotIn('salesperson', $this->excludedSalespeople)
                                ->where('outstanding', '>', 0)
                                ->distinct()
                                ->whereNotNull('currency_code')
                                ->where('currency_code', '!=', '')
                                ->pluck('currency_code', 'currency_code')
                                ->toArray();
                        })
                        ->label('Currency Type')
                        ->multiple()
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['values'])) {
                                $this->filterCurrency = [];
                                return $query;
                            }

                            $this->filterCurrency = $data['values'];
                            $this->loadData();

                            return $query->whereIn('currency_code', $data['values']);
                        }),

                    // NO3: Filter 3 - Support
                    SelectFilter::make('support')
                        ->options(function () {
                            $options = DebtorAging::query()
                                ->whereNotIn('salesperson', $this->excludedSalespeople)
                                ->where('outstanding', '>', 0)
                                ->distinct()
                                ->whereNotNull('support')
                                ->where('support', '!=', '')
                                ->pluck('support', 'support')
                                ->toArray();

                            // Add blank option
                            $options['blank'] = 'Blank/Missing';
                            return $options;
                        })
                        ->label('Support')
                        ->multiple()
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['values'])) {
                                $this->filterSupport = [];
                                return $query;
                            }

                            $this->filterSupport = $data['values'];
                            $this->loadData();

                            return $query->where(function($q) use ($data) {
                                $q->whereIn('support', $data['values']);
                                // Include blank/null if selected
                                if (in_array('blank', $data['values'])) {
                                    $q->orWhereNull('support')->orWhere('support', '');
                                }
                            });
                        }),

                    // NO3: Filter 4 - Salesperson (including blank)
                    SelectFilter::make('salesperson')
                        ->options(function () {
                            $options = DebtorAging::query()
                                ->whereNotIn('salesperson', $this->excludedSalespeople)
                                ->where('outstanding', '>', 0)
                                ->distinct()
                                ->whereNotNull('salesperson')
                                ->where('salesperson', '!=', '')
                                ->pluck('salesperson', 'salesperson')
                                ->toArray();

                            // Add blank option
                            $options['blank'] = 'Blank/Missing';
                            return $options;
                        })
                        ->label('Salesperson')
                        ->multiple()
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['values'])) {
                                $this->filterSalesperson = [];
                                return $query;
                            }

                            $this->filterSalesperson = $data['values'];
                            $this->loadData();

                            return $query->where(function($q) use ($data) {
                                $q->whereIn('salesperson', $data['values']);
                                // Include blank/null if selected
                                if (in_array('blank', $data['values'])) {
                                    $q->orWhereNull('salesperson')->orWhere('salesperson', '');
                                }
                            });
                        }),

                    // Filter 5 - By Invoice Type
                    SelectFilter::make('invoice_type')
                        ->options([
                            'hrdf' => 'HRDF',
                            'product' => 'Product',
                        ])
                        ->label('Invoice Type')
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['value'])) {
                                $this->filterInvoiceType = null;
                                return $query;
                            }

                            $this->filterInvoiceType = $data['value'];
                            $this->loadData();

                            if ($data['value'] === 'hrdf') {
                                return $query->where('invoice_number', 'like', 'EHIN%');
                            } elseif ($data['value'] === 'product') {
                                return $query->where('invoice_number', 'like', 'EPIN%');
                            }
                        }),

                    // Filter 6 - By Payment Status
                    SelectFilter::make('payment_status')
                        ->options([
                            'unpaid' => 'Unpaid',
                            'partial' => 'Partial Payment',
                        ])
                        ->label('Payment Status')
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['value'])) {
                                $this->filterPaymentStatus = null;
                                return $query;
                            }

                            $this->filterPaymentStatus = $data['value'];
                            $this->loadData();

                            if ($data['value'] === 'unpaid') {
                                return $query->whereRaw('outstanding = invoice_amount');
                            } elseif ($data['value'] === 'partial') {
                                return $query->whereRaw('outstanding < invoice_amount')
                                    ->where('outstanding', '>', 0);
                            }
                        }),

                    // Filter 7 - By Debtor Aging
                    SelectFilter::make('debtor_aging')
                        ->options([
                            'current' => 'Current',
                            '1_month' => '1 Month',
                            '2_months' => '2 Months',
                            '3_months' => '3 Months',
                            '4_months' => '4 Months',
                            '5_plus_months' => '5+ Months',
                        ])
                        ->label('Debtor Aging')
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['value'])) {
                                $this->filterDebtorAging = null;
                                return $query;
                            }

                            $this->filterDebtorAging = $data['value'];
                            $this->loadData();

                            $this->applyDebtorAgingFilter($query, $data['value']);
                            return $query;
                        }),

                    // Filter 8 - By Date Range
                    Filter::make('invoice_date_range')
                        ->form([
                            DateRangePicker::make('date_range')
                                ->label('Invoice Date Range')
                                ->placeholder('Select date range'),
                        ])
                        ->query(function (Builder $query, array $data) {
                            if (!empty($data['date_range'])) {
                                [$start, $end] = explode(' - ', $data['date_range']);
                                $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                                $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                                $this->filterInvoiceDateFrom = $startDate->format('Y-m-d');
                                $this->filterInvoiceDateUntil = $endDate->format('Y-m-d');
                                $this->loadData();

                                $query->whereBetween('invoice_date', [$startDate, $endDate]);
                            } else {
                                $this->filterInvoiceDateFrom = null;
                                $this->filterInvoiceDateUntil = null;
                                $this->loadData();
                            }
                        })
                        ->indicateUsing(function (array $data) {
                            if (!empty($data['date_range'])) {
                                [$start, $end] = explode(' - ', $data['date_range']);
                                return 'Invoice Date: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                    ' to ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                            }
                            return null;
                        }),

                    // Filter 9 - By Year
                    SelectFilter::make('year')
                        ->options(function () {
                            $years = [];
                            $currentYear = date('Y');
                            for ($i = $currentYear; $i >= $currentYear - 3; $i--) {
                                $years[$i] = $i;
                            }
                            return $years;
                        })
                        ->label('Year')
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['value'])) {
                                $this->filterYear = null;
                                return $query;
                            }

                            $this->filterYear = $data['value'];
                            $this->loadData();

                            return $query->whereYear('invoice_date', $data['value']);
                        }),

                    // Filter 10 - By Month
                    SelectFilter::make('month')
                        ->options([
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
                        ])
                        ->label('Month')
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['value'])) {
                                $this->filterMonth = null;
                                return $query;
                            }

                            $this->filterMonth = $data['value'];
                            $this->loadData();

                            return $query->whereMonth('invoice_date', $data['value']);
                        }),

                    SelectFilter::make('invoice_age_days')
                        ->options([
                            '30_days' => 'More than 30 Days',
                            '60_days' => 'More than 60 Days',
                            '90_days' => 'More than 90 Days',
                            '120_days' => 'More than 120 Days',
                        ])
                        ->label('Invoice Age')
                        ->placeholder('All Invoice Ages')
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['value'])) {
                                $this->filterInvoiceAgeDays = null;
                                $this->loadData(); // Refresh stats
                                return $query;
                            }

                            $this->filterInvoiceAgeDays = $data['value'];
                            $this->loadData(); // Refresh stats

                            $this->applyInvoiceAgeDaysFilter($query, $data['value']);
                            return $query;
                        }),
                ])
                ->filtersFormColumns(3)
                ->defaultPaginationPageOption(50)
                ->paginated([50])
                ->paginationPageOptions([50, 100]);
    }

    protected function determinePaymentStatus($record)
    {
        if (!isset($record->outstanding) || (float)$record->outstanding === 0.0) {
            return 'Full Payment';
        }

        if ((float)$record->outstanding === (float)$record->invoice_amount) {
            return 'UnPaid';
        }

        if ((float)$record->outstanding < (float)$record->invoice_amount && (float)$record->outstanding > 0) {
            return 'Partial Payment';
        }

        return 'UnPaid';
    }

    protected function calculateAgingText(DebtorAging $record): string
    {
        if (!$record->aging_date) {
            return 'N/A';
        }

        $due = \Carbon\Carbon::parse($record->aging_date);
        $now = \Carbon\Carbon::now();

        if ($due->greaterThanOrEqualTo($now)) {
            return 'Current';
        }

        $monthsDiff = $now->diffInMonths($due);

        if ($monthsDiff == 0) {
            return 'Current';
        } elseif ($monthsDiff == 1) {
            return '1 Month';
        } elseif ($monthsDiff == 2) {
            return '2 Months';
        } elseif ($monthsDiff == 3) {
            return '3 Months';
        } elseif ($monthsDiff == 4) {
            return '4 Months';
        } else {
            return '5+ Months';
        }
    }

    // Stats methods (same as SalesDebtor but without salesperson filtering)
    protected function getAllDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $totalInvoices = $query->count();
        $totalAmount = $query->sum(DB::raw('
            CASE
                WHEN currency_code = "MYR" THEN outstanding
                WHEN outstanding IS NOT NULL AND exchange_rate IS NOT NULL THEN outstanding * exchange_rate
                ELSE 0
            END
        '));

        // return [
        //     'total_invoices' => $totalInvoices,
        //     'total_amount' => $totalAmount,
        //     'formatted_amount' => number_format($totalAmount, 2)
        // ];

        return [
            'total_invoices' => 0,
            'total_amount' => 0,
            'formatted_amount' => '0.00'
        ];
    }

    protected function getHrdfDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $query->where('invoice_number', 'like', 'EHIN%');
        $totalInvoices = $query->count();
        $totalAmount = $query->sum(DB::raw('
            CASE
                WHEN currency_code = "MYR" THEN outstanding
                WHEN outstanding IS NOT NULL AND exchange_rate IS NOT NULL THEN outstanding * exchange_rate
                ELSE 0
            END
        '));

        // return [
        //     'total_invoices' => $totalInvoices,
        //     'total_amount' => $totalAmount,
        //     'formatted_amount' => number_format($totalAmount, 2)
        // ];

        return [
            'total_invoices' => 0,
            'total_amount' => 0,
            'formatted_amount' => '0.00'
        ];
    }

    protected function getProductDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $query->where('invoice_number', 'like', 'EPIN%');
        $totalInvoices = $query->count();
        $totalAmount = $query->sum(DB::raw('
            CASE
                WHEN currency_code = "MYR" THEN outstanding
                WHEN outstanding IS NOT NULL AND exchange_rate IS NOT NULL THEN outstanding * exchange_rate
                ELSE 0
            END
        '));

        // return [
        //     'total_invoices' => $totalInvoices,
        //     'total_amount' => $totalAmount,
        //     'formatted_amount' => number_format($totalAmount, 2)
        // ];

        return [
            'total_invoices' => 0,
            'total_amount' => 0,
            'formatted_amount' => '0.00'
        ];
    }

    protected function getUnpaidDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $query->whereRaw('outstanding = invoice_amount');
        $totalInvoices = $query->count();
        $totalAmount = $query->sum(DB::raw('
            CASE
                WHEN currency_code = "MYR" THEN outstanding
                WHEN outstanding IS NOT NULL AND exchange_rate IS NOT NULL THEN outstanding * exchange_rate
                ELSE 0
            END
        '));

        // return [
        //     'total_invoices' => $totalInvoices,
        //     'total_amount' => $totalAmount,
        //     'formatted_amount' => number_format($totalAmount, 2)
        // ];

        return [
            'total_invoices' => 0,
            'total_amount' => 0,
            'formatted_amount' => '0.00'
        ];
    }

    protected function getPartialPaymentDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $query->whereRaw('outstanding < invoice_amount')->where('outstanding', '>', 0);
        $totalInvoices = $query->count();
        $totalAmount = $query->sum(DB::raw('
            CASE
                WHEN currency_code = "MYR" THEN outstanding
                WHEN outstanding IS NOT NULL AND exchange_rate IS NOT NULL THEN outstanding * exchange_rate
                ELSE 0
            END
        '));

        // return [
        //     'total_invoices' => $totalInvoices,
        //     'total_amount' => $totalAmount,
        //     'formatted_amount' => number_format($totalAmount, 2)
        // ];

        return [
            'total_invoices' => 0,
            'total_amount' => 0,
            'formatted_amount' => '0.00'
        ];
    }
}
