<?php
namespace App\Filament\Pages;

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
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;

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

class AdminRenewalRawData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Renewal Raw Data';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.admin-renewal-raw-data';

    protected function getTableQuery(): Builder
    {
        return RenewalData::query()
            ->selectRaw("
                f_company_id,
                ANY_VALUE(f_company_name) AS f_company_name,
                ANY_VALUE(f_currency) AS f_currency,
                SUM(f_total_amount) AS total_amount,
                SUM(f_unit) AS total_units,
                COUNT(*) AS total_products,
                MIN(f_expiry_date) AS earliest_expiry
            ")
            ->groupBy('f_company_id')
            ->orderByRaw('MIN(f_expiry_date) ASC');
    }


    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return $this->getTableQuery();
            })
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
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('earliest_expiry', 'asc');
    }
}
