<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SalesAdminInvoice extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Sales Admin Invoice';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 31;

    protected static string $view = 'filament.pages.sales-admin-invoice';
    protected static ?string $slug = 'sales-admin-invoices';

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

    public $salespersonData = [];

    // Cache for invoice amounts
    protected $invoiceAmountCache = [];

    // Cache for payment statuses
    protected $paymentStatusCache = [];

    public function mount(): void
    {
        $this->loadSalespersonData();
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

    protected function getCurrentFilters(): array
    {
        $tableFilters = $this->tableFilters ?? [];

        $filters = [];

        // Extract filter values
        $filterKeys = ['year', 'month', 'invoice_type', 'salesperson', 'sales_admin', 'payment_status'];

        foreach ($filterKeys as $key) {
            if (isset($tableFilters[$key]['value'])) {
                $filters[$key] = $tableFilters[$key]['value'];
            } elseif (isset($tableFilters[$key]) && !is_array($tableFilters[$key])) {
                $filters[$key] = $tableFilters[$key];
            }
        }

        return $filters;
    }

    public function updatedTableFilters(): void
    {
        $filters = $this->getCurrentFilters();
        $this->loadSalespersonData($filters);
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

        // If total amount is 0 or less, don't process
        if ($totalInvoiceAmount <= 0) {
            $this->paymentStatusCache[$invoiceNo] = 'Charge Out';
            return 'Charge Out';
        }

        // Look for this invoice in debtor_agings table
        $debtorAging = DB::table('debtor_agings')
            ->where('invoice_number', $invoiceNo)
            ->first();

        $status = 'Full Payment'; // Default

        // If no matching record in debtor_agings or outstanding is 0
        if (!$debtorAging || (float)$debtorAging->outstanding === 0.0) {
            $status = 'Full Payment';
        }
        // If outstanding equals total invoice amount
        elseif ((float)$debtorAging->outstanding === (float)$totalInvoiceAmount) {
            $status = 'UnPaid';
        }
        // If outstanding is less than invoice amount but greater than 0
        elseif ((float)$debtorAging->outstanding < (float)$totalInvoiceAmount && (float)$debtorAging->outstanding > 0) {
            $status = 'Partial Payment';
        }
        else {
            $status = 'UnPaid';
        }

        // Store in cache
        $this->paymentStatusCache[$invoiceNo] = $status;

        return $status;
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

        // Get debtor aging data
        $debtorAgings = DB::table('debtor_agings')
            ->whereIn('invoice_number', $invoiceNos)
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

    public function loadSalespersonData(?array $filters = null): void
    {
        $cacheKey = 'sales_admin_invoice_data_' . md5(json_encode($filters)) . '_' . date('Y-m-d');

        $this->salespersonData = Cache::remember($cacheKey, 300, function () use ($filters) {
            $today = Carbon::today();
            $currentYear = $today->year;
            $allYearStart = Carbon::create($currentYear - 1, 1, 1);
            $allYearEnd = $today;

            // Extract filter values if provided
            $yearFilter = $filters['year'] ?? null;
            $monthFilter = $filters['month'] ?? null;
            $invoiceTypeFilter = $filters['invoice_type'] ?? null;
            $salespersonFilter = $filters['salesperson'] ?? null;
            $salesAdminFilter = $filters['sales_admin'] ?? null;
            $paymentStatusFilter = $filters['payment_status'] ?? null;

            $data = [];

            foreach (static::$salespersonUserIds as $salespersonName => $userId) {
                // Build query with filters
                $invoiceQuery = Invoice::query()
                    ->where('salesperson', $salespersonName);

                // Apply year filter
                if ($yearFilter) {
                    $invoiceQuery->whereYear('invoice_date', $yearFilter);
                } else {
                    $invoiceQuery->whereBetween('invoice_date', [$allYearStart, $allYearEnd]);
                }

                // Apply month filter
                if ($monthFilter) {
                    $invoiceQuery->whereMonth('invoice_date', $monthFilter);
                }

                // Apply invoice type filter
                if ($invoiceTypeFilter) {
                    $invoiceQuery->where('invoice_no', 'like', $invoiceTypeFilter . '%');
                }

                // Apply salesperson filter (for admin view)
                if ($salespersonFilter && $salespersonFilter !== $salespersonName) {
                    $data[$salespersonName] = [
                        'jaja_amount' => 0,
                        'sheena_amount' => 0,
                    ];
                    continue;
                }

                // Apply sales admin filter
                if ($salesAdminFilter !== null) {
                    if ($salesAdminFilter === '') {
                        $invoiceQuery->where(function ($q) {
                            $q->whereNull('sales_admin')
                            ->orWhere('sales_admin', '');
                        });
                    } else {
                        $invoiceQuery->where('sales_admin', $salesAdminFilter);
                    }
                }

                $invoices = $invoiceQuery->get();

                // Batch load data
                if ($invoices->isNotEmpty()) {
                    $docKeys = $invoices->pluck('doc_key')->toArray();
                    $invoiceNos = $invoices->pluck('invoice_no')->toArray();

                    $this->batchLoadInvoiceAmounts($docKeys);
                    $this->batchLoadPaymentStatuses($invoiceNos);
                }

                $jajaAmount = 0;
                $sheenaAmount = 0;

                foreach ($invoices as $invoice) {
                    $totalInvoiceAmount = $this->getTotalInvoiceAmount($invoice->doc_key);

                    if ($totalInvoiceAmount <= 0) {
                        continue;
                    }

                    $paymentStatus = $this->getPaymentStatusForInvoice($invoice->invoice_no);

                    // Apply payment status filter
                    if ($paymentStatusFilter && $paymentStatus !== $paymentStatusFilter) {
                        continue;
                    }

                    $salesAdmin = $this->getSalesAdminFromInvoice($invoice);

                    if ($salesAdmin === 'JAJA') {
                        if ($paymentStatus === 'Full Payment') {
                            $jajaAmount += $totalInvoiceAmount;
                        }
                    } elseif ($salesAdmin === 'SHEENA') {
                        if ($paymentStatus === 'Full Payment') {
                            $sheenaAmount += $totalInvoiceAmount;
                        }
                    }
                }

                // Handle credit notes with filters
                $creditNoteQuery = DB::table('credit_notes')
                    ->where('salesperson', $salespersonName);

                // Apply date filters to credit notes
                if ($yearFilter && $monthFilter) {
                    $creditNoteQuery->whereYear('credit_note_date', $yearFilter)
                                ->whereMonth('credit_note_date', $monthFilter);
                } elseif ($yearFilter) {
                    $creditNoteQuery->whereYear('credit_note_date', $yearFilter);
                } elseif ($monthFilter) {
                    $creditNoteQuery->whereMonth('credit_note_date', $monthFilter);
                } else {
                    $creditNoteQuery->whereBetween('credit_note_date', [$allYearStart, $allYearEnd]);
                }

                // Apply invoice type filter to credit notes
                if ($invoiceTypeFilter) {
                    $creditNoteQuery->where('invoice_number', 'like', $invoiceTypeFilter . '%');
                }

                $creditNotesForSalesperson = $creditNoteQuery->get();

                foreach ($creditNotesForSalesperson as $creditNote) {
                    $invoice = Invoice::where('invoice_no', $creditNote->invoice_number)->first();

                    if (!$invoice) continue;

                    // Apply sales admin filter to credit notes
                    if ($salesAdminFilter !== null) {
                        if ($salesAdminFilter === '') {
                            if (!empty($invoice->sales_admin)) continue;
                        } else {
                            if ($invoice->sales_admin !== $salesAdminFilter) continue;
                        }
                    }

                    $salesAdmin = $this->getSalesAdminFromInvoice($invoice);
                    $creditAmount = (float)$creditNote->amount;

                    $paymentStatus = $this->getPaymentStatusForInvoice($creditNote->invoice_number);

                    // Apply payment status filter to credit notes
                    if ($paymentStatusFilter && $paymentStatus !== $paymentStatusFilter) {
                        continue;
                    }

                    if ($paymentStatus === 'Full Payment') {
                        if ($salesAdmin === 'JAJA') {
                            $jajaAmount -= $creditAmount;
                        } elseif ($salesAdmin === 'SHEENA') {
                            $sheenaAmount -= $creditAmount;
                        }
                    }
                }

                $data[$salespersonName] = [
                    'jaja_amount' => $jajaAmount,
                    'sheena_amount' => $sheenaAmount,
                ];
            }

            return $data;
        });
    }

    protected function getSalesAdminFromInvoice($invoice): string
    {
        return $invoice->sales_admin ?: 'Unassigned';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query())
            ->defaultPaginationPageOption(50)
            ->heading('Invoices')
            ->deferLoading() // Defer loading for better performance
            ->columns([
                Tables\Columns\TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sales_admin')
                    ->label('Sales Admin')
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->dateTime('d F Y')
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
                        return $this->getTotalInvoiceAmount($record->doc_key);
                    })
                    ->summarize([
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Grand Total')
                            ->using(function ($query) {
                                $groupedResults = $query->get();

                                // Batch load all invoice amounts
                                $docKeys = $groupedResults->pluck('doc_key')->toArray();
                                $this->batchLoadInvoiceAmounts($docKeys);

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
                                $salesAdminFilter = null;

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

                                if (isset($tableFilters['sales_admin']['value'])) {
                                    $salesAdminFilter = $tableFilters['sales_admin']['value'];
                                } elseif (isset($tableFilters['sales_admin']) && !is_array($tableFilters['sales_admin'])) {
                                    $salesAdminFilter = $tableFilters['sales_admin'];
                                }

                                // Build the credit note query based on credit_note_date
                                $creditNoteQuery = DB::table('credit_notes')
                                    ->whereIn('salesperson', $allowedSalespersons);

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
                                    $creditNoteQuery->where('invoice_number', 'like', $invoiceType . '%');
                                }

                                // Apply salesperson filter if set
                                if ($salespersonFilter) {
                                    $creditNoteQuery->where('salesperson', $salespersonFilter);
                                }

                                // Apply sales_admin filter if set
                                if ($salesAdminFilter !== null) {
                                    $invoiceNosWithSalesAdmin = Invoice::query()
                                        ->whereIn('salesperson', $allowedSalespersons);

                                    if ($salesAdminFilter === '') {
                                        $invoiceNosWithSalesAdmin->where(function ($q) {
                                            $q->whereNull('sales_admin')
                                              ->orWhere('sales_admin', '');
                                        });
                                    } else {
                                        $invoiceNosWithSalesAdmin->where('sales_admin', $salesAdminFilter);
                                    }

                                    $matchingInvoiceNos = $invoiceNosWithSalesAdmin
                                        ->pluck('invoice_no')
                                        ->toArray();

                                    $creditNoteQuery->whereIn('invoice_number', $matchingInvoiceNos);
                                }

                                // For role_id 2, filter by their own salesperson name
                                if (Auth::check() && Auth::user()->role_id === 2) {
                                    $userId = Auth::id();
                                    $salespersonName = array_search($userId, static::$salespersonUserIds);
                                    if ($salespersonName) {
                                        $creditNoteQuery->where('salesperson', $salespersonName);
                                    }
                                }

                                $totalCreditNotes = $creditNoteQuery->sum('amount');

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

                $query->whereIn('salesperson', $allowedSalespersons)
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
                $results = $query->get();
                if ($results->isNotEmpty()) {
                    $docKeys = $results->pluck('doc_key')->toArray();
                    $invoiceNos = $results->pluck('invoice_no')->toArray();

                    $this->batchLoadInvoiceAmounts($docKeys);
                    $this->batchLoadPaymentStatuses($invoiceNos);
                }

                return $query;
            })
            ->filters([
                SelectFilter::make('sales_admin')
                    ->label('Sales Admin')
                    ->options(function () {
                        $allowedSalespersons = array_keys(static::$salespersonUserIds);

                        try {
                            return Invoice::query()
                                ->select('sales_admin')
                                ->distinct()
                                ->whereIn('salesperson', $allowedSalespersons)
                                ->orderBy('sales_admin')
                                ->get()
                                ->mapWithKeys(function ($item) {
                                    $value = $item->sales_admin ?: 'Unassigned';
                                    $key = $item->sales_admin ?: '';
                                    return [$key => $value];
                                })
                                ->toArray();
                        } catch (\Exception $e) {
                            return [];
                        }
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value']) && $data['value'] !== '') {
                            return $query;
                        }

                        if ($data['value'] === '') {
                            return $query->where(function ($q) {
                                $q->whereNull('sales_admin')
                                  ->orWhere('sales_admin', '');
                            });
                        }

                        return $query->where('sales_admin', $data['value']);
                    }),

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

                        // OPTIMIZED: Use raw SQL
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

                        $placeholders = implode(',', array_fill(0, count($excludedItemCodes), '?'));
                        $salespersonPlaceholders = implode(',', array_fill(0, count($allowedSalespersons), '?'));

                        $statusCondition = match($targetStatus) {
                            'Full Payment' => 'COALESCE(da.outstanding, 0) = 0',
                            'Partial Payment' => 'da.outstanding > 0 AND da.outstanding < invoice_total',
                            'UnPaid' => 'da.outstanding >= invoice_total AND da.outstanding > 0',
                            default => '1=1'
                        };

                        $matchingInvoiceNos = DB::select("
                            SELECT DISTINCT i.invoice_no,
                                COALESCE(SUM(id.local_sub_total), 0) as invoice_total
                            FROM invoices i
                            LEFT JOIN invoice_details id ON i.doc_key = id.doc_key
                                AND id.item_code NOT IN ($placeholders)
                            LEFT JOIN debtor_agings da ON i.invoice_no = da.invoice_number
                            WHERE i.salesperson IN ($salespersonPlaceholders)
                            GROUP BY i.invoice_no, da.outstanding, da.invoice_amount
                            HAVING invoice_total > 0 AND ($statusCondition)
                        ", array_merge($excludedItemCodes, $allowedSalespersons));

                        $invoiceNumbers = array_column($matchingInvoiceNos, 'invoice_no');

                        return $query->whereIn('invoice_no', $invoiceNumbers);
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
            ->actions([])
            ->bulkActions([]);
    }
}
