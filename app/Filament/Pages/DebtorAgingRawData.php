<?php
namespace App\Filament\Pages;

use App\Models\DebtorAging;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

class DebtorAgingRawData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Debtor Aging Raw Data';
    protected static ?string $title = '';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.debtor-aging-raw-data';

    public function getTotalOutstandingAmount(): string
    {
        try {
            // Get the query after all filters have been applied
            $query = $this->getTableQuery();

            // If query is null, create a base query
            if (!$query) {
                $query = DebtorAging::query();
            }

            // Apply the same calculation used in the table column's state callback
            $total = $query
                ->get()
                ->sum(function ($record) {
                    if ($record->currency_code === 'MYR') {
                        return $record->outstanding;
                    }

                    if ($record->outstanding && $record->exchange_rate) {
                        return $record->outstanding * $record->exchange_rate;
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

    public function table(Table $table): Table
    {
        // Get the current date for aging calculations
        $currentDate = Carbon::now();

        return $table
            ->query(DebtorAging::query())
            ->columns([
                TextColumn::make('debtor_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->sortable(),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('support')
                    ->label('Support')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('balance_in_rm')
                    ->label('Outstanding Amount')
                    ->numeric(2)
                    ->money('MYR') // Always display in MYR
                    ->state(function ($record) {
                        // Calculate the balance in RM by multiplying outstanding by exchange_rate
                        if ($record->currency_code === 'MYR') {
                            // If already in MYR, return as is
                            return $record->outstanding;
                        }

                        // Apply exchange rate conversion
                        if ($record->outstanding && $record->exchange_rate) {
                            return $record->outstanding * $record->exchange_rate;
                        }

                        return 0;
                    })
                    ->sortable(),
            ])
            ->filters([
                // No1: Filter by Year
                Filter::make('invoice_year')
                    ->form([
                        Select::make('year')
                            ->label('Invoice Year')
                            ->options(function() {
                                // Get all years from invoice_date, from current year back to 5 years
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
                        return DebtorAging::distinct()
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
                        return DebtorAging::distinct()
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
                                return $query->whereBetween('outstanding', [$data['min_amount'], $data['max_amount']]);
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
                                return $query->where('outstanding', '>', 0);
                            }

                            if ($data['value_type'] === 'negative') {
                                return $query->where('outstanding', '<', 0);
                            }
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (isset($data['value_type'])) {
                            return 'Value Type: ' . ucfirst($data['value_type']);
                        }
                        return null;
                    }),

                Filter::make('company_name')
                    ->form([
                        Select::make('mode')
                            ->label('Filter Mode')
                            ->options([
                                'include' => 'Include Selected Companies',
                                'exclude' => 'Exclude Selected Companies',
                            ])
                            ->default('include'),

                        Select::make('companies')
                            ->label('Companies')
                            ->options(function () {
                                return DebtorAging::distinct()
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
                                return $query->where('debtor_code', 'like', 'STL%');
                            }

                            if ($data['stl_mode'] === 'exclude') {
                                return $query->where(function ($query) {
                                    $query->where('debtor_code', 'not like', 'STL%')
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

                // Only show records with outstanding amounts (optional, can be removed)
                Filter::make('has_outstanding')
                    ->label('Only Outstanding')
                    ->query(fn (Builder $query): Builder => $query->where('outstanding', '!=', 0))
                    ->default(true),
            ])
            ->filtersFormColumns(3) // Show filters in 3 columns for better spacing
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultSort('invoice_date', 'desc');
    }
}
