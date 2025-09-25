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

    protected function getProductGroupMapping(): array
    {
        return [
            // TimeTec HR Group
            'timetec_hr' => [
                'TimeTec TA (1 User License)',
                'TimeTec TA (10 User License)',
                'TimeTec Leave (1 User License)',
                'TimeTec Leave (10 User License)',
                'TimeTec Claim (1 User License)',
                'TimeTec Claim (10 User License)',
                'TimeTec Payroll (1 Payroll License)',
                'TimeTec Payroll (10 Payroll License)',
            ],
            // Non-TimeTec HR Group
            'non_timetec_hr' => [
                'Face & QR Code (1 Device License)',
                'FCC Terminal License',
                'TimeTec Access (1 Door License)',
                'TimeTec Hire Business (Unlimited Job Posts)',
                'TimeTec Hire Startup (10 Job Posts)',
            ],
            // Other Division Group
            'other_division' => [
                'TimeTec VMS Corporate (1 Floor License)',
                'TimeTec VMS SME (1 Location License)',
                'TimeTec Patrol (1 Checkpoint License)',
                'TimeTec Patrol (10 Checkpoint License)',
                'Other',
                'TimeTec Profile (10 User License)',
            ],
        ];
    }

    // ADD: Method to determine product group
    protected function getProductGroup(string $productName): ?string
    {
        $mapping = $this->getProductGroupMapping();

        foreach ($mapping as $group => $products) {
            foreach ($products as $product) {
                if (stripos($productName, $product) !== false || $productName === $product) {
                    return $group;
                }
            }
        }

        return 'other_division'; // Default to Other Division for unmapped products
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Removed default date filtering - now shows all records
                return LicenseData::query();
            })
            ->filters([
                SelectFilter::make('product_group')
                    ->label('Product Group')
                    ->options([
                        'timetec_hr' => 'TimeTec HR',
                        'non_timetec_hr' => 'Non-TimeTec HR',
                        'other_division' => 'Other Division',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $mapping = $this->getProductGroupMapping();
                            $selectedProducts = $mapping[$data['value']] ?? [];

                            if (!empty($selectedProducts)) {
                                $query->where(function ($q) use ($selectedProducts) {
                                    foreach ($selectedProducts as $product) {
                                        $q->orWhere('f_name', 'LIKE', '%' . $product . '%');
                                    }
                                });
                            }
                        }
                    })
                    ->indicator('Product Group'),

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

                                $query->whereBetween('f_expiry_date', [$startDate, $endDate]);

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

                SelectFilter::make('f_currency')
                    ->label('Currency')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        return LicenseData::query()
                            ->distinct()
                            ->orderBy('f_currency')
                            ->whereNotNull('f_currency')
                            ->where('f_currency', '!=', '')
                            ->pluck('f_currency', 'f_currency')
                            ->toArray();
                    })
                    ->indicator('Currency'),
            ])
            ->filtersFormColumns(3)
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
                    ->label('PI Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('f_created_time')
                    ->label('PI date') // NO6: Renamed "Created" to "Invoice Date"
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
