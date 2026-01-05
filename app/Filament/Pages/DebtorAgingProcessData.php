<?php
namespace App\Filament\Pages;

use App\Models\DebtorAging;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

// Temporary model for the grouped query
class DebtorAgingData extends Model
{
    protected $table = 'debtor_agings';
    protected $primaryKey = 'debtor_code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'debtor_code' => 'string',
    ];

    // REQUEST 1: Exclude these 7 salespeople
    protected static array $excludedSalespeople = [
        'MUIM',
        'YASMIN',
        'FARHANAH',
        'JOSHUA',
        'AZIZ',
        'BARI',
        'VINCE'
    ];

    // Add getKey method to ensure we have string keys
    public function getKey()
    {
        $key = $this->getAttribute($this->getKeyName());
        return $key !== null ? (string) $key : 'record-' . uniqid();
    }

    // Get invoices for a specific debtor
    public static function getInvoicesForDebtor($debtorCode)
    {
        try {
            $sql = "SELECT
                id, doc_key, debtor_code, company_name,
                invoice_date, invoice_number, due_date, aging_date,
                exchange_rate, currency_code, total, invoice_amount,
                outstanding, salesperson, support,
                created_at, updated_at
            FROM debtor_agings
            WHERE debtor_code = ?
            AND outstanding > 0
            AND (debtor_code LIKE 'ARU%' OR debtor_code LIKE 'ARM%')
            AND (
                (salesperson NOT IN ('" . implode("','", self::$excludedSalespeople) . "')
                OR salesperson IS NULL
                OR salesperson = '')
            )
            ORDER BY due_date ASC";

            return DB::select($sql, [$debtorCode]);
        } catch (\Exception $e) {
            Log::error("Error fetching invoices for debtor $debtorCode: " . $e->getMessage());
            return [];
        }
    }

    // Base query method with default filters
    public static function getBaseQuery(): Builder
    {
        return self::query()
            // Only show ARU and ARM debtor codes
            ->where(function ($query) {
                $query->where('debtor_code', 'like', 'ARU%')
                      ->orWhere('debtor_code', 'like', 'ARM%');
            })
            // Only show amounts greater than zero
            ->where('outstanding', '>', 0)
            // REQUEST 1: Exclude the 7 salespeople BUT keep NULL/blank salespeople
            ->where(function($q) {
                $q->whereNotIn('salesperson', self::$excludedSalespeople)
                  ->orWhereNull('salesperson')
                  ->orWhere('salesperson', '');
            });
    }
}

class DebtorAgingProcessData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Debtor Aging Process Data';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 11;
    protected static ?string $title = '';

    protected static string $view = 'filament.pages.debtor-aging-process-data';

    // REQUEST 2: Filter properties like DebtorAgingRawData
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
    public $filterDecimalPlaces = 2; // REQUEST 4: Default 2 decimal places

    // REQUEST 1: Exclude these 7 salespeople
    protected array $excludedSalespeople = [
        'MUIM',
        'YASMIN',
        'FARHANAH',
        'JOSHUA',
        'AZIZ',
        'BARI',
        'VINCE'
    ];

    // Method to get total outstanding amount that respects filters
    public function getTotalOutstandingAmount(): string
    {
        try {
            // Get the filtered query from the table
            $query = $this->getFilteredTableQuery();

            // Apply the same calculation used in the table column's state callback
            $total = $query
                ->get()
                ->sum(function ($record) {
                    if ($record->currency_code === 'MYR') {
                        return $record->total_outstanding;
                    }

                    if ($record->total_outstanding && $record->exchange_rate) {
                        // REQUEST 4: Use 2 decimal point precision for exchange rate calculation
                        return round($record->total_outstanding * $record->exchange_rate, 2);
                    }

                    return 0;
                });

            // Format the amount as currency
            return Number::currency($total, 'MYR');
        } catch (\Exception $e) {
            // Log error and return a default value
            Log::error('Error calculating total outstanding amount: ' . $e->getMessage());
            return Number::currency(0, 'MYR');
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
        return $table
            ->query(function () {
                // Build the query with aggregation by debtor_code using base query
                return DebtorAgingData::getBaseQuery()
                    // ->selectRaw("
                    //     CAST(debtor_code AS CHAR) AS debtor_code,
                    //     ANY_VALUE(company_name) AS company_name,
                    //     ANY_VALUE(currency_code) AS currency_code,
                    //     SUM(total) AS total_amount,
                    //     SUM(outstanding) AS total_outstanding,
                    //     COUNT(*) AS invoice_count,
                    //     MIN(due_date) AS earliest_due_date,
                    //     MAX(due_date) AS latest_due_date,
                    //     ANY_VALUE(exchange_rate) AS exchange_rate,
                    //     ANY_VALUE(salesperson) AS salesperson,
                    //     ANY_VALUE(support) AS support
                    // ")
                    // ->where('debtor_code', '!=', '') // Avoid empty strings
                    // ->whereNotNull('debtor_code') // Avoid nulls
                    // ->groupBy('debtor_code');
                    ->where('id', 0);
            })
            ->columns([
                // REQUEST 3: Main header - Company Name + Total Invoice Count + Total Outstanding
                Split::make([
                    Stack::make([
                        TextColumn::make('company_name')
                            ->label('Company')
                            ->searchable()
                            ->weight('bold'),
                    ]),

                    Stack::make([
                        TextColumn::make('invoice_count')
                            ->label('Total Invoices')
                            ->formatStateUsing(fn ($state) => $state . ' invoice(s)')
                            ->color('gray')
                            ->alignCenter(),
                    ]),

                    Stack::make([
                        TextColumn::make('total_outstanding_rm')
                            ->label('Total Outstanding (RM)')
                            ->numeric(
                                decimalPlaces: fn() => $this->filterDecimalPlaces, // REQUEST 4: Dynamic decimal places
                                decimalSeparator: '.',
                                thousandsSeparator: ','
                            )
                            ->prefix('RM ')
                            ->alignRight()
                            ->weight('bold')
                            ->color('danger')
                            ->state(function ($record) {
                                // Calculate the balance in RM
                                if ($record->currency_code === 'MYR') {
                                    return $record->total_outstanding;
                                }

                                // Apply exchange rate conversion with dynamic decimal precision
                                if ($record->total_outstanding && $record->exchange_rate) {
                                    return round($record->total_outstanding * $record->exchange_rate, $this->filterDecimalPlaces);
                                }

                                return 0;
                            }),
                    ]),
                ])->from('md'),

                // REQUEST 5: Collapsible panel with new sub headers
                Panel::make([
                    TextColumn::make('debtor_code')
                        ->label('')
                        ->formatStateUsing(function ($state, $record) {
                            // Ensure we're dealing with a string
                            $debtorCode = (string)$state;

                            return view('components.debtor-invoices', [
                                'invoices' => DebtorAgingData::getInvoicesForDebtor($debtorCode),
                                'filterDecimalPlaces' => $this->filterDecimalPlaces,
                            ]);
                        })
                        ->html(),
                ])->collapsible()->collapsed(),
            ])
            ->filters([
                // REQUEST 2: Same filters as DebtorAgingRawData

                // Filter 1 - Decimal Places
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

                // Filter 2 - Currency Type
                SelectFilter::make('currency_code')
                    ->options(function () {
                        return DebtorAgingData::getBaseQuery()
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
                        return $query->whereIn('currency_code', $data['values']);
                    }),

                // Filter 3 - Support
                SelectFilter::make('support')
                    ->options(function () {
                        $options = DebtorAgingData::getBaseQuery()
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
                        return $query->where(function($q) use ($data) {
                            $q->whereIn('support', $data['values']);
                            // Include blank/null if selected
                            if (in_array('blank', $data['values'])) {
                                $q->orWhereNull('support')->orWhere('support', '');
                            }
                        });
                    }),

                // Filter 4 - Salesperson (including blank)
                SelectFilter::make('salesperson')
                    ->options(function () {
                        $options = DebtorAgingData::getBaseQuery()
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

                            $query->whereBetween('invoice_date', [$startDate, $endDate]);
                        } else {
                            $this->filterInvoiceDateFrom = null;
                            $this->filterInvoiceDateUntil = null;
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
                        return $query->whereMonth('invoice_date', $data['value']);
                    }),
            ])
            ->filtersFormColumns(3)
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('earliest_due_date', 'asc');
    }
}
