<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
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

    public function mount(): void
    {
        $this->loadSalespersonData();
    }

    // Helper method to get payment status for an invoice
    protected function getPaymentStatusForInvoice(string $invoiceNo): string
    {
        // Get the total invoice amount for this invoice number (only positive amounts)
        $totalInvoiceAmount = Invoice::where('invoice_no', $invoiceNo)
            ->where('invoice_amount', '>', 0) // Only consider positive amounts
            ->sum('invoice_amount');

        // If total amount is 0 or less, don't process
        if ($totalInvoiceAmount <= 0) {
            return 'N/A';
        }

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


    public function loadSalespersonData(): void
    {
        $today = Carbon::today();
        $currentYear = $today->year;
        $allYearStart = Carbon::create($currentYear - 1, 1, 1); // Previous year January 1st
        $allYearEnd = $today; // Today

        foreach (static::$salespersonUserIds as $salespersonName => $userId) {
            // Get invoices for this salesperson (only positive amounts)
            $invoiceNos = Invoice::query()
                ->where('salesperson', $salespersonName)
                ->where('invoice_amount', '>', 0) // Only positive amounts
                ->whereBetween('invoice_date', [$allYearStart, $allYearEnd])
                ->distinct('invoice_no')
                ->pluck('invoice_no');

            $jajaAmount = 0;
            $sheenaAmount = 0;

            foreach ($invoiceNos as $invoiceNo) {
                // Calculate total invoice amount for this invoice (only positive amounts)
                $totalInvoiceAmount = Invoice::where('invoice_no', $invoiceNo)
                    ->where('invoice_amount', '>', 0)
                    ->sum('invoice_amount');

                $paymentStatus = $this->getPaymentStatusForInvoice($invoiceNo);

                // Get the invoice to check sales admin (lead_owner)
                $invoice = Invoice::where('invoice_no', $invoiceNo)
                    ->where('invoice_amount', '>', 0)
                    ->first();

                if (!$invoice) continue;

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

            // Deduct credit notes issued in this date range for this salesperson
            $creditNotesForSalesperson = DB::table('credit_notes')
                ->where('salesperson', $salespersonName)
                ->whereBetween('credit_note_date', [$allYearStart, $allYearEnd])
                ->get();

            foreach ($creditNotesForSalesperson as $creditNote) {
                // Get the invoice to check sales admin
                $invoice = Invoice::where('invoice_no', $creditNote->invoice_number)
                    ->where('invoice_amount', '>', 0)
                    ->first();

                if (!$invoice) continue;

                $salesAdmin = $this->getSalesAdminFromInvoice($invoice);
                $creditAmount = (float)$creditNote->amount;

                // Only deduct from Full Payment status
                $paymentStatus = $this->getPaymentStatusForInvoice($creditNote->invoice_number);

                if ($paymentStatus === 'Full Payment') {
                    if ($salesAdmin === 'JAJA') {
                        $jajaAmount -= $creditAmount;
                    } elseif ($salesAdmin === 'SHEENA') {
                        $sheenaAmount -= $creditAmount;
                    }
                }
            }

            $this->salespersonData[$salespersonName] = [
                'jaja_amount' => $jajaAmount,
                'sheena_amount' => $sheenaAmount,
            ];
        }
    }

    protected function getSalesAdminFromInvoice($invoice): string
    {
        // Show the actual value from database, even if it's null/empty
        return $invoice->sales_admin ?: 'Unassigned';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query())
            ->defaultPaginationPageOption(50)
            ->heading('Invoices')
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
                        return Invoice::where('invoice_no', $record->invoice_no)
                            ->where('invoice_amount', '>', 0)
                            ->sum('invoice_amount');
                    })
                    ->summarize([
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Grand Total')
                            ->using(function ($query) {
                                $groupedResults = $query->get();
                                $grandTotal = 0;

                                foreach ($groupedResults as $record) {
                                    $grandTotal += Invoice::where('invoice_no', $record->invoice_no)
                                        ->where('invoice_amount', '>', 0)
                                        ->sum('invoice_amount');
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
                                    // Get invoice numbers that match the sales_admin filter
                                    $invoiceNosWithSalesAdmin = Invoice::query()
                                        ->whereIn('salesperson', $allowedSalespersons)
                                        ->where('invoice_amount', '>', 0);

                                    if ($salesAdminFilter === '') {
                                        // Filter for unassigned
                                        $invoiceNosWithSalesAdmin->where(function ($q) {
                                            $q->whereNull('sales_admin')
                                            ->orWhere('sales_admin', '');
                                        });
                                    } else {
                                        // Filter for specific sales_admin
                                        $invoiceNosWithSalesAdmin->where('sales_admin', $salesAdminFilter);
                                    }

                                    $matchingInvoiceNos = $invoiceNosWithSalesAdmin
                                        ->distinct('invoice_no')
                                        ->pluck('invoice_no')
                                        ->toArray();

                                    // Only include credit notes for these invoice numbers
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

                $query = $query->select([
                    DB::raw('MIN(id) as id'),
                    'salesperson',
                    'sales_admin', // Add this line to include sales_admin in the GROUP BY
                    'invoice_no',
                    'invoice_date',
                    'company_name',
                    DB::raw('SUM(invoice_amount) as total_invoice_amount')
                ])
                ->whereIn('salesperson', $allowedSalespersons)
                ->where('invoice_amount', '>', 0)
                ->groupBy('invoice_no', 'salesperson', 'sales_admin', 'invoice_date', 'company_name') // Add sales_admin here
                ->havingRaw('SUM(invoice_amount) > 0')
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
                                ->where('invoice_amount', '>', 0)
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
                            // Filter for unassigned (null or empty sales_admin)
                            return $query->where(function ($q) {
                                $q->whereNull('sales_admin')
                                ->orWhere('sales_admin', '');
                            });
                        }

                        // Filter for specific sales_admin value
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
