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

class SalesDebtor extends Page implements HasTable
{
    use InteractsWithTable;

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
    public $filterInvoiceDateFrom = null;
    public $filterInvoiceDateUntil = null;
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

        // Apply date filters if set
        if ($this->filterInvoiceDateFrom) {
            $query->whereDate('invoice_date', '>=', $this->filterInvoiceDateFrom);
        }

        if ($this->filterInvoiceDateUntil) {
            $query->whereDate('invoice_date', '<=', $this->filterInvoiceDateUntil);
        }

        return $query;
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
                        ->label('Company')
                        ->searchable()
                        ->sortable()
                        ->wrap(),

                    TextColumn::make('invoice_number')
                        ->label('Invoice Number')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('invoice_date')
                        ->label('Date')
                        ->date('d/m/Y')
                        ->sortable(),

                    TextColumn::make('salesperson')
                        ->label('Salesperson')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('invoice_type')
                        ->label('Type')
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

                    TextColumn::make('outstanding_rm')
                        ->label('Outstanding (RM)')
                        ->getStateUsing(function (DebtorAging $record): float {
                            return $record->currency_code === 'MYR'
                                ? $record->outstanding
                                : ($record->outstanding * $record->exchange_rate);
                        })
                        ->money('MYR')
                        ->alignRight(),
                ])
                ->filters([
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

                    Filter::make('invoice_date')
                        ->form([
                            DatePicker::make('invoice_date_from')
                                ->label('From')
                                ->placeholder('From'),
                            DatePicker::make('invoice_date_until')
                                ->label('Until')
                                ->placeholder('Until'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            $this->filterInvoiceDateFrom = $data['invoice_date_from'] ?? null;
                            $this->filterInvoiceDateUntil = $data['invoice_date_until'] ?? null;

                            $this->loadData(); // Refresh stats

                            return $query
                                ->when(
                                    $data['invoice_date_from'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '>=', $date),
                                )
                                ->when(
                                    $data['invoice_date_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '<=', $date),
                                );
                        }),
            ])
            ->filtersFormColumns(2)
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

    // Stats methods to remain unchanged
    protected function getAllDebtorStats($baseQuery)
    {
        // Existing implementation
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
            'formatted_amount' => Number::currency($totalAmount, 'MYR')
        ];
    }

    protected function getHrdfDebtorStats($baseQuery)
    {
        // Existing implementation
        $query = clone $baseQuery;
        // Use the invoice number pattern for HRDF
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
            'formatted_amount' => Number::currency($totalAmount, 'MYR')
        ];
    }

    protected function getProductDebtorStats($baseQuery)
    {
        // Existing implementation
        $query = clone $baseQuery;
        // Use the invoice number pattern for Product
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
            'formatted_amount' => Number::currency($totalAmount, 'MYR')
        ];
    }

    protected function getUnpaidDebtorStats($baseQuery)
    {
        // Existing implementation
        $query = clone $baseQuery;
        // Instead of filtering by payment_status column, use the raw outstanding = invoice_amount condition
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
            'formatted_amount' => Number::currency($totalAmount, 'MYR')
        ];
    }

    protected function getPartialPaymentDebtorStats($baseQuery)
    {
        // Existing implementation
        $query = clone $baseQuery;
        // Instead of filtering by payment_status column, use the raw outstanding < invoice_amount condition
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
            'formatted_amount' => Number::currency($totalAmount, 'MYR')
        ];
    }
}
