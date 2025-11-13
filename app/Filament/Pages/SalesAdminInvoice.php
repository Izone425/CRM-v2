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
    protected $creditNoteCache = [];

    // Cache for payment statuses
    protected $paymentStatusCache = [];

    public function mount(): void
    {
        $this->loadSalespersonData();
    }

    // Helper method to get total invoice amount from invoice_details (with caching)
    protected function getTotalInvoiceAmount(string $docKey): float
    {
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

        foreach ($results as $docKey => $amount) {
            $this->invoiceAmountCache[$docKey] = (float) $amount;
        }

        foreach ($docKeys as $docKey) {
            if (!isset($this->invoiceAmountCache[$docKey])) {
                $this->invoiceAmountCache[$docKey] = 0.0;
            }
        }

        return $this->invoiceAmountCache;
    }

    // ✅ Batch load credit notes with exclusions
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

        foreach ($invoiceNos as $invoiceNo) {
            if (!isset($this->creditNoteCache[$invoiceNo])) {
                $this->creditNoteCache[$invoiceNo] = 0.0;
            }
        }
    }

    // ✅ Get credit note amount with exclusions
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

    protected function getCurrentFilters(): array
    {
        $tableFilters = $this->tableFilters ?? [];

        $filters = [];

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

    protected function getPaymentStatusForInvoice(string $invoiceNo): string
    {
        if (isset($this->paymentStatusCache[$invoiceNo])) {
            return $this->paymentStatusCache[$invoiceNo];
        }

        $invoice = Invoice::where('invoice_no', $invoiceNo)->first();

        if (!$invoice) {
            $this->paymentStatusCache[$invoiceNo] = 'Charge Out';
            return 'Charge Out';
        }

        $totalInvoiceAmount = $this->getTotalInvoiceAmount($invoice->doc_key);

        if ($totalInvoiceAmount <= 0) {
            $this->paymentStatusCache[$invoiceNo] = 'Charge Out';
            return 'Charge Out';
        }

        $debtorAging = DB::table('debtor_agings')
            ->where('invoice_number', $invoiceNo)
            ->first();

        $status = 'Full Payment';

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

    protected function batchLoadPaymentStatuses(array $invoiceNos): void
    {
        if (empty($invoiceNos)) {
            return;
        }

        $invoices = Invoice::whereIn('invoice_no', $invoiceNos)
            ->get()
            ->keyBy('invoice_no');

        $debtorAgings = DB::table('debtor_agings')
            ->whereIn('invoice_number', $invoiceNos)
            ->get()
            ->keyBy('invoice_number');

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

            $status = 'Full Payment';

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

            $yearFilter = $filters['year'] ?? null;
            $monthFilter = $filters['month'] ?? null;
            $invoiceTypeFilter = $filters['invoice_type'] ?? null;
            $salespersonFilter = $filters['salesperson'] ?? null;
            $salesAdminFilter = $filters['sales_admin'] ?? null;
            $paymentStatusFilter = $filters['payment_status'] ?? null;

            $data = [];

            foreach (static::$salespersonUserIds as $salespersonName => $userId) {
                $invoiceQuery = Invoice::query()
                    ->where('salesperson', $salespersonName)
                    ->where('invoice_status', '!=', 'V');

                if ($yearFilter) {
                    $invoiceQuery->whereYear('invoice_date', $yearFilter);
                } else {
                    $invoiceQuery->whereBetween('invoice_date', [$allYearStart, $allYearEnd]);
                }

                if ($monthFilter) {
                    $invoiceQuery->whereMonth('invoice_date', $monthFilter);
                }

                if ($invoiceTypeFilter) {
                    $invoiceQuery->where('invoice_no', 'like', $invoiceTypeFilter . '%');
                }

                if ($salespersonFilter && $salespersonFilter !== $salespersonName) {
                    $data[$salespersonName] = [
                        'jaja_amount' => 0,
                        'sheena_amount' => 0,
                    ];
                    continue;
                }

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

                // ✅ Handle credit notes with exclusions using credit_note_details
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

                $creditNoteQuery = DB::table('credit_notes as cn')
                    ->join('credit_note_details as cnd', 'cn.id', '=', 'cnd.credit_note_id')
                    ->where('cn.salesperson', $salespersonName)
                    ->whereNotIn('cnd.item_code', $excludedItemCodes);

                if ($yearFilter && $monthFilter) {
                    $creditNoteQuery->whereYear('cn.credit_note_date', $yearFilter)
                                ->whereMonth('cn.credit_note_date', $monthFilter);
                } elseif ($yearFilter) {
                    $creditNoteQuery->whereYear('cn.credit_note_date', $yearFilter);
                } elseif ($monthFilter) {
                    $creditNoteQuery->whereMonth('cn.credit_note_date', $monthFilter);
                } else {
                    $creditNoteQuery->whereBetween('cn.credit_note_date', [$allYearStart, $allYearEnd]);
                }

                if ($invoiceTypeFilter) {
                    $creditNoteQuery->where('cn.invoice_number', 'like', $invoiceTypeFilter . '%');
                }

                $creditNotesForSalesperson = $creditNoteQuery
                    ->select('cn.invoice_number', 'cn.id', DB::raw('SUM(cnd.local_sub_total) as total_credit'))
                    ->groupBy('cn.invoice_number', 'cn.id')
                    ->get();

                foreach ($creditNotesForSalesperson as $creditNote) {
                    $invoice = Invoice::where('invoice_no', $creditNote->invoice_number)
                        ->where('invoice_status', '!=', 'V')
                        ->first();

                    if (!$invoice) continue;

                    if ($salesAdminFilter !== null) {
                        if ($salesAdminFilter === '') {
                            if (!empty($invoice->sales_admin)) continue;
                        } else {
                            if ($invoice->sales_admin !== $salesAdminFilter) continue;
                        }
                    }

                    $salesAdmin = $this->getSalesAdminFromInvoice($invoice);
                    $creditAmount = (float)$creditNote->total_credit;

                    $paymentStatus = $this->getPaymentStatusForInvoice($creditNote->invoice_number);

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
            ->deferLoading()
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

                                $docKeys = $groupedResults->pluck('doc_key')->toArray();
                                $this->batchLoadInvoiceAmounts($docKeys);

                                $grandTotal = 0;
                                foreach ($groupedResults as $record) {
                                    $grandTotal += $this->getTotalInvoiceAmount($record->doc_key);
                                }

                                $allowedSalespersons = array_keys(static::$salespersonUserIds);

                                $tableFilters = $this->tableFilters ?? [];

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

                                // ✅ Build credit note query with exclusions
                                $excludedItemCodes = [
                                    'SHIPPING', 'BANKCHG',
                                    'DEPOSIT-MYR', 'F.COMMISSION', 'L.COMMISSION',
                                    'L.ENTITLEMENT', 'MGT FEES', 'PG.COMMISSION'
                                ];

                                $creditNoteQuery = DB::table('credit_notes as cn')
                                    ->join('credit_note_details as cnd', 'cn.id', '=', 'cnd.credit_note_id')
                                    ->whereIn('cn.salesperson', $allowedSalespersons)
                                    ->whereNotIn('cnd.item_code', $excludedItemCodes);

                                if ($year) {
                                    $creditNoteQuery->whereYear('cn.credit_note_date', $year);
                                }

                                if ($month) {
                                    $creditNoteQuery->whereMonth('cn.credit_note_date', $month);
                                }

                                if ($invoiceType) {
                                    $creditNotePrefix = str_replace('EPIN', 'EPCN', str_replace('EHIN', 'ECN', $invoiceType));
                                    $creditNoteQuery->where('cn.credit_note_number', 'like', $creditNotePrefix . '%');
                                }

                                if ($salespersonFilter) {
                                    $creditNoteQuery->where('cn.salesperson', $salespersonFilter);
                                }

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

                                    $creditNoteQuery->whereIn('cn.invoice_number', $matchingInvoiceNos);
                                }

                                if (Auth::check() && Auth::user()->role_id === 2) {
                                    $userId = Auth::id();
                                    $salespersonName = array_search($userId, static::$salespersonUserIds);
                                    if ($salespersonName) {
                                        $creditNoteQuery->where('cn.salesperson', $salespersonName);
                                    }
                                }

                                $totalCreditNotes = $creditNoteQuery->sum('cnd.local_sub_total');

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
