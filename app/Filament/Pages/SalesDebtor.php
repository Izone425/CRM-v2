<?php

namespace App\Filament\Pages;

use App\Models\DebtorAging;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
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

    // Helper method to get total invoice amount from invoice_details (excluding certain item codes)
    protected function getTotalInvoiceAmount(string $invoiceNo): float
    {
        // Get the invoice first - exclude voided invoices
        $invoice = Invoice::where('invoice_no', $invoiceNo)
            ->where('invoice_status', '!=', 'V')
            ->first();

        if (!$invoice) {
            return 0;
        }

        $excludedItemCodes = [
            'SHIPPING',
            'BANKCHG',
            'DEPOSIT-MYR',
            'F.COMMISSION',
            'L.COMMISSION',
            'L.ENTITLEMENT',
            'MGT FEES',
            'PG.COMMISSION'
        ];

        return InvoiceDetail::where('doc_key', $invoice->doc_key)
            ->whereNotIn('item_code', $excludedItemCodes)
            ->sum('local_sub_total');
    }

    // Helper method to recalculate payment status based on actual invoice amount
    protected function getRecalculatedPaymentStatus(DebtorAging $record): string
    {
        // Get actual invoice amount from invoice_details (excluding certain items)
        $actualInvoiceAmount = $this->getTotalInvoiceAmount($record->invoice_number);

        if ($actualInvoiceAmount <= 0) {
            return 'Charge Out';
        }

        $outstanding = (float)$record->outstanding;

        // If no outstanding amount or it's 0
        if ($outstanding === 0.0) {
            return 'Full Payment';
        }

        // If outstanding equals actual invoice amount
        if (abs($outstanding - $actualInvoiceAmount) < 0.01) { // Use small epsilon for float comparison
            return 'UnPaid';
        }

        // If outstanding is less than actual invoice amount but greater than 0
        if ($outstanding < $actualInvoiceAmount && $outstanding > 0) {
            return 'Partial Payment';
        }

        // If outstanding is greater than actual invoice amount (shouldn't happen normally)
        if ($outstanding > $actualInvoiceAmount) {
            return 'UnPaid';
        }

        // Fallback
        return 'UnPaid';
    }

    // Helper method to get recalculated outstanding in MYR
    protected function getRecalculatedOutstanding(DebtorAging $record): float
    {
        $actualInvoiceAmount = $this->getTotalInvoiceAmount($record->invoice_number);
        $originalInvoiceAmount = (float)$record->invoice_amount;
        $outstanding = (float)$record->outstanding;

        // If invoice amounts match, return original outstanding
        if (abs($originalInvoiceAmount - $actualInvoiceAmount) < 0.01) {
            return $record->currency_code === 'MYR'
                ? $outstanding
                : ($outstanding * $record->exchange_rate);
        }

        // Calculate the ratio and adjust outstanding
        if ($originalInvoiceAmount > 0) {
            $ratio = $actualInvoiceAmount / $originalInvoiceAmount;
            $adjustedOutstanding = $outstanding * $ratio;

            return $record->currency_code === 'MYR'
                ? $adjustedOutstanding
                : ($adjustedOutstanding * $record->exchange_rate);
        }

        return $record->currency_code === 'MYR'
            ? $outstanding
            : ($outstanding * $record->exchange_rate);
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

        // Get non-voided invoice numbers first
        $nonVoidedInvoiceNumbers = Invoice::where('invoice_status', '!=', 'V')
            ->pluck('invoice_no')
            ->toArray();

        // Filter by non-voided invoices
        $query->whereIn('invoice_number', $nonVoidedInvoiceNumbers);

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

        // Apply payment status filter if set (now using recalculated amounts)
        if ($this->filterPaymentStatus === 'unpaid') {
            // Get invoice numbers where outstanding matches actual invoice amount
            $unpaidInvoiceNumbers = [];
            $allRecords = DebtorAging::whereIn('salesperson', $this->filteredSalespeople)
                ->whereIn('invoice_number', $nonVoidedInvoiceNumbers)
                ->where('outstanding', '>', 0)
                ->get();

            foreach ($allRecords as $record) {
                if ($this->getRecalculatedPaymentStatus($record) === 'UnPaid') {
                    $unpaidInvoiceNumbers[] = $record->invoice_number;
                }
            }

            $query->whereIn('invoice_number', $unpaidInvoiceNumbers);
        } elseif ($this->filterPaymentStatus === 'partial') {
            // Get invoice numbers where outstanding is less than actual invoice amount
            $partialInvoiceNumbers = [];
            $allRecords = DebtorAging::whereIn('salesperson', $this->filteredSalespeople)
                ->whereIn('invoice_number', $nonVoidedInvoiceNumbers)
                ->where('outstanding', '>', 0)
                ->get();

            foreach ($allRecords as $record) {
                if ($this->getRecalculatedPaymentStatus($record) === 'Partial Payment') {
                    $partialInvoiceNumbers[] = $record->invoice_number;
                }
            }

            $query->whereIn('invoice_number', $partialInvoiceNumbers);
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
                $cutoffDate = $today->copy()->subDays(30);
                $query->where('invoice_date', '<', $cutoffDate);
                break;
            case '60_days':
                $cutoffDate = $today->copy()->subDays(60);
                $query->where('invoice_date', '<', $cutoffDate);
                break;
            case '90_days':
                $cutoffDate = $today->copy()->subDays(90);
                $query->where('invoice_date', '<', $cutoffDate);
                break;
            case '120_days':
                $cutoffDate = $today->copy()->subDays(120);
                $query->where('invoice_date', '<', $cutoffDate);
                break;
        }
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

    public function table(Table $table): Table
    {
        $nonVoidedInvoiceNumbers = Invoice::where('invoice_status', '!=', 'V')
            ->pluck('invoice_no')
            ->toArray();

        return $table
            ->query(DebtorAging::query()
                ->whereIn('salesperson', $this->filteredSalespeople)
                ->whereIn('invoice_number', $nonVoidedInvoiceNumbers)
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
                            return $this->getRecalculatedPaymentStatus($record);
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
                            $this->loadData();

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
                            $this->loadData();

                            if ($data['value'] === 'hrdf') {
                                return $query->where('invoice_number', 'like', 'EHIN%');
                            } elseif ($data['value'] === 'product') {
                                return $query->where('invoice_number', 'like', 'EPIN%');
                            }
                        }),

                    // Filter 3 - By Payment Status (updated to use recalculated status)
                    SelectFilter::make('payment_status')
                        ->options([
                            'unpaid' => 'Unpaid',
                            'partial' => 'Partial Payment',
                        ])
                        ->label('Payment Status')
                        ->query(function (Builder $query, array $data) {
                            if (empty($data['value'])) {
                                $this->filterPaymentStatus = null;
                                $this->loadData();
                                return $query;
                            }

                            $this->filterPaymentStatus = $data['value'];
                            $this->loadData();

                            // Get non-voided invoice numbers
                            $nonVoidedInvoiceNumbers = Invoice::where('invoice_status', '!=', 'V')
                                ->pluck('invoice_no')
                                ->toArray();

                            // Get matching invoice numbers based on recalculated payment status
                            $matchingInvoiceNumbers = [];
                            $allRecords = DebtorAging::whereIn('salesperson', $this->filteredSalespeople)
                                ->whereIn('invoice_number', $nonVoidedInvoiceNumbers)
                                ->where('outstanding', '>', 0)
                                ->get();

                            foreach ($allRecords as $record) {
                                $status = $this->getRecalculatedPaymentStatus($record);
                                if ($data['value'] === 'unpaid' && $status === 'UnPaid') {
                                    $matchingInvoiceNumbers[] = $record->invoice_number;
                                } elseif ($data['value'] === 'partial' && $status === 'Partial Payment') {
                                    $matchingInvoiceNumbers[] = $record->invoice_number;
                                }
                            }

                            return $query->whereIn('invoice_number', $matchingInvoiceNumbers);
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
                            $this->loadData();

                            $this->applyDebtorAgingFilter($query, $data['value']);
                            return $query;
                        }),

                    // Filter 5 - By Date Range
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
                            $this->loadData();

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
                                $this->loadData();
                                return $query;
                            }

                            $this->filterInvoiceAgeDays = $data['value'];
                            $this->loadData();

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
        // Get non-voided invoice numbers
        $nonVoidedInvoiceNumbers = Invoice::where('invoice_status', '!=', 'V')
            ->pluck('invoice_no')
            ->toArray();

        $query = DebtorAging::query();
        $query->where('outstanding', '>', 0);
        $query->whereIn('salesperson', $this->filteredSalespeople);
        $query->whereIn('invoice_number', $nonVoidedInvoiceNumbers);
        return $query;
    }

    protected function determinePaymentStatus($record)
    {
        return $this->getRecalculatedPaymentStatus($record);
    }

    protected function determineInvoiceType($invoiceNumber)
    {
        if (strpos($invoiceNumber, 'EPIN') === 0) {
            return 'Product';
        }
        if (strpos($invoiceNumber, 'EHIN') === 0) {
            return 'HRDF';
        }
        return 'Other';
    }

    // Updated stats methods to use recalculated amounts
    protected function getAllDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $records = $query->get();

        $totalInvoices = $records->count();
        $totalAmount = 0;

        foreach ($records as $record) {
            $totalAmount += $this->getRecalculatedOutstanding($record);
        }

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
        $records = $query->get();

        $totalInvoices = $records->count();
        $totalAmount = 0;

        foreach ($records as $record) {
            $totalAmount += $this->getRecalculatedOutstanding($record);
        }

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
        $records = $query->get();

        $totalInvoices = $records->count();
        $totalAmount = 0;

        foreach ($records as $record) {
            $totalAmount += $this->getRecalculatedOutstanding($record);
        }

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'formatted_amount' => number_format($totalAmount, 2)
        ];
    }

    protected function getUnpaidDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $records = $query->get();

        $totalInvoices = 0;
        $totalAmount = 0;

        foreach ($records as $record) {
            if ($this->getRecalculatedPaymentStatus($record) === 'UnPaid') {
                $totalInvoices++;
                $totalAmount += $this->getRecalculatedOutstanding($record);
            }
        }

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'formatted_amount' => number_format($totalAmount, 2)
        ];
    }

    protected function getPartialPaymentDebtorStats($baseQuery)
    {
        $query = clone $baseQuery;
        $records = $query->get();

        $totalInvoices = 0;
        $totalAmount = 0;

        foreach ($records as $record) {
            if ($this->getRecalculatedPaymentStatus($record) === 'Partial Payment') {
                $totalInvoices++;
                $totalAmount += $this->getRecalculatedOutstanding($record);
            }
        }

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

    protected function calculateAgingColor(DebtorAging $record): string
    {
        if (!$record->aging_date) {
            return 'gray';
        }

        $due = \Carbon\Carbon::parse($record->aging_date);
        $now = \Carbon\Carbon::now();

        if ($due->greaterThanOrEqualTo($now)) {
            return 'success';
        }

        $monthsDiff = $now->diffInMonths($due);

        return match($monthsDiff) {
            0 => 'success',
            1 => 'info',
            2 => 'warning',
            3 => 'warning',
            4 => 'danger',
            default => 'danger'
        };
    }
}
