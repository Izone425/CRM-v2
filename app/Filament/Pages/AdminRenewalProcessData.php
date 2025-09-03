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

    // Get products for a specific company
    public static function getProductsForCompany($companyId)
    {
        try {
            $sql = "SELECT
                f_currency, f_id, f_company_name, f_company_id,
                f_name, f_invoice_no, f_total_amount, f_unit,
                f_start_date, f_expiry_date, Created, payer,
                payer_id, f_created_time
            FROM crm_expiring_license
            WHERE f_company_id = ?
            ORDER BY f_expiry_date ASC";

            return DB::connection('frontenddb')->select($sql, [$companyId]);
        } catch (\Exception $e) {
            Log::error("Error fetching products for company $companyId: " . $e->getMessage());
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

                // Apply date filtering directly at the raw SQL level
                if (!empty($this->startDate) && !empty($this->endDate)) {
                    // Filter at the row level before aggregation
                    $baseQuery->whereRaw('f_expiry_date BETWEEN ? AND ?', [$this->startDate, $this->endDate]);
                    Log::info("Applying direct filter from {$this->startDate} to {$this->endDate}");
                }

                // Now apply the aggregation
                $baseQuery->selectRaw("
                    f_company_id,
                    ANY_VALUE(f_company_name) AS f_company_name,
                    ANY_VALUE(f_currency) AS f_currency,
                    SUM(f_total_amount) AS total_amount,
                    SUM(f_unit) AS total_units,
                    COUNT(*) AS total_products,
                    MIN(f_expiry_date) AS earliest_expiry,
                    ANY_VALUE(f_created_time) AS f_created_time
                ")
                ->groupBy('f_company_id');

                // Log the complete query
                Log::info("SQL query: " . $baseQuery->toSql());
                Log::info("SQL bindings: ", $baseQuery->getBindings());

                return $baseQuery;
            })
            ->filters([
                SelectFilter::make('f_name')
                    ->label('Products')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        // Get distinct product names
                        return LicenseData::query()
                            ->distinct()
                            ->orderBy('f_name')
                            ->pluck('f_name', 'f_name')
                            ->toArray();
                    })
                    ->indicator('Products'),

                Filter::make('earliest_expiry')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('Expiry Date Range')
                            ->placeholder('Select expiry date range'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            try {
                                [$start, $end] = explode(' - ', $data['date_range']);

                                $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->startOfDay()->format('Y-m-d');
                                $endDate   = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay()->format('Y-m-d');

                                // Apply filter at row-level BEFORE aggregation
                                $query->whereBetween('f_expiry_date', [$startDate, $endDate]);
                            } catch (\Exception $e) {
                                Log::error("Date filter error: " . $e->getMessage());
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
                        return null;
                    }),
            ])
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('f_company_name')
                            ->label('Company')
                            ->searchable()
                            ->weight('bold'),

                        TextColumn::make('total_products')
                            ->label('Products')
                            ->formatStateUsing(fn ($state) => "{$state} products")
                            ->color('gray'),
                    ]),

                    Stack::make([
                        TextColumn::make('total_amount')
                            ->label('Amount')
                            ->numeric(2)
                            ->alignRight(),

                        TextColumn::make('f_currency')
                            ->label('Currency')
                            ->alignRight()
                            ->color('gray')
                            ->size('sm'),
                    ]),

                    Stack::make([
                        TextColumn::make('earliest_expiry')
                            ->label('Expiry Date')
                            ->date('Y-m-d'),

                        TextColumn::make('total_units')
                            ->label('Units')
                            ->numeric()
                            ->prefix('Total: ')
                            ->color('gray')
                            ->size('sm'),
                    ]),
                ])->from('md'),

                // Collapsible content - this will be hidden by default but can be expanded
                Panel::make([
                    TextColumn::make('f_company_id')
                        ->label('')
                        ->formatStateUsing(function ($state, $record) {
                            return view('components.company-products', [
                                'products' => RenewalData::getProductsForCompany($state),
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
