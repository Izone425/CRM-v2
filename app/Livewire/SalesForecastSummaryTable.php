<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\Lead;
use App\Models\SalesTarget;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Livewire\Component;
use Carbon\Carbon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class SalesForecastSummaryTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable, InteractsWithForms;

    public $selectedYear;
    public $selectedMonth;
    public $salesSummary = [];

    public function mount()
    {
        $this->selectedYear = date('Y');
        $this->selectedMonth = date('m');

        info("Sales Forecast Table Loaded for Year: $this->selectedYear, Month: $this->selectedMonth");

        $this->loadSalesSummary();
    }

    public function loadSalesSummary()
    {
        $salespeople = $this->getTableQuery()->get();

        $this->salesSummary = $salespeople->map(function ($salesperson) {
            return [
                'id' => $salesperson->id,
                'salesperson' => $salesperson->name,
                'invoice' => $this->getInvoiceTotal($salesperson),
                'proforma_inv' => $this->getProformaTotal($salesperson),
                'inv_pi' => $this->getInvoiceTotal($salesperson) + $this->getProformaTotal($salesperson),
                'forecast_hot' => $this->getForecastHot($salesperson),
                'grand_total' => $this->getInvoiceTotal($salesperson) + $this->getProformaTotal($salesperson) + $this->getForecastHot($salesperson),
                'sales_target' => $salesperson->sales_target,
                'difference' => ($this->getInvoiceTotal($salesperson) + $this->getProformaTotal($salesperson) + $this->getForecastHot($salesperson)) - $salesperson->sales_target,
            ];
        })->toArray();
    }

    protected function getTableQuery()
    {
        return User::where('role_id', 2); // Fetch only salespersons
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('name')
            ->heading('Sales Forecast Summary')
            ->headerActions([
                \Filament\Tables\Actions\Action::make('setSalesTarget')
                    ->label('Set Sales Target')
                    ->form([
                        Select::make('salesperson_id')
                            ->label('Salesperson')
                            ->options(User::where('role_id', 2)->pluck('name', 'id'))
                            ->required(),

                        DatePicker::make('month')
                            ->label('Month')
                            ->format('Y-m')
                            ->required(),

                        TextInput::make('target_amount')
                            ->label('Sales Target Amount')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function ($data) {
                        SalesTarget::updateOrCreate(
                            [
                                'salesperson' => $data['salesperson_id'],
                                'year' => Carbon::parse($data['month'])->year,
                                'month' => Carbon::parse($data['month'])->month,
                            ],
                            [
                                'target_amount' => $data['target_amount'],
                            ]
                        );
                    })
            ])
            ->filters([
                Filter::make('selectedYear')
                    ->form([
                        Select::make('selectedYear')
                            ->label('Year')
                            ->options(range(date('Y'), date('Y') - 5))
                            ->default($this->selectedYear)
                            ->reactive()
                            ->afterStateUpdated(fn ($state) => $this->selectedYear = $state),
                    ]),

                Filter::make('selectedMonth')
                    ->form([
                        DatePicker::make('selectedMonth')
                            ->label('Month')
                            ->displayFormat('F Y')
                            ->default(Carbon::now()->format('Y-m'))
                            ->reactive()
                            ->afterStateUpdated(fn ($state) => $this->selectedMonth = Carbon::parse($state)->month),
                    ]),
                Filter::make('created_at')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            // Parse the date range from the "start - end" format
                            [$start, $end] = explode(' - ', $data['date_range']);

                            // Ensure valid dates
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                            // Apply the filter
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['date_range'])) {
                            // Parse the date range for display
                            [$start, $end] = explode(' - ', $data['date_range']);

                            return 'From: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                ' To: ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }
                        return null;
                    }),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                TextColumn::make('name')->label('SALESPERSON')->sortable()->searchable(),

                TextColumn::make('invoice')
                    ->label('INVOICE')
                    ->getStateUsing(fn ($record) => 'RM ' . number_format($this->getInvoiceTotal($record), 2)),

                TextColumn::make('proforma_inv')
                    ->label('PERFORMA INV')
                    ->getStateUsing(fn ($record) => 'RM ' . number_format($this->getProformaTotal($record), 2)),

                TextColumn::make('inv_pi')
                    ->label('INV + PI')
                    ->getStateUsing(fn ($record) => 'RM ' . number_format($this->getInvoiceTotal($record) + $this->getProformaTotal($record), 2)),

                TextColumn::make('forecast_hot')
                    ->label('FORECAST - HOT')
                    ->getStateUsing(fn ($record) => 'RM ' . number_format($this->getForecastHot($record), 2)),

                TextColumn::make('grand_total')
                    ->label('GRAND TOTAL')
                    ->getStateUsing(fn ($record) => 'RM ' . number_format(
                        $this->getInvoiceTotal($record) +
                        $this->getProformaTotal($record) +
                        $this->getForecastHot($record), 2
                    )),

                TextColumn::make('sales_target')
                    ->label('SALES TARGET')
                    ->getStateUsing(fn ($record) => 'RM ' . number_format($this->getSalesTarget($record), 2)),

                TextColumn::make('difference')
                    ->label('DIFFERENCE')
                    ->getStateUsing(fn ($record) => 'RM ' . number_format(
                        ($this->getInvoiceTotal($record) +
                        $this->getProformaTotal($record) +
                        $this->getForecastHot($record)) - $this->getSalesTarget($record), 2
                    ))
                    ->color(fn ($record) =>
                        ($this->getInvoiceTotal($record) +
                        $this->getProformaTotal($record) +
                        $this->getForecastHot($record)) >= $this->getSalesTarget($record) ? 'success' : 'danger'
                    ),
            ]);
    }

    private function getInvoiceTotal($salesperson)
    {
        $total = Invoice::where('salesperson', $salesperson->id)
            ->whereYear('invoice_date', $this->selectedYear)
            ->whereMonth('invoice_date', $this->selectedMonth)
            ->sum('amount');

        info("Invoice Total for Salesperson ID {$salesperson->id}: " . $total);
        return $total;
    }

    private function getProformaTotal($salesperson)
    {
        $total = ProformaInvoice::where('salesperson', $salesperson->id)
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->sum('amount');

        info("Proforma Invoice Total for Salesperson ID {$salesperson->id}: " . $total);
        return $total;
    }

    private function getForecastHot($salesperson)
    {
        $total = Lead::where('salesperson', $salesperson->id)
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->where('lead_status', 'Hot')
            ->sum('deal_amount');

        info("Forecast Hot Deals for Salesperson ID {$salesperson->id}: " . $total);
        return $total;
    }

    private function getSalesTarget($salesperson)
    {
        return SalesTarget::where('salesperson', $salesperson->id)
            // ->where('year', $this->selectedYear)
            // ->where('month', $this->selectedMonth)
            ->value('target_amount') ?? 0;
    }

    public function render()
    {
        return view('livewire.sales-forecast-summary-table');
    }
}
