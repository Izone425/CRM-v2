<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
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
use Illuminate\Support\Facades\Cache;

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

    // Cache for invoice amounts
    protected $invoiceAmountCache = [];
    protected $creditNoteCache = [];

    // Cache for payment statuses
    protected $paymentStatusCache = [];

    public function mount(): void
    {
        $this->loadSummaryData();
    }

    // Helper method to get total invoice amount from invoice_details (with caching)
    protected function getTotalInvoiceAmount(string $docKey): float
    {
        // Check cache first
        if (isset($this->invoiceAmountCache[$docKey])) {
            return $this->invoiceAmountCache[$docKey];
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

        $amount = InvoiceDetail::where('doc_key', $docKey)
            ->whereNotIn('item_code', $excludedItemCodes)
            ->sum('local_sub_total');

        // Store in cache
        $this->invoiceAmountCache[$docKey] = $amount;

        return $amount;
    }

    // Batch load invoice amounts for multiple doc_keys
    protected function batchLoadInvoiceAmounts(array $docKeys): array
    {
        if (empty($docKeys)) {
            return [];
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

        $results = InvoiceDetail::whereIn('doc_key', $docKeys)
            ->whereNotIn('item_code', $excludedItemCodes)
            ->select('doc_key', DB::raw('SUM(local_sub_total) as total'))
            ->groupBy('doc_key')
            ->get()
            ->pluck('total', 'doc_key')
            ->toArray();

        // Cache all results
        foreach ($results as $docKey => $amount) {
            $this->invoiceAmountCache[$docKey] = (float) $amount;
        }

        // Fill in missing doc_keys with 0
        foreach ($docKeys as $docKey) {
            if (!isset($this->invoiceAmountCache[$docKey])) {
                $this->invoiceAmountCache[$docKey] = 0.0;
            }
        }

        return $this->invoiceAmountCache;
    }

    // Batch load payment statuses for multiple invoice numbers
    protected function batchLoadPaymentStatuses(array $invoiceNos): void
    {
        if (empty($invoiceNos)) {
            return;
        }

        // Get invoices and their doc_keys
        $invoices = Invoice::whereIn('invoice_no', $invoiceNos)
            ->get()
            ->keyBy('invoice_no');

        // Get debtor aging data - CAST to handle collation
        $debtorAgings = DB::table('debtor_agings')
            ->whereIn(DB::raw('CAST(invoice_number AS CHAR)'), array_map('strval', $invoiceNos))
            ->get()
            ->keyBy('invoice_number');

        // Batch load invoice amounts
        $docKeys = $invoices->pluck('doc_key')->toArray();
        $this->batchLoadInvoiceAmounts($docKeys);

        foreach ($invoiceNos as $invoiceNo) {
            $invoice = $invoices->get($invoiceNo);

            if (!$invoice) {
                $this->paymentStatusCache[$invoiceNo] = 'Charge Out';
                continue;
            }

            $totalInvoiceAmount = $this->getTotalInvoiceAmount($invoice->doc_key);

            if ($totalInvoiceAmount <= 0) {
                $this->paymentStatusCache[$invoiceNo] = 'Charge Out';
                continue;
            }

            $debtorAging = $debtorAgings->get($invoiceNo);

            $status = 'Full Payment'; // Default

            if (!$debtorAging || (float)$debtorAging->outstanding === 0.0) {
                $status = 'Full Payment';
            } elseif ((float)$debtorAging->outstanding === (float)$totalInvoiceAmount) {
                $status = 'UnPaid';
            } elseif ((float)$debtorAging->outstanding < (float)$totalInvoiceAmount && (float)$debtorAging->outstanding > 0) {
                $status = 'Partial Payment';
            } else {
                $status = 'UnPaid';
            }

            $this->paymentStatusCache[$invoiceNo] = $status;
        }
    }

    // Helper method to get payment status for an invoice (with caching)
    protected function getPaymentStatusForInvoice(string $invoiceNo): string
    {
        // Check cache first
        if (isset($this->paymentStatusCache[$invoiceNo])) {
            return $this->paymentStatusCache[$invoiceNo];
        }

        // Get the invoice record
        $invoice = Invoice::where('invoice_no', $invoiceNo)->first();

        if (!$invoice) {
            $this->paymentStatusCache[$invoiceNo] = 'Charge Out';
            return 'Charge Out';
        }

        // Get total invoice amount from invoice_details
        $totalInvoiceAmount = $this->getTotalInvoiceAmount($invoice->doc_key);

        if ($totalInvoiceAmount <= 0) {
            $this->paymentStatusCache[$invoiceNo] = 'Charge Out';
            return 'Charge Out';
        }

        // Look for this invoice in debtor_agings table - CAST to handle collation
        $debtorAging = DB::table('debtor_agings')
            ->where(DB::raw('CAST(invoice_number AS CHAR)'), '=', $invoiceNo)
            ->first();

        $status = 'Full Payment'; // Default

        if (!$debtorAging || (float)$debtorAging->outstanding === 0.0) {
            $status = 'Full Payment';
        } elseif ((float)$debtorAging->outstanding === (float)$totalInvoiceAmount) {
            $status = 'UnPaid';
        } elseif ((float)$debtorAging->outstanding < (float)$totalInvoiceAmount && (float)$debtorAging->outstanding > 0) {
            $status = 'Partial Payment';
        } else {
            $status = 'UnPaid';
        }

        $this->paymentStatusCache[$invoiceNo] = $status;

        return $status;
    }

    public function loadSummaryData(): void
    {
        $cacheKey = 'invoices_table_summary_' . Auth::id() . '_' . date('Y-m-d');

        $this->summaryData = Cache::remember($cacheKey, 300, function () {
            $today = Carbon::today();
            $currentYear = $today->year;
            $currentMonth = $today->month;

            $allYearStart = Carbon::create($currentYear - 1, 1, 1);
            $allYearEnd = $today;

            $currentYearStart = Carbon::create($currentYear, 1, 1);
            $currentYearEnd = $today;

            $currentMonthStart = Carbon::create($currentYear, $currentMonth, 1);
            $currentMonthEnd = $today;

            $allowedSalespersons = $this->getAllowedSalespersons();

            return [
                'all_year' => $this->calculateSummaryStats($allYearStart, $allYearEnd, $allowedSalespersons),
                'current_year' => $this->calculateSummaryStats($currentYearStart, $currentYearEnd, $allowedSalespersons),
                'current_month' => $this->calculateSummaryStats($currentMonthStart, $currentMonthEnd, $allowedSalespersons),
                'hrdf_all_year' => $this->calculateSummaryStats($allYearStart, $allYearEnd, $allowedSalespersons, 'EHIN'),
                'product_all_year' => $this->calculateSummaryStats($allYearStart, $allYearEnd, $allowedSalespersons, 'EPIN'),
            ];
        });
    }

    protected function getAllowedSalespersons(): array
    {
        $allowedSalespersons = array_keys(static::$salespersonUserIds);

        if (Auth::check() && Auth::user()->role_id === 2) {
            $userId = Auth::id();
            $salespersonName = array_search($userId, static::$salespersonUserIds);

            if ($salespersonName) {
                return [$salespersonName];
            } else {
                return [];
            }
        }

        return $allowedSalespersons;
    }

    protected function batchLoadCreditNotes(array $invoiceNos): void
    {
        if (empty($invoiceNos)) {
            return;
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

        $results = DB::table('credit_notes as cn')
            ->join('credit_note_details as cnd', 'cn.id', '=', 'cnd.credit_note_id')
            ->whereIn('cn.invoice_number', $invoiceNos)
            ->whereNotIn('cnd.item_code', $excludedItemCodes)
            ->select('cn.invoice_number', DB::raw('SUM(cnd.local_sub_total) as total_credit'))
            ->groupBy('cn.invoice_number')
            ->get();

        foreach ($results as $row) {
            $this->creditNoteCache[$row->invoice_number] = (float) $row->total_credit;
        }

        // Fill in missing invoice numbers with 0
        foreach ($invoiceNos as $invoiceNo) {
            if (!isset($this->creditNoteCache[$invoiceNo])) {
                $this->creditNoteCache[$invoiceNo] = 0.0;
            }
        }
    }

    // Helper method to get credit note amount for an invoice
    protected function getCreditNoteAmountWithExclusions(int $creditNoteId): float
    {
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

        return DB::table('credit_note_details')
            ->where('credit_note_id', $creditNoteId)
            ->whereNotIn('item_code', $excludedItemCodes)
            ->sum('local_sub_total');
    }

    protected function getCreditNoteAmount(string $invoiceNo): float
    {
        if (isset($this->creditNoteCache[$invoiceNo])) {
            return $this->creditNoteCache[$invoiceNo];
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

        $amount = DB::table('credit_notes as cn')
            ->join('credit_note_details as cnd', 'cn.id', '=', 'cnd.credit_note_id')
            ->where('cn.invoice_number', $invoiceNo)
            ->whereNotIn('cnd.item_code', $excludedItemCodes)
            ->sum('cnd.local_sub_total');

        $this->creditNoteCache[$invoiceNo] = (float) $amount;

        return (float) $amount;
    }

    protected function calculateSummaryStats(Carbon $startDate, Carbon $endDate, array $allowedSalespersons, string $invoicePrefix = null): array
    {
        if (empty($allowedSalespersons)) {
            return [
                'full_payment_amount' => 0,
                'partial_payment_amount' => 0,
                'unpaid_amount' => 0,
                'total_amount' => 0,
            ];
        }

        $excludedItemCodes = [
            'SHIPPING', 'BANKCHG',
            'DEPOSIT-MYR', 'F.COMMISSION', 'L.COMMISSION',
            'L.ENTITLEMENT', 'MGT FEES', 'PG.COMMISSION'
        ];

        $placeholders = implode(',', array_fill(0, count($excludedItemCodes), '?'));
        $salespersonPlaceholders = implode(',', array_fill(0, count($allowedSalespersons), '?'));

        $params = array_merge($excludedItemCodes, $allowedSalespersons, [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        $invoiceTypeCondition = '';
        if ($invoicePrefix) {
            $invoiceTypeCondition = " AND i.invoice_no LIKE ?";
            $params[] = $invoicePrefix . '%';
        }

        // Get invoice amounts - exclude voided invoices
        $results = DB::select("
            SELECT
                i.invoice_no,
                i.doc_key,
                i.salesperson,
                COALESCE(SUM(id.local_sub_total), 0) as total_amount,
                COALESCE(da.outstanding, 0) as outstanding,
                COALESCE(da.invoice_amount, 0) as debtor_invoice_amount
            FROM invoices i
            LEFT JOIN invoice_details id ON i.doc_key = id.doc_key
                AND id.item_code NOT IN ($placeholders)
            LEFT JOIN debtor_agings da ON CAST(i.invoice_no AS CHAR) = CAST(da.invoice_number AS CHAR)
            WHERE i.salesperson IN ($salespersonPlaceholders)
                AND i.invoice_date BETWEEN ? AND ?
                AND i.invoice_status != 'V'
                $invoiceTypeCondition
            GROUP BY i.invoice_no, i.doc_key, i.salesperson, da.outstanding, da.invoice_amount
            HAVING total_amount > 0
        ", $params);

        // ✅ Get credit note amounts based on credit_note_date (WITH EXCLUSIONS)
        $creditNoteParams = array_merge($excludedItemCodes, $allowedSalespersons, [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        $creditNoteTypeCondition = '';
        if ($invoicePrefix) {
            $creditNotePrefix = str_replace('EPIN', 'EPCN', str_replace('EHIN', 'ECN', $invoicePrefix));
            $creditNoteTypeCondition = " AND cn.credit_note_number LIKE ?";
            $creditNoteParams[] = $creditNotePrefix . '%';
        }

        $creditNotes = DB::select("
            SELECT
                cn.salesperson,
                SUM(cnd.local_sub_total) as total_credit
            FROM credit_notes cn
            JOIN credit_note_details cnd ON cn.id = cnd.credit_note_id
            WHERE cnd.item_code NOT IN ($placeholders)
                AND cn.salesperson IN ($salespersonPlaceholders)
                AND cn.credit_note_date BETWEEN ? AND ?
                $creditNoteTypeCondition
            GROUP BY cn.salesperson
        ", $creditNoteParams);

        // Create a map of credit notes by salesperson
        $creditNoteMap = [];
        foreach ($creditNotes as $cn) {
            $creditNoteMap[$cn->salesperson] = (float) $cn->total_credit;
        }

        $stats = [
            'full_payment_amount' => 0,
            'partial_payment_amount' => 0,
            'unpaid_amount' => 0,
            'total_amount' => 0,
        ];

        // Group results by salesperson
        $salespersonTotals = [];
        foreach ($results as $row) {
            $salesperson = $row->salesperson;
            if (!isset($salespersonTotals[$salesperson])) {
                $salespersonTotals[$salesperson] = [
                    'invoices' => [],
                ];
            }
            $salespersonTotals[$salesperson]['invoices'][] = $row;
        }

        // Process each salesperson's invoices and subtract their credit notes
        foreach ($salespersonTotals as $salesperson => $data) {
            $creditAmount = $creditNoteMap[$salesperson] ?? 0;

            foreach ($data['invoices'] as $row) {
                $amount = (float) $row->total_amount;
                $outstanding = (float) $row->outstanding;

                $stats['total_amount'] += $amount;

                if ($outstanding == 0) {
                    $stats['full_payment_amount'] += $amount;
                } elseif ($outstanding < $amount) {
                    $stats['partial_payment_amount'] += $amount;
                } else {
                    $stats['unpaid_amount'] += $amount;
                }
            }

            // Subtract credit notes from total amount only
            $stats['total_amount'] -= $creditAmount;
        }

        return $stats;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query())
            ->defaultPaginationPageOption(50)
            ->heading('Invoices')
            ->deferLoading()
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
                        // Use getTotalInvoiceAmount from invoice_details
                        return $this->getTotalInvoiceAmount($record->doc_key);
                    })
                    ->summarize([
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Grand Total')
                            ->using(function ($query) {
                                // Get the grouped results
                                $groupedResults = $query->get();

                                $docKeys = $groupedResults->pluck('doc_key')->toArray();
                                $this->batchLoadInvoiceAmounts($docKeys);

                                // Calculate invoice total from invoice_details using getTotalInvoiceAmount
                                $grandTotal = 0;
                                foreach ($groupedResults as $record) {
                                    $grandTotal += $this->getTotalInvoiceAmount($record->doc_key);
                                }

                                // Now deduct credit notes that were issued in the same date range
                                $allowedSalespersons = array_keys(static::$salespersonUserIds);

                                // Get the active table filters
                                $tableFilters = $this->tableFilters ?? [];

                                // Extract filter values safely
                                $year = null;
                                $month = null;
                                $invoiceType = null;
                                $salespersonFilter = null;

                                if (isset($tableFilters['year']['value'])) {
                                    $year = $tableFilters['year']['value'];
                                } elseif (isset($tableFilters['year']) && !is_array($tableFilters['year'])) {
                                    $year = $tableFilters['year'];
                                }

                                if (isset($tableFilters['month']['value'])) {
                                    $month = $tableFilters['month']['value'];
                                } elseif (isset($tableFilters['month']) && !is_array($tableFilters['month'])) {
                                    $month = $tableFilters['month'];
                                }

                                if (isset($tableFilters['invoice_type']['value'])) {
                                    $invoiceType = $tableFilters['invoice_type']['value'];
                                } elseif (isset($tableFilters['invoice_type']) && !is_array($tableFilters['invoice_type'])) {
                                    $invoiceType = $tableFilters['invoice_type'];
                                }

                                if (isset($tableFilters['salesperson']['value'])) {
                                    $salespersonFilter = $tableFilters['salesperson']['value'];
                                } elseif (isset($tableFilters['salesperson']) && !is_array($tableFilters['salesperson'])) {
                                    $salespersonFilter = $tableFilters['salesperson'];
                                }

                                $excludedItemCodes = [
                                    'SHIPPING', 'BANKCHG',
                                    'DEPOSIT-MYR', 'F.COMMISSION', 'L.COMMISSION',
                                    'L.ENTITLEMENT', 'MGT FEES', 'PG.COMMISSION'
                                ];

                                $creditNoteQuery = DB::table('credit_notes as cn')
                                    ->join('credit_note_details as cnd', 'cn.id', '=', 'cnd.credit_note_id')
                                    ->whereIn('cn.salesperson', $allowedSalespersons)
                                    ->whereNotIn('cnd.item_code', $excludedItemCodes);

                                // Apply year filter if set
                                if ($year) {
                                    $creditNoteQuery->whereYear('credit_note_date', $year);
                                }

                                // Apply month filter if set
                                if ($month) {
                                    $creditNoteQuery->whereMonth('credit_note_date', $month);
                                }

                                // Apply invoice type filter if set
                                if ($invoiceType) {
                                    $creditNotePrefix = str_replace('EPIN', 'EPCN', str_replace('EHIN', 'ECN', $invoiceType));
                                    $creditNoteQuery->where('credit_note_number', 'like', $creditNotePrefix . '%');
                                }

                                // Apply salesperson filter if set
                                if ($salespersonFilter) {
                                    $creditNoteQuery->where('salesperson', $salespersonFilter);
                                }

                                // For role_id 2, filter by their own salesperson name
                                if (Auth::check() && Auth::user()->role_id === 2) {
                                    $userId = Auth::id();
                                    $salespersonName = array_search($userId, static::$salespersonUserIds);
                                    if ($salespersonName) {
                                        $creditNoteQuery->where('salesperson', $salespersonName);
                                    }
                                }

                                $totalCreditNotes = $creditNoteQuery->sum('cnd.local_sub_total');

                                // Deduct credit notes from grand total
                                $grandTotal -= $totalCreditNotes;

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
                $allowedSalespersons = array_keys(static::$salespersonUserIds);

                // Exclude voided invoices
                $query->whereIn('salesperson', $allowedSalespersons)
                    ->where('invoice_status', '!=', 'V')
                    ->orderBy('invoice_date', 'desc');

                if (Auth::check() && Auth::user()->role_id === 2) {
                    $userId = Auth::id();
                    $salespersonName = array_search($userId, static::$salespersonUserIds);

                    if ($salespersonName) {
                        $query->where('salesperson', $salespersonName);
                    } else {
                        $query->where('id', 0);
                    }
                }

                // Batch load data for current page
                $results = $query->limit(1000)->get();
                if ($results->isNotEmpty()) {
                    $docKeys = $results->pluck('doc_key')->toArray();
                    $invoiceNos = $results->pluck('invoice_no')->toArray();

                    $this->batchLoadInvoiceAmounts($docKeys);
                    $this->batchLoadPaymentStatuses($invoiceNos);
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

                        $allInvoices = Invoice::whereIn('salesperson', $allowedSalespersons)->get();

                        $matchingInvoiceNos = [];

                        // Batch load for efficiency
                        $docKeys = $allInvoices->pluck('doc_key')->toArray();
                        $invoiceNos = $allInvoices->pluck('invoice_no')->toArray();

                        $this->batchLoadInvoiceAmounts($docKeys);
                        $this->batchLoadPaymentStatuses($invoiceNos);

                        foreach ($allInvoices as $invoice) {
                            $status = $this->getPaymentStatusForInvoice($invoice->invoice_no);
                            if ($status === $targetStatus) {
                                $matchingInvoiceNos[] = $invoice->invoice_no;
                            }
                        }

                        return $query->whereIn('invoice_no', $matchingInvoiceNos);
                    }),

                SelectFilter::make('invoice_type')
                    ->label('Invoice Type')
                    ->options([
                        'EPIN' => 'Product Invoice (EPIN)',
                        'EHIN' => 'HRDF Invoice (EHIN)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $prefix): Builder => $query->where('invoice_no', 'like', $prefix . '%')
                        );
                    }),

                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        $allowedSalespersons = array_keys(static::$salespersonUserIds);

                        return Invoice::select('salesperson')
                            ->distinct()
                            ->whereNotNull('salesperson')
                            ->where('salesperson', '!=', '')
                            ->whereIn('salesperson', $allowedSalespersons)
                            ->orderBy('salesperson')
                            ->pluck('salesperson', 'salesperson')
                            ->toArray();
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
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $year): Builder => $query->whereYear('invoice_date', $year)
                        );
                    }),

                SelectFilter::make('month')
                    ->label('Month')
                    ->options([
                        '1' => 'January', '2' => 'February', '3' => 'March',
                        '4' => 'April', '5' => 'May', '6' => 'June',
                        '7' => 'July', '8' => 'August', '9' => 'September',
                        '10' => 'October', '11' => 'November', '12' => 'December',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $month): Builder => $query->whereMonth('invoice_date', $month)
                        );
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
