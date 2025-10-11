<?php
namespace App\Filament\Pages;

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
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

// Create a temporary model for the query
class RenewalData extends Model
{
    // Set the connection to the frontenddb database
    protected $connection = 'frontenddb';
    protected $table = 'crm_expiring_license';
    protected $primaryKey = 'f_company_id'; // Changed to company ID
    public $timestamps = false;

    // Add custom attribute for storing related products
    protected $appends = ['related_products'];

    // Define allowed products - only HR licenses
    public static $allowedProducts = [
        'TimeTec TA (1 User License)',
        'TimeTec TA (10 User License)',
        'TimeTec Leave (1 User License)',
        'TimeTec Leave (10 User License)',
        'TimeTec Claim (1 User License)',
        'TimeTec Claim (10 User License)',
        'TimeTec Payroll (1 Payroll License)',
        'TimeTec Payroll (10 Payroll License)',
    ];

    // Add getKey method to ensure we have string keys
    public function getKey()
    {
        $key = $this->getAttribute($this->getKeyName());
        return $key !== null ? (string) $key : 'record-' . uniqid();
    }

    // Helper method to apply product filtering
    public static function applyProductFilter($query)
    {
        $query->where(function ($q) {
            foreach (self::$allowedProducts as $product) {
                $q->orWhere('f_name', 'LIKE', '%' . $product . '%');
            }
        });
        return $query;
    }

    // Get invoices for a specific company
    public static function getInvoicesForCompany($companyId, $startDate = null, $endDate = null)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            $query = DB::connection('frontenddb')->table('crm_expiring_license')
                ->select([
                    'f_invoice_no',
                    'f_currency',
                    DB::raw('SUM(f_total_amount) AS invoice_total_amount'),
                    DB::raw('SUM(f_unit) AS invoice_total_units'),
                    DB::raw('COUNT(*) AS invoice_product_count'),
                    DB::raw('MIN(f_expiry_date) AS invoice_earliest_expiry'),
                    DB::raw('MAX(f_expiry_date) AS invoice_latest_expiry'),
                    DB::raw('ANY_VALUE(f_company_name) AS f_company_name'),
                    DB::raw('ANY_VALUE(f_company_id) AS f_company_id')
                ])
                ->where('f_company_id', $companyId)
                ->whereRaw('f_expiry_date >= ?', [$today]);

            // Apply product filtering
            $query->where(function ($q) {
                foreach (self::$allowedProducts as $product) {
                    $q->orWhere('f_name', 'LIKE', '%' . $product . '%');
                }
            });

            // Apply date range if provided
            if ($startDate && $endDate) {
                $query->whereBetween('f_expiry_date', [$startDate, $endDate]);
            }

            return $query->groupBy('f_invoice_no', 'f_currency')
                ->havingRaw('COUNT(*) > 0')
                ->orderBy('f_invoice_no', 'ASC')
                ->get()
                ->toArray();

        } catch (\Exception $e) {
            Log::error("Error fetching invoices for company $companyId: " . $e->getMessage());
            return [];
        }
    }

    // Get products for a specific company and invoice within date range
    public static function getProductsForInvoice($companyId, $invoiceNo, $startDate = null, $endDate = null)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            $query = DB::connection('frontenddb')->table('crm_expiring_license')
                ->select([
                    'f_currency', 'f_id', 'f_company_name', 'f_company_id',
                    'f_name', 'f_invoice_no', 'f_total_amount', 'f_unit',
                    'f_start_date', 'f_expiry_date', 'Created', 'payer',
                    'payer_id', 'f_created_time'
                ])
                ->where('f_company_id', $companyId)
                ->where('f_invoice_no', $invoiceNo)
                ->whereRaw('f_expiry_date >= ?', [$today]);

            // Apply product filtering
            $query->where(function ($q) {
                foreach (self::$allowedProducts as $product) {
                    $q->orWhere('f_name', 'LIKE', '%' . $product . '%');
                }
            });

            // Apply date range if provided
            if ($startDate && $endDate) {
                $query->whereBetween('f_expiry_date', [$startDate, $endDate]);
            }

            return $query->orderBy('f_expiry_date', 'ASC')
                ->get()
                ->toArray();

        } catch (\Exception $e) {
            Log::error("Error fetching products for company $companyId and invoice $invoiceNo: " . $e->getMessage());
            return [];
        }
    }
}

class AdminRenewalProcessData extends Page implements HasTable
{
    use InteractsWithTable;

    protected $startDate = null;
    protected $endDate = null;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Renewal Raw Data';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.admin-renewal-process-data';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Build the query with pre-filtered data
                $baseQuery = RenewalData::query();

                // Only show records where expiry date has not yet passed (future or today)
                $today = Carbon::now()->format('Y-m-d');
                $baseQuery->whereRaw('f_expiry_date >= ?', [$today]);

                // Apply product filtering - only show allowed HR products
                RenewalData::applyProductFilter($baseQuery);

                // Now apply the aggregation - this will only include non-expired HR products
                $baseQuery->selectRaw("
                    f_company_id,
                    ANY_VALUE(f_company_name) AS f_company_name,
                    ANY_VALUE(f_currency) AS f_currency,
                    SUM(f_total_amount) AS total_amount,
                    SUM(f_unit) AS total_units,
                    COUNT(*) AS total_products,
                    COUNT(DISTINCT f_invoice_no) AS total_invoices,
                    MIN(f_expiry_date) AS earliest_expiry,
                    ANY_VALUE(f_created_time) AS f_created_time
                ")
                ->groupBy('f_company_id')
                ->havingRaw('COUNT(*) > 0'); // Ensure we only show companies with at least one non-expired HR product

                return $baseQuery;
            })
            ->filters([
                Filter::make('expiry_date_range')
                    ->form([
                        DatePicker::make('expiry_from')
                            ->label('Expiry From')
                            ->placeholder('Select start date')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->default(Carbon::now()->format('Y-m-d')), // Default to today

                        DatePicker::make('expiry_until')
                            ->label('Expiry Until')
                            ->placeholder('Select end date')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->default(Carbon::now()->addMonths(3)->format('Y-m-d')), // Default to 3 months from now
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['expiry_from'],
                                function (Builder $query, $date): Builder {
                                    // Filter at the row level before aggregation
                                    $subQuery = RenewalData::query()
                                        ->select('f_company_id')
                                        ->whereRaw('f_expiry_date >= ?', [$date])
                                        ->whereRaw('f_expiry_date >= ?', [Carbon::now()->format('Y-m-d')])
                                        ->distinct();

                                    // Apply product filtering to subquery
                                    RenewalData::applyProductFilter($subQuery);

                                    return $query->whereIn('f_company_id', $subQuery);
                                }
                            )
                            ->when(
                                $data['expiry_until'],
                                function (Builder $query, $date): Builder {
                                    // Filter at the row level before aggregation
                                    $subQuery = RenewalData::query()
                                        ->select('f_company_id')
                                        ->whereRaw('f_expiry_date <= ?', [$date])
                                        ->whereRaw('f_expiry_date >= ?', [Carbon::now()->format('Y-m-d')])
                                        ->distinct();

                                    // Apply product filtering to subquery
                                    RenewalData::applyProductFilter($subQuery);

                                    return $query->whereIn('f_company_id', $subQuery);
                                }
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['expiry_from']) {
                            $indicators[] = Indicator::make('Expiry from: ' . Carbon::parse($data['expiry_from'])->format('d M Y'))
                                ->removeField('expiry_from');
                        }

                        if ($data['expiry_until']) {
                            $indicators[] = Indicator::make('Expiry until: ' . Carbon::parse($data['expiry_until'])->format('d M Y'))
                                ->removeField('expiry_until');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('f_name')
                    ->label('Products')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        // Get distinct product names (only for allowed HR products and non-expired records)
                        $today = Carbon::now()->format('Y-m-d');
                        $query = RenewalData::query()
                            ->whereRaw('f_expiry_date >= ?', [$today]);

                        // Apply product filtering
                        RenewalData::applyProductFilter($query);

                        return $query->distinct()
                            ->orderBy('f_name')
                            ->pluck('f_name', 'f_name')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['values'])) {
                            // Apply product filter at the row level before aggregation
                            $subQuery = RenewalData::query()
                                ->select('f_company_id')
                                ->whereIn('f_name', $data['values'])
                                ->whereRaw('f_expiry_date >= ?', [Carbon::now()->format('Y-m-d')])
                                ->distinct();

                            // Apply product filtering to subquery too
                            RenewalData::applyProductFilter($subQuery);

                            $query->whereIn('f_company_id', $subQuery);
                        }
                    })
                    ->indicator('Products'),

                SelectFilter::make('f_currency')
                    ->label('Currency')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        // Get distinct currencies (only for allowed HR products and non-expired records)
                        $today = Carbon::now()->format('Y-m-d');
                        $query = RenewalData::query()
                            ->whereRaw('f_expiry_date >= ?', [$today]);

                        // Apply product filtering
                        RenewalData::applyProductFilter($query);

                        return $query->distinct()
                            ->orderBy('f_currency')
                            ->whereNotNull('f_currency')
                            ->where('f_currency', '!=', '')
                            ->pluck('f_currency', 'f_currency')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['values'])) {
                            // Apply currency filter at the row level before aggregation
                            $subQuery = RenewalData::query()
                                ->select('f_company_id')
                                ->whereIn('f_currency', $data['values'])
                                ->whereRaw('f_expiry_date >= ?', [Carbon::now()->format('Y-m-d')])
                                ->distinct();

                            // Apply product filtering to subquery too
                            RenewalData::applyProductFilter($subQuery);

                            $query->whereIn('f_company_id', $subQuery);
                        }
                    })
                    ->indicator('Currency'),
            ])
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('f_company_name')
                            ->label('Company')
                            ->searchable()
                            ->formatStateUsing(fn (string $state): string => strtoupper($state))
                            ->weight('bold'),

                        TextColumn::make('total_products')
                            ->label('Products')
                            ->formatStateUsing(fn ($state, $record) => "{$state} products in {$record->total_invoices} invoices")
                            ->color('gray'),
                    ]),

                    Stack::make([
                        TextColumn::make('total_amount')
                            ->label('Amount')
                            ->numeric(2),

                        TextColumn::make('f_currency')
                            ->label('Currency')
                            ->color('gray')
                            ->size('sm'),
                    ]),

                    Stack::make([
                        TextColumn::make('earliest_expiry')
                            ->label('Expiry Date')
                            ->date('Y-m-d')
                            ->color(function ($state) {
                                $today = Carbon::now();
                                $expiryDate = Carbon::parse($state);

                                // Color coding based on how close to expiry
                                if ($expiryDate->isToday()) {
                                    return 'danger'; // Expires today
                                } elseif ($expiryDate->diffInDays($today) <= 7) {
                                    return 'warning'; // Expires within a week
                                } elseif ($expiryDate->diffInDays($today) <= 30) {
                                    return 'info'; // Expires within a month
                                }
                                return 'gray'; // More than a month
                            }),

                        TextColumn::make('total_units')
                            ->label('Units')
                            ->numeric()
                            ->prefix('Total: ')
                            ->color('gray')
                            ->size('sm'),
                    ]),
                ])->from('md'),

                // Collapsible content - shows invoices for the company
                Panel::make([
                    TextColumn::make('f_company_id')
                        ->label('')
                        ->formatStateUsing(function ($state, $record) {
                            return view('components.company-invoices', [
                                'invoices' => RenewalData::getInvoicesForCompany($state),
                                'companyId' => $state,
                            ]);
                        })
                        ->html()
                ])->collapsible()->collapsed(),
            ])
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('earliest_expiry', 'asc');
    }
}
