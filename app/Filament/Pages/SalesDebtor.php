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

class SalesDebtor extends Page implements HasTable
{
    use InteractsWithTable;

    public $filterInvoiceAgeDays = null;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Sales Debtor Dashboard';
    protected static ?string $title = 'Sales Debtor Dashboard';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.sales-debtor';

    public array $salespeople = [
        'MUIM',
        'YASMIN',
        'FARHANAH',
        'JOSHUA',
        'AZIZ',
        'BARI',
        'VINCE'
    ];

    // Mapping of salesperson names to user IDs
    protected array $salespersonUserIds = [
        'MUIM' => 6,
        'YASMIN' => 7,
        'FARHANAH' => 8,
        'JOSHUA' => 9,
        'AZIZ' => 10,
        'BARI' => 11,
        'VINCE' => 12
    ];

    public $allDebtorStats;
    public $hrdfDebtorStats;
    public $productDebtorStats;
    public $unpaidDebtorStats;
    public $partialPaymentDebtorStats;

    // Store the filtered salespeople based on user role
    public $filteredSalespeople;

    public $filterSalesperson = [];
    public $filterInvoiceType = null;
    public $filterPaymentStatus = null;
    public $filterDebtorAging = null;
    public $filterInvoiceDateFrom = null;
    public $filterInvoiceDateUntil = null;
    public $filterYear = null;
    public $filterMonth = null;

    public function mount(): void
    {
        // Filter salespeople based on user role
        $this->filterSalespeopleByUserRole();

        // Load data with filtered salespeople
        $this->loadData();
    }

    protected function filterSalespeopleByUserRole(): void
    {
        $user = auth()->user();

        // If user is admin (role_id = 3), they can see all salespeople
        if ($user->role_id == 3) {
            $this->filteredSalespeople = $this->salespeople;
            return;
        }

        // Find which salesperson corresponds to the current user
        $userSalesperson = null;
        foreach ($this->salespersonUserIds as $salesperson => $userId) {
            if ($userId == $user->id) {
                $userSalesperson = $salesperson;
                break;
            }
        }

        // If user is a salesperson, they can only see their own data
        if ($userSalesperson) {
            $this->filteredSalespeople = [$userSalesperson];
        } else {
            // If user is not in the salesperson list, default to empty to show no data
            $this->filteredSalespeople = [];
        }
    }

    protected function loadData(): void
    {
        // Get base query with filtered salespeople and apply all current filters
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

        // Filter by the filtered salespeople
        // If additional salesperson filters are selected, use those instead
        if (!empty($this->filterSalesperson)) {
            $query->whereIn('salesperson', $this->filterSalesperson);
        } else {
            $query->whereIn('salesperson', $this->filteredSalespeople);
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

        // Apply invoice age days filter if set
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

    protected function applyInvoiceAgeDaysFilter($query, $daysFilter)
    {
        $today = Carbon::now()->startOfDay();

        switch ($daysFilter) {
            case '30_days':
                // Show invoices between 30 days ago and today
                $cutoffDate = $today->copy()->subDays(30);
                $query->whereBetween('invoice_date', [$cutoffDate, $today]);
                break;
            case '60_days':
                // Show invoices between 60 days ago and today
                $cutoffDate = $today->copy()->subDays(60);
                $query->whereBetween('invoice_date', [$cutoffDate, $today]);
                break;
            case '90_days':
                // Show invoices between 90 days ago and today
                $cutoffDate = $today->copy()->subDays(90);
                $query->whereBetween('invoice_date', [$cutoffDate, $today]);
                break;
            case '120_days':
                // Show invoices between 120 days ago and today
                $cutoffDate = $today->copy()->subDays(120);
                $query->whereBetween('invoice_date', [$cutoffDate, $today]);
                break;
        }
    }

    protected function applyDebtorAgingFilter($query, $agingFilter)
    {
        $now = Carbon::now();

        switch ($agingFilter) {
            case 'current':
                // Current month invoices (not overdue or overdue but less than a month)
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

    public function table(Table $table): Table
    {
        return $table
            ->query(DebtorAging::query()
                ->whereIn('salesperson', $this->filteredSalespeople)
                ->where('outstanding', '>', 0))
                ->defaultSort('invoice_date', 'desc')
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
                        ->sortable(),

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
                        ->label('Payment Type')
                        ->getStateUsing(function (DebtorAging $record): string {
                            return $this->determinePaymentStatus($record);
                        })
                        ->colors([
                            'danger' => 'UnPaid',
                            'warning' => 'Partial Payment',
                            'success' => 'Full Payment',
                        ]),

                    TextColumn::make('outstanding_rm')
                        ->label('Outstanding (RM)')
                        ->getStateUsing(function (DebtorAging $record): float {
                            return $record->currency_code === 'MYR'
                                ? $record->outstanding
                                : ($record->outstanding * $record->exchange_rate);
                        })
                        ->numeric(
                            decimalPlaces: 2,
                            decimalSeparator: '.',
                            thousandsSeparator: ','
                        )
                        ->alignRight(),
                ])
                ->filters([
                    // Filter 1 - By SalesPerson
                    SelectFilter::make('salesperson')
                        ->options(array_combine($this->salespeople, $this->salespeople))
                        ->placeholder('All Salespeople')
                        ->label('Salesperson')
                        ->multiple()
                        ->visible(fn() => count($this->filteredSalespeople) > 1)
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['values'])) {
                                $this->filterSalesperson = [];
                                return $query;
                            }

                            $this->filterSalesperson = $data['values'];
                            $this->loadData(); // Refresh stats

                            return $query->whereIn('salesperson', $data['values']);
                        }),

                    // Filter 2 - By Invoice Type
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
                            $this->loadData(); // Refresh stats

                            if ($data['value'] === 'hrdf') {
                                return $query->where('invoice_number', 'like', 'EHIN%');
                            } elseif ($data['value'] === 'product') {
                                return $query->where('invoice_number', 'like', 'EPIN%');
                            }
                        }),

                    // Filter 3 - By Payment Status
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
                            $this->loadData(); // Refresh stats

                            if ($data['value'] === 'unpaid') {
                                return $query->whereRaw('outstanding = invoice_amount');
                            } elseif ($data['value'] === 'partial') {
                                return $query->whereRaw('outstanding < invoice_amount')
                                    ->where('outstanding', '>', 0);
                            }
                        }),

                    // Filter 4 - By Debtor Aging
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
                            $this->loadData(); // Refresh stats

                            $this->applyDebtorAgingFilter($query, $data['value']);
                            return $query;
                        }),

                    // Filter 5 - By Date Range (using DateRangePicker)
                    Filter::make('invoice_date_range')
                        ->form([
                            DateRangePicker::make('date_range')
                                ->label('Invoice Date Range')
                                ->placeholder('Select date range'),
                        ])
                        ->query(function (Builder $query, array $data) {
                            if (!empty($data['date_range'])) {
                                // Parse the date range from the "start - end" format
                                [$start, $end] = explode(' - ', $data['date_range']);

                                // Ensure valid dates
                                $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                                $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                                // Set the filter properties for stats refresh
                                $this->filterInvoiceDateFrom = $startDate->format('Y-m-d');
                                $this->filterInvoiceDateUntil = $endDate->format('Y-m-d');

                                $this->loadData(); // Refresh stats

                                // Apply the filter
                                $query->whereBetween('invoice_date', [$startDate, $endDate]);
                            } else {
                                // Clear filters when empty
                                $this->filterInvoiceDateFrom = null;
                                $this->filterInvoiceDateUntil = null;
                                $this->loadData();
                            }
                        })
                        ->indicateUsing(function (array $data) {
                            if (!empty($data['date_range'])) {
                                // Parse the date range for display
                                [$start, $end] = explode(' - ', $data['date_range']);

                                return 'Invoice Date: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                    ' to ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                            }
                            return null;
                        }),

                    // Filter 6 - By Year
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
                            $this->loadData(); // Refresh stats

                            return $query->whereYear('invoice_date', $data['value']);
                        }),

                    // Filter 7 - By Month
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
                            $this->loadData(); // Refresh stats

                            return $query->whereMonth('invoice_date', $data['value']);
                        }),

                    SelectFilter::make('invoice_age_days')
                        ->options([
                            '30_days' => '30 Days',
                            '60_days' => '60 Days',
                            '90_days' => '90 Days',
                            '120_days' => '120 Days',
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

    protected function getBaseQuery()
    {
        $query = DebtorAging::query();

        // Filter only for unpaid or partial payment debtors
        $query->where(function ($q) {
            // Unpaid or partial payment cases:
            // outstanding > 0, meaning it's either unpaid or partially paid
            $q->where('outstanding', '>', 0);
        });

        // Filter by the filtered salespeople
        $query->whereIn('salesperson', $this->filteredSalespeople);

        return $query;
    }

    protected function determinePaymentStatus($record)
    {
        // If no outstanding amount or it's 0
        if (!isset($record->outstanding) || (float)$record->outstanding === 0.0) {
            return 'Full Payment';
        }

        // If outstanding equals total invoice amount
        if ((float)$record->outstanding === (float)$record->invoice_amount) {
            return 'UnPaid';
        }

        // If outstanding is less than invoice amount but greater than 0
        if ((float)$record->outstanding < (float)$record->invoice_amount && (float)$record->outstanding > 0) {
            return 'Partial Payment';
        }

        // Fallback
        return 'UnPaid';
    }

    protected function determineInvoiceType($invoiceNumber)
    {
        // Invoice numbers starting with EPIN indicate Product invoices
        if (strpos($invoiceNumber, 'EPIN') === 0) {
            return 'Product';
        }

        // Invoice numbers starting with EHIN indicate HRDF invoices
        if (strpos($invoiceNumber, 'EHIN') === 0) {
            return 'HRDF';
        }

        // Check if the invoice_type is already set in the record
        // This is a fallback for records that might not follow the naming convention
        return 'Other';
    }

    // Stats methods remain unchanged
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

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'formatted_amount' => number_format($totalAmount, 2)
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

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'formatted_amount' => number_format($totalAmount, 2)
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

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'formatted_amount' => number_format($totalAmount, 2)
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

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'formatted_amount' => number_format($totalAmount, 2)
        ];
    }

    protected function getPartialPaymentDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $query->whereRaw('outstanding < invoice_amount')
            ->where('outstanding', '>', 0);

        $totalInvoices = $query->count();
        $totalAmount = $query->sum(DB::raw('
            CASE
                WHEN currency_code = "MYR" THEN outstanding
                WHEN outstanding IS NOT NULL AND exchange_rate IS NOT NULL THEN outstanding * exchange_rate
                ELSE 0
            END
        '));

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'formatted_amount' => number_format($totalAmount, 2)
        ];
    }

    protected function calculateAgingText(DebtorAging $record): string
    {
        if (!$record->aging_date) {
            return 'N/A';
        }

        $due = \Carbon\Carbon::parse($record->aging_date);
        $now = \Carbon\Carbon::now();

        // For current month invoices (not overdue)
        if ($due->greaterThanOrEqualTo($now)) {
            return 'Current';
        }

        // For overdue invoices, calculate months difference
        $monthsDiff = $now->diffInMonths($due);

        if ($monthsDiff == 0) {
            // Still in the same month (overdue but less than a month)
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

    protected function calculateAgingColor(DebtorAging $record): string
    {
        if (!$record->aging_date) {
            return 'gray';
        }

        $due = \Carbon\Carbon::parse($record->aging_date);
        $now = \Carbon\Carbon::now();

        if ($due->greaterThanOrEqualTo($now)) {
            return 'success'; // Green for current
        }

        $monthsDiff = $now->diffInMonths($due);

        return match($monthsDiff) {
            0 => 'success',     // Green
            1 => 'info',        // Blue
            2 => 'warning',     // Yellow
            3 => 'warning',     // Orange
            4 => 'danger',      // Red
            default => 'danger' // Dark red for 5+ months
        };
    }
}
