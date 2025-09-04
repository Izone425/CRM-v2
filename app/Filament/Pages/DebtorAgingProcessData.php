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

    public function mount(): void
    {
        // Debug - log a sample of debtor codes to see what's happening
        $sampleCodes = DB::table('debtor_agings')
            ->select('debtor_code')
            ->distinct()
            ->limit(5)
            ->get();

        Log::info("Sample debtor codes", ['codes' => $sampleCodes->toArray()]);
    }

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
            ORDER BY due_date ASC";

            return DB::select($sql, [$debtorCode]);
        } catch (\Exception $e) {
            Log::error("Error fetching invoices for debtor $debtorCode: " . $e->getMessage());
            return [];
        }
    }
}

class DebtorAgingProcessData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Debtor Aging';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.debtor-aging-process-data';

    public function table(Table $table): Table
    {
        // Get the current date for aging calculations
        $currentDate = Carbon::now();

        return $table
            ->query(function () {
                // Build the query with aggregation by debtor_code
                return DebtorAgingData::query()
                    ->selectRaw("
                        CAST(debtor_code AS CHAR) AS debtor_code,
                        ANY_VALUE(company_name) AS company_name,
                        ANY_VALUE(currency_code) AS currency_code,
                        SUM(total) AS total_amount,
                        SUM(outstanding) AS total_outstanding,
                        COUNT(*) AS invoice_count,
                        MIN(due_date) AS earliest_due_date,
                        MAX(due_date) AS latest_due_date,
                        ANY_VALUE(exchange_rate) AS exchange_rate,
                        ANY_VALUE(salesperson) AS salesperson,
                        ANY_VALUE(support) AS support
                    ")
                    ->where('outstanding', '>', 0)
                    ->where('debtor_code', '!=', '') // Avoid empty strings
                    ->whereNotNull('debtor_code') // Avoid nulls
                    ->groupBy('debtor_code');
            })
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('company_name')
                            ->label('Company')
                            ->searchable()
                            ->weight('bold'),
                    ]),

                    Stack::make([
                        TextColumn::make('total_outstanding')
                            ->label('Outstanding')
                            ->numeric(2)
                            ->alignRight(),
                    ]),

                    Stack::make([
                        TextColumn::make('total_outstanding_rm')
                            ->label('Bal in RM')
                            ->numeric(2)
                            ->money('MYR')
                            ->alignRight()
                            ->state(function ($record) {
                                // Calculate the balance in RM
                                if ($record->currency_code === 'MYR') {
                                    return $record->total_outstanding;
                                }

                                return $record->total_outstanding * $record->exchange_rate;
                            }),
                    ]),
                ])->from('md'),

                // Collapsible panel to show individual invoices
                Panel::make([
                    TextColumn::make('debtor_code')
                        ->label('')
                        ->formatStateUsing(function ($state, $record) {
                            // Ensure we're dealing with a string
                            $debtorCode = (string)$state;

                            // Debug with more info
                            Log::info("Debtor code in panel", [
                                'state' => $state,
                                'debtor_code' => $debtorCode,
                                'type' => gettype($state)
                            ]);

                            return view('components.debtor-invoices', [
                                'invoices' => DebtorAgingData::getInvoicesForDebtor($debtorCode),
                            ]);
                        })
                        ->html(),
                ])->collapsible()->collapsed(),
            ])
            ->filters([
                // No1: Filter by Year
                Filter::make('invoice_year')
                    ->form([
                        Select::make('year')
                            ->label('Invoice Year')
                            ->options(function() {
                                // Get all years from invoice_date, from current year back to 2 years
                                $currentYear = (int)date('Y');
                                $years = [];
                                for ($i = $currentYear; $i >= $currentYear - 2; $i--) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['year']) && $data['year']) {
                            return $query->whereYear('invoice_date', $data['year']);
                        }
                        return $query;
                    }),

                // No2: Filter by Month
                Filter::make('invoice_month')
                    ->form([
                        Select::make('month')
                            ->label('Invoice Month')
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
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['month']) && $data['month']) {
                            return $query->whereMonth('invoice_date', $data['month']);
                        }
                        return $query;
                    }),

                // No3: Filter by Salesperson
                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        return DebtorAgingData::distinct('salesperson')
                            ->whereNotNull('salesperson')
                            ->where('salesperson', '!=', '')
                            ->pluck('salesperson', 'salesperson')
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),

                // No4: Filter by Currency
                SelectFilter::make('currency_code')
                    ->label('Currency')
                    ->options(function () {
                        return DebtorAgingData::distinct('currency_code')
                            ->whereNotNull('currency_code')
                            ->where('currency_code', '!=', '')
                            ->pluck('currency_code', 'currency_code')
                            ->toArray();
                    })
                    ->multiple(),

                // No5: Filter by Amount Range
                Filter::make('amount_range')
                    ->form([
                        Select::make('amount_filter_type')
                            ->label('Amount Filter Type')
                            ->options([
                                'above' => 'Above Amount',
                                'below' => 'Below Amount',
                                'between' => 'Between Amounts',
                            ])
                            ->reactive(),

                        TextInput::make('min_amount')
                            ->label(function (callable $get) {
                                return $get('amount_filter_type') === 'between' ? 'Minimum Amount' : 'Amount';
                            })
                            ->numeric()
                            ->visible(function (callable $get) {
                                return in_array($get('amount_filter_type'), ['above', 'below', 'between']);
                            }),

                        TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->visible(function (callable $get) {
                                return $get('amount_filter_type') === 'between';
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['amount_filter_type'])) {
                            if ($data['amount_filter_type'] === 'above' && isset($data['min_amount'])) {
                                return $query->where('outstanding', '>=', $data['min_amount']);
                            }

                            if ($data['amount_filter_type'] === 'below' && isset($data['min_amount'])) {
                                return $query->where('outstanding', '<=', $data['min_amount']);
                            }

                            if ($data['amount_filter_type'] === 'between' &&
                                isset($data['min_amount']) && isset($data['max_amount'])) {
                                // Use two having conditions instead of whereBetween
                                return $query->where('outstanding', '>=', $data['min_amount'])
                                            ->where('outstanding', '<=', $data['max_amount']);
                            }
                        }
                        return $query;
                    }),

                // No6: Filter Value (Negative/Positive/All)
                Filter::make('value_type')
                    ->form([
                        Radio::make('value_type')
                            ->label('Value Type')
                            ->options([
                                'positive' => 'Positive Values',
                                'negative' => 'Negative Values',
                                'all' => 'All Values',
                            ])
                            ->default('all'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value_type'])) {
                            if ($data['value_type'] === 'positive') {
                                return $query->having('total_outstanding', '>', 0);
                            }

                            if ($data['value_type'] === 'negative') {
                                return $query->having('total_outstanding', '<', 0);
                            }
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (isset($data['value_type']) && $data['value_type'] !== 'all') {
                            return 'Value Type: ' . ucfirst($data['value_type']);
                        }
                        return null;
                    }),

                // No7: Filter Internal Company
                Filter::make('company_name')
                    ->form([
                        Select::make('mode')
                            ->label('Filter Mode')
                            ->options([
                                'include' => 'Include Selected Companies',
                                'exclude' => 'Exclude Selected Companies',
                            ])
                            ->default('exclude'),

                        Select::make('companies')
                            ->label('Companies')
                            ->options(function () {
                                return DebtorAgingData::query()
                                    ->select('company_name')
                                    ->distinct()
                                    ->whereNotNull('company_name')
                                    ->where('company_name', '!=', '')
                                    ->pluck('company_name', 'company_name')
                                    ->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // Only apply filter if companies are selected
                        if (isset($data['companies']) && !empty($data['companies'])) {
                            // If mode is "include", use whereIn
                            if (($data['mode'] ?? 'include') === 'include') {
                                return $query->whereIn('company_name', $data['companies']);
                            }

                            // If mode is "exclude", use whereNotIn
                            return $query->whereNotIn('company_name', $data['companies']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (isset($data['companies']) && !empty($data['companies'])) {
                            $mode = ($data['mode'] ?? 'include') === 'include' ? 'Including' : 'Excluding';
                            $count = count($data['companies']);

                            return $mode . ' ' . $count . ' ' . ($count === 1 ? 'company' : 'companies');
                        }

                        return null;
                    }),

                Filter::make('stl_filter')
                    ->form([
                        Radio::make('stl_mode')
                            ->label('STL Records')
                            ->options([
                                'include' => 'Show Only STL Records',
                                'exclude' => 'Exclude STL Records',
                                'all' => 'Show All Records',
                            ])
                            ->default('all'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['stl_mode'])) {
                            if ($data['stl_mode'] === 'include') {
                                // Show only STL records
                                return $query->where('debtor_code', 'like', 'ICT%');
                            }

                            if ($data['stl_mode'] === 'exclude') {
                                // Exclude STL records
                                return $query->where(function ($query) {
                                    $query->where('debtor_code', 'not like', 'ICT%')
                                        ->orWhereNull('debtor_code');
                                });
                            }
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (isset($data['stl_mode']) && $data['stl_mode'] !== 'all') {
                            return $data['stl_mode'] === 'include'
                                ? 'Showing only STL records'
                                : 'Excluding STL records';
                        }
                        return null;
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
