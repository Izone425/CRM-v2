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

    // Add getKey method to ensure we have string keys
    public function getKey()
    {
        $key = $this->getAttribute($this->getKeyName());
        return $key !== null ? (string) $key : 'record-' . uniqid();
    }

    // Get invoices for a specific company
    public static function getInvoicesForCompany($companyId, $startDate = null, $endDate = null)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            // If no date range provided, use default 60 days
            if (!$startDate || !$endDate) {
                $startDate = $today;
                $endDate = Carbon::now()->addDays(60)->format('Y-m-d');
            }

            $sql = "SELECT
                f_invoice_no,
                f_currency,
                SUM(f_total_amount) AS invoice_total_amount,
                SUM(f_unit) AS invoice_total_units,
                COUNT(*) AS invoice_product_count,
                MIN(f_expiry_date) AS invoice_earliest_expiry,
                MAX(f_expiry_date) AS invoice_latest_expiry,
                ANY_VALUE(f_company_name) AS f_company_name,
                ANY_VALUE(f_company_id) AS f_company_id
            FROM crm_expiring_license
            WHERE f_company_id = ?
            AND f_expiry_date >= ?
            AND f_expiry_date <= ?
            GROUP BY f_invoice_no, f_currency
            HAVING COUNT(*) > 0
            ORDER BY f_invoice_no ASC";

            return DB::connection('frontenddb')->select($sql, [$companyId, $startDate, $endDate]);
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

            // If no date range provided, use default 60 days
            if (!$startDate || !$endDate) {
                $startDate = $today;
                $endDate = Carbon::now()->addDays(60)->format('Y-m-d');
            }

            $sql = "SELECT
                f_currency, f_id, f_company_name, f_company_id,
                f_name, f_invoice_no, f_total_amount, f_unit,
                f_start_date, f_expiry_date, Created, payer,
                payer_id, f_created_time
            FROM crm_expiring_license
            WHERE f_company_id = ?
            AND f_invoice_no = ?
            AND f_expiry_date >= ?
            AND f_expiry_date <= ?
            ORDER BY f_expiry_date ASC";

            return DB::connection('frontenddb')->select($sql, [$companyId, $invoiceNo, $startDate, $endDate]);
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

                // Now apply the aggregation - this will only include non-expired products
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
                ->havingRaw('COUNT(*) > 0'); // Ensure we only show companies with at least one non-expired product

                return $baseQuery;
            })
            ->filters([
                SelectFilter::make('f_name')
                    ->label('Products')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        // Get distinct product names (only for non-expired records)
                        $today = Carbon::now()->format('Y-m-d');
                        return RenewalData::query()
                            ->whereRaw('f_expiry_date >= ?', [$today])
                            ->distinct()
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

                            $query->whereIn('f_company_id', $subQuery);
                        }
                    })
                    ->indicator('Products'),

                SelectFilter::make('f_currency')
                    ->label('Currency')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        // Get distinct currencies (only for non-expired records)
                        $today = Carbon::now()->format('Y-m-d');
                        return RenewalData::query()
                            ->whereRaw('f_expiry_date >= ?', [$today])
                            ->distinct()
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

                            $query->whereIn('f_company_id', $subQuery);
                        }
                    })
                    ->indicator('Currency'),

                Filter::make('earliest_expiry')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('Expiry Date Range')
                            ->placeholder('Select expiry date range')
                            ->default(function () {
                                // Default to next 60 days
                                $today = Carbon::now()->format('d/m/Y');
                                $next60Days = Carbon::now()->addDays(60)->format('d/m/Y');
                                return $today . ' - ' . $next60Days;
                            }),
                    ])
                    ->query(function (Builder $query, array $data) {
                        // If no custom date range is set, apply default 60-day filter
                        if (empty($data['date_range'])) {
                            $today = Carbon::now()->format('Y-m-d');
                            $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
                            $query->whereBetween('f_expiry_date', [$today, $next60Days]);
                        } else {
                            try {
                                [$start, $end] = explode(' - ', $data['date_range']);

                                $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->startOfDay()->format('Y-m-d');
                                $endDate   = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay()->format('Y-m-d');

                                // Ensure start date is not in the past
                                $today = Carbon::now()->format('Y-m-d');
                                if ($startDate < $today) {
                                    $startDate = $today;
                                }

                                // Apply filter at row-level BEFORE aggregation
                                $query->whereBetween('f_expiry_date', [$startDate, $endDate]);
                            } catch (\Exception $e) {
                                Log::error("Date filter error: " . $e->getMessage());
                                // Fallback to 60-day default if date parsing fails
                                $today = Carbon::now()->format('Y-m-d');
                                $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
                                $query->whereBetween('f_expiry_date', [$today, $next60Days]);
                            }
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);

                            return 'Expiry: ' .
                                Carbon::createFromFormat('d/m/Y', trim($start))->format('j M Y') .
                                ' → ' .
                                Carbon::createFromFormat('d/m/Y', trim($end))->format('j M Y');
                        }
                        // Show default indicator
                        return 'Expiry: ' .
                            Carbon::now()->format('j M Y') .
                            ' → ' .
                            Carbon::now()->addDays(60)->format('j M Y') .
                            ' (Default 60 days)';
                    }),
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
