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
        // Get dates for default 60-day range
        $today = Carbon::today();
        $startDate = $today->format('Y-m-d');
        $endDate = $today->addDays(60)->format('Y-m-d');

        // Format for display in the date range picker
        $defaultDateRange = Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');

        return $table
            ->query(function () use ($startDate, $endDate) {
                // Apply the default 60-day filter to the base query
                return LicenseData::query()
                    ->whereBetween('f_expiry_date', [$startDate, $endDate]);
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
                            ->placeholder('Select expiry date range')
                            ->default($defaultDateRange), // Set default 60-day range
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
                    })
                    ->default(true), // Automatically apply this filter by default
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('NO.')
                    ->rowIndex(),

                TextColumn::make('f_company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)) // NO1: ALL UPPERCASE
                    ->wrap(),

                TextColumn::make('f_name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('f_unit')
                    ->label('Headcount')
                    ->alignCenter() // NO8: Align Right
                    ->numeric()
                    ->sortable(),

                TextColumn::make('f_total_amount')
                    ->label('Amount')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('f_currency')
                    ->label('Currency')
                    ->alignCenter() // NO3: Align Centre
                    ->sortable(),

                TextColumn::make('f_start_date')
                    ->label('Start Date') // NO5: REMOVED "License"
                    ->date('Y-m-d')
                    ->alignCenter() // NO4: Align Centre
                    ->sortable(),

                TextColumn::make('f_expiry_date')
                    ->label('Expiry Date') // NO5: REMOVED "License"
                    ->date('Y-m-d')
                    ->alignCenter() // NO4: Align Centre
                    ->sortable(),

                TextColumn::make('f_invoice_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('f_created_time')
                    ->label('Invoice Date') // NO6: Renamed "Created" to "Invoice Date"
                    ->date('Y-m-d') // NO6: Remove the timing
                    ->alignCenter() // NO6: Align Centre
                    ->sortable(),

                TextColumn::make('payer')
                    ->label('Payer')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)) // NO7: ALL UPPERCASE
                    ->searchable()
                    ->sortable()
                    ->wrap(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('f_expiry_date', 'asc');
    }
}
