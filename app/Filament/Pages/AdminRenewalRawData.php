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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

// Create a model for the license data
class LicenseData extends Model
{
    // Set the connection to the frontenddb database
    protected $connection = 'frontenddb';
    protected $table = 'crm_expiring_license';
    protected $primaryKey = 'f_id';
    public $timestamps = false;

    // Add getKey method to ensure we have string keys
    public function getKey()
    {
        $key = $this->getAttribute($this->getKeyName());
        return $key !== null ? (string) $key : 'record-' . uniqid();
    }
}

class AdminRenewalRawData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Renewal Raw Data';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 49; // Placed before Process Data

    protected static string $view = 'filament.pages.admin-renewal-raw-data';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Simple query without grouping - shows each record individually
                return LicenseData::query();
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

                Filter::make('expiry_date_range')
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
                                $endDate = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay()->format('Y-m-d');

                                // Simple direct filtering on the date column
                                $query->whereBetween('f_expiry_date', [$startDate, $endDate]);

                                // Log the filter
                                Log::info("Filtering expiry dates between: {$startDate} and {$endDate}");
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
                // Simple TextColumns without any complex layouts
                TextColumn::make('f_company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('f_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('f_total_amount')
                    ->label('Amount')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('f_currency')
                    ->label('Currency')
                    ->sortable(),

                TextColumn::make('f_expiry_date')
                    ->label('Expiry Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('f_unit')
                    ->label('Units')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('f_invoice_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('f_created_time')
                    ->label('Created')
                    ->date('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('payer')
                    ->label('Payer')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('f_start_date')
                    ->label('Start Date')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('f_expiry_date', 'asc');
    }
}
