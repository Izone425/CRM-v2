<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Carbon\Carbon;

class InvoicesTable extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.invoices-table';
    protected static ?string $slug = 'invoices';

    // Map of salesperson names to their user IDs - Only these 7 will be shown
    protected static $salespersonUserIds = [
        'MUIM' => 6,
        'YASMIN' => 7,
        'FARHANAH' => 8,
        'JOSHUA' => 9,
        'AZIZ' => 10,
        'BARI' => 11,
        'VINCE' => 12,
    ];

    public $summaryData = [];

    public function mount(): void
    {
        $this->loadSummaryData();
    }

    // Helper method to get payment status for an invoice
    protected function getPaymentStatusForInvoice(string $invoiceNo): string
    {
        // Get the total invoice amount for this invoice number
        $totalInvoiceAmount = Invoice::where('invoice_no', $invoiceNo)->sum('invoice_amount');

        // Look for this invoice in debtor_agings table
        $debtorAging = DB::table('debtor_agings')
            ->where('invoice_number', $invoiceNo)
            ->first();

        // If no matching record in debtor_agings or outstanding is 0
        if (!$debtorAging || (float)$debtorAging->outstanding === 0.0) {
            return 'Full Payment';
        }

        // If outstanding equals total invoice amount
        if ((float)$debtorAging->outstanding === (float)$totalInvoiceAmount) {
            return 'UnPaid';
        }

        // If outstanding is less than invoice amount but greater than 0
        if ((float)$debtorAging->outstanding < (float)$totalInvoiceAmount && (float)$debtorAging->outstanding > 0) {
            return 'Partial Payment';
        }

        // Fallback (shouldn't normally reach here)
        return 'UnPaid';
    }

    public function loadSummaryData(): void
    {
        $today = Carbon::today();
        $currentYear = $today->year;
        $currentMonth = $today->month;

        // Determine date ranges based on current date
        $allYearStart = Carbon::create($currentYear - 1, 1, 1); // Previous year January 1st
        $allYearEnd = $today; // Today

        $currentYearStart = Carbon::create($currentYear, 1, 1); // Current year January 1st
        $currentYearEnd = $today; // Today

        $currentMonthStart = Carbon::create($currentYear, $currentMonth, 1); // Current month 1st
        $currentMonthEnd = $today; // Today

        // Get allowed salespersons based on user role
        $allowedSalespersons = $this->getAllowedSalespersons();

        // Calculate summary data
        $this->summaryData = [
            'all_year' => $this->calculateSummaryStats($allYearStart, $allYearEnd, $allowedSalespersons),
            'current_year' => $this->calculateSummaryStats($currentYearStart, $currentYearEnd, $allowedSalespersons),
            'current_month' => $this->calculateSummaryStats($currentMonthStart, $currentMonthEnd, $allowedSalespersons),
            'hrdf_all_year' => $this->calculateSummaryStats($allYearStart, $allYearEnd, $allowedSalespersons, 'EHIN'),
            'product_all_year' => $this->calculateSummaryStats($allYearStart, $allYearEnd, $allowedSalespersons, 'EPIN'),
        ];
    }

    protected function getAllowedSalespersons(): array
    {
        $allowedSalespersons = array_keys(static::$salespersonUserIds);

        // Filter for individual salespersons to see only their own data
        if (Auth::check() && Auth::user()->role_id === 2) {
            $userId = Auth::id();
            $salespersonName = array_search($userId, static::$salespersonUserIds);

            if ($salespersonName) {
                return [$salespersonName];
            } else {
                return []; // No results if user not in mapping
            }
        }

        return $allowedSalespersons;
    }

    protected function calculateSummaryStats(Carbon $startDate, Carbon $endDate, array $allowedSalespersons, string $invoicePrefix = null): array
    {
        $baseQuery = Invoice::query()
            ->whereIn('salesperson', $allowedSalespersons)
            ->whereBetween('invoice_date', [$startDate, $endDate]);

        if ($invoicePrefix) {
            $baseQuery->where('invoice_no', 'like', $invoicePrefix . '%');
        }

        // Group by invoice_no to get unique invoices
        $invoiceNos = $baseQuery->distinct('invoice_no')->pluck('invoice_no');

        $stats = [
            'full_payment_amount' => 0,
            'partial_payment_amount' => 0,
            'unpaid_amount' => 0,
            'total_amount' => 0,
        ];

        foreach ($invoiceNos as $invoiceNo) {
            // Calculate total invoice amount for this invoice
            $totalInvoiceAmount = Invoice::where('invoice_no', $invoiceNo)->sum('invoice_amount');
            $stats['total_amount'] += $totalInvoiceAmount;

            // Use the same payment status logic
            $paymentStatus = $this->getPaymentStatusForInvoice($invoiceNo);

            switch ($paymentStatus) {
                case 'Full Payment':
                    $stats['full_payment_amount'] += $totalInvoiceAmount;
                    break;
                case 'Partial Payment':
                    $stats['partial_payment_amount'] += $totalInvoiceAmount;
                    break;
                case 'UnPaid':
                    $stats['unpaid_amount'] += $totalInvoiceAmount;
                    break;
            }
        }

        return $stats;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query())
            ->defaultPaginationPageOption(50)
            ->heading('Invoices')
            ->columns([
                Tables\Columns\TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_no')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Local Subtotal')
                    ->money('MYR')
                    ->sortable()
                    ->getStateUsing(function (Invoice $record): float {
                        // Calculate the sum for this invoice_no
                        return Invoice::where('invoice_no', $record->invoice_no)->sum('invoice_amount');
                    })
                    ->summarize([
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Grand Total')
                            ->using(function ($query) {
                                // Get the grouped results
                                $groupedResults = $query->get();
                                $grandTotal = 0;

                                // Calculate total for each unique invoice
                                foreach ($groupedResults as $record) {
                                    $grandTotal += Invoice::where('invoice_no', $record->invoice_no)->sum('invoice_amount');
                                }

                                return 'RM ' . number_format($grandTotal, 2);
                            }),
                    ]),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment Status')
                    ->colors([
                        'danger' => 'UnPaid',
                        'warning' => 'Partial Payment',
                        'success' => 'Full Payment',
                    ])
                    ->getStateUsing(function (Invoice $record): string {
                        return $this->getPaymentStatusForInvoice($record->invoice_no);
                    })
                    ->sortable()
            ])
            ->defaultSort('invoice_date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // Only show invoices from the 7 specified salespersons
                $allowedSalespersons = array_keys(static::$salespersonUserIds);

                $query = $query->select([
                    DB::raw('MIN(id) as id'),
                    'salesperson',
                    'invoice_no',
                    'invoice_date',
                    'company_name',
                    DB::raw('SUM(invoice_amount) as total_invoice_amount')
                ])
                ->whereIn('salesperson', $allowedSalespersons)
                ->groupBy('invoice_no', 'salesperson', 'invoice_date', 'company_name')
                ->orderBy('invoice_date', 'desc');

                // Additional filter for individual salespersons to see only their own data
                if (Auth::check() && Auth::user()->role_id === 2) {
                    $userId = Auth::id();

                    // Find the salesperson name that corresponds to the current user ID
                    $salespersonName = array_search($userId, static::$salespersonUserIds);

                    if ($salespersonName) {
                        // Filter invoices to only show those belonging to this salesperson
                        $query->where('salesperson', $salespersonName);
                    } else {
                        // If the user ID is not in our mapping, don't show any results
                        $query->where('id', 0); // This will return no results
                    }
                }

                return $query;
            })
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'UnPaid' => 'UnPaid',
                        'Partial Payment' => 'Partial Payment',
                        'Full Payment' => 'Full Payment',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $targetStatus = $data['value'];
                        $allowedSalespersons = array_keys(static::$salespersonUserIds);

                        // Get all invoices that match the target payment status
                        $matchingInvoiceNos = collect();

                        $allInvoices = Invoice::query()
                            ->whereIn('salesperson', $allowedSalespersons)
                            ->select('invoice_no')
                            ->distinct()
                            ->pluck('invoice_no');

                        foreach ($allInvoices as $invoiceNo) {
                            $paymentStatus = $this->getPaymentStatusForInvoice($invoiceNo);
                            if ($paymentStatus === $targetStatus) {
                                $matchingInvoiceNos->push($invoiceNo);
                            }
                        }

                        // Filter the main query to only include matching invoice numbers
                        return $query->whereIn('invoice_no', $matchingInvoiceNos->toArray());
                    }),

                SelectFilter::make('invoice_type')
                    ->label('Invoice Type')
                    ->options([
                        'EPIN' => 'Product Invoice (EPIN)',
                        'EHIN' => 'HRDF Invoice (EHIN)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                function (Builder $query, $prefix): Builder {
                                    return $query->where('invoice_no', 'like', $prefix . '%');
                                }
                            );
                    }),
                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        // Only show the 7 specified salespersons in the filter
                        $allowedSalespersons = array_keys(static::$salespersonUserIds);

                        try {
                            return Invoice::query()
                                ->select('salesperson')
                                ->distinct()
                                ->whereNotNull('salesperson')
                                ->where('salesperson', '!=', '')
                                ->whereIn('salesperson', $allowedSalespersons)
                                ->orderBy('salesperson')
                                ->pluck('salesperson', 'salesperson')
                                ->toArray();
                        } catch (\Exception $e) {
                            // In case of database error, return empty array
                            return [];
                        }
                    })
                    ->visible(fn () => Auth::check() && Auth::user()->role_id === 3),

                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        $allowedSalespersons = array_keys(static::$salespersonUserIds);

                        return Invoice::selectRaw('YEAR(invoice_date) as year')
                            ->whereIn('salesperson', $allowedSalespersons)
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $year): Builder => $query->whereYear('invoice_date', $year)
                            );
                    }),

                SelectFilter::make('month')
                    ->label('Month')
                    ->options([
                        '1' => 'January',
                        '2' => 'February',
                        '3' => 'March',
                        '4' => 'April',
                        '5' => 'May',
                        '6' => 'June',
                        '7' => 'July',
                        '8' => 'August',
                        '9' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $month): Builder => $query->whereMonth('invoice_date', $month)
                            );
                    }),
            ])
            ->actions([
            ])
            ->bulkActions([]);
    }
}
