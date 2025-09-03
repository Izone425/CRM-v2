<?php
namespace App\Filament\Pages;

use App\Models\DebtorAging;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
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
            // Log the debtor code to check what we're receiving
            Log::info("Fetching invoices for debtor code", [
                'code' => $debtorCode,
                'type' => gettype($debtorCode)
            ]);

            $sql = "SELECT
                id, doc_key, debtor_code, company_name,
                invoice_date, invoice_number, due_date, aging_date,
                exchange_rate, currency_code, total, invoice_amount,
                outstanding, salesperson, support,
                created_at, updated_at
            FROM debtor_agings
            WHERE debtor_code = ?
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

                        TextColumn::make('debtor_code')
                            ->label('Debtor Code')
                            ->searchable()
                            ->color('gray'),
                    ]),

                    Stack::make([
                        TextColumn::make('total_outstanding')
                            ->label('Outstanding')
                            ->numeric(2)
                            ->alignRight(),

                        TextColumn::make('currency_code')
                            ->label('Currency')
                            ->alignRight()
                            ->color('gray')
                            ->size('sm'),
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

                        TextColumn::make('earliest_due_date')
                            ->label('Earliest Due')
                            ->date('Y-m-d')
                            ->color('gray')
                            ->size('sm')
                            ->alignRight(),
                    ]),

                    Stack::make([
                        // Calculate aging buckets based on earliest due date
                        // TextColumn::make('aging_status')
                        //     ->label('Aging Status')
                        //     ->formatStateUsing(function ($record) {
                        //         $earliest = Carbon::parse($record->earliest_due_date);
                        //         $now = Carbon::now();
                        //         $days = $earliest->diffInDays($now, false);

                        //         if ($days <= 0) {
                        //             return 'Current';
                        //         } elseif ($days <= 30) {
                        //             return '1 Month';
                        //         } elseif ($days <= 60) {
                        //             return '2 Months';
                        //         } elseif ($days <= 90) {
                        //             return '3 Months';
                        //         } elseif ($days <= 120) {
                        //             return '4 Months';
                        //         } else {
                        //             return '5+ Months';
                        //         }
                        //     })
                        //     ->color(function ($record) {
                        //         $earliest = Carbon::parse($record->earliest_due_date);
                        //         $now = Carbon::now();
                        //         $days = $earliest->diffInDays($now, false);

                        //         if ($days <= 0) return 'success';
                        //         if ($days <= 30) return 'info';
                        //         if ($days <= 60) return 'warning';
                        //         if ($days <= 90) return 'orange';
                        //         if ($days <= 120) return 'danger';
                        //         return 'danger';
                        //     }),

                        // TextColumn::make('earliest_due_date')
                        //     ->label('Earliest Due')
                        //     ->date('Y-m-d')
                        //     ->color('gray')
                        //     ->size('sm'),
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
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('earliest_due_date', 'asc');
    }
}
