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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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
        $now = now();

        $this->selectedMonth ??= $now->month;
        $this->selectedYear ??= $now->year;

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
                ->modalHeading('Set Sales Target for Salespersons')
                ->form(function () {
                    $salespeople = User::where('role_id', 2)->get();
                    $components = [];

                    foreach ($salespeople as $salesperson) {
                        // Get the latest target record
                        $latestTarget = \App\Models\SalesTarget::where('salesperson', $salesperson->id)
                            ->orderByDesc('year')
                            ->orderByDesc('month')
                            ->first();

                        $latestAmount = optional($latestTarget)->target_amount;
                        $latestMonth = optional($latestTarget)->month;
                        $latestYear = optional($latestTarget)->year;

                        $components[] = TextInput::make("targets.{$salesperson->id}")
                            ->label($salesperson->name)
                            ->numeric()
                            ->placeholder($latestAmount
                                ? 'Latest: RM ' . number_format($latestAmount, 2) . " ({$latestMonth}/{$latestYear})"
                                : 'No previous target set');
                    }

                    return $components;
                })
                ->action(function ($data) {
                    $now = now();

                    foreach ($data['targets'] as $salespersonId => $amount) {
                        if (is_null($amount) || $amount === '') {
                            continue; // Skip empty entries
                        }

                        \App\Models\SalesTarget::updateOrCreate(
                            [
                                'salesperson' => $salespersonId,
                                'year' => $now->year,
                                'month' => $now->month,
                            ],
                            [
                                'target_amount' => $amount,
                            ]
                        );
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Sales targets updated successfully!')
                        ->success()
                        ->send();
                })
                ->modalSubmitActionLabel('Save Targets')
            ])
            ->filters([
                Filter::make('selectedMonth')
                ->form([
                    TextInput::make('selectedMonth')
                        ->type('month')
                        ->label('Month')
                        ->default(Carbon::now()->format('Y-m'))
                        ->reactive()
                        ->afterStateUpdated(function ($state, $livewire) {
                            $parsed = Carbon::parse($state);
                            $livewire->selectedMonth = $parsed->month;
                            $livewire->selectedYear = $parsed->year;
                        }),
                ]),
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
                    ->getStateUsing(function ($record) {
                        $now = now(); // fallback
                        $month = $this->selectedMonth ?? $now->month;
                        $year = $this->selectedYear ?? $now->year;

                        $target = \App\Models\SalesTarget::where('salesperson', $record->id)
                            ->where('month', $month)
                            ->where('year', $year)
                            ->value('target_amount') ?? 0;

                        return 'RM ' . number_format($target, 2);
                    }),


                TextColumn::make('difference')
                    ->label('DIFFERENCE')
                    ->getStateUsing(function ($record) {
                        $month = $this->selectedMonth ?? now()->month;
                        $year = $this->selectedYear ?? now()->year;

                        $invoiceTotal = $this->getInvoiceTotal($record, $month, $year);
                        $proformaTotal = $this->getProformaTotal($record, $month, $year);
                        $forecastHot = $this->getForecastHot($record, $month, $year);
                        $target = $this->getSalesTarget($record, $month, $year);

                        $difference = ($invoiceTotal + $proformaTotal + $forecastHot) - $target;

                        return 'RM ' . number_format($difference, 2);
                    })
                    ->color(function ($record) {
                        $month = $this->selectedMonth ?? now()->month;
                        $year = $this->selectedYear ?? now()->year;

                        $invoiceTotal = $this->getInvoiceTotal($record, $month, $year);
                        $proformaTotal = $this->getProformaTotal($record, $month, $year);
                        $forecastHot = $this->getForecastHot($record, $month, $year);
                        $target = $this->getSalesTarget($record, $month, $year);

                        return ($invoiceTotal + $proformaTotal + $forecastHot) >= $target
                            ? 'success'
                            : 'danger';
                    })


            ]);
    }

    private function getInvoiceTotal($salesperson)
    {
        $total = Invoice::where('salesperson', $salesperson->id)
            ->whereYear('invoice_date', $this->selectedYear)
            ->whereMonth('invoice_date', $this->selectedMonth)
            ->sum('amount');

        return $total;
    }

    private function getProformaTotal($salesperson)
    {
        $total = ProformaInvoice::where('salesperson', $salesperson->id)
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->sum('amount');

        return $total;
    }

    private function getForecastHot($salesperson)
    {
        $total = Lead::where('salesperson', $salesperson->id)
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->where('lead_status', 'Hot')
            ->sum('deal_amount');

        return $total;
    }

    private function getSalesTarget($record, $month, $year)
    {
        return \App\Models\SalesTarget::where('salesperson', $record->id)
            ->where('month', $month)
            ->where('year', $year)
            ->value('target_amount') ?? 0;
    }


    public function render()
    {
        return view('livewire.sales-forecast-summary-table');
    }
}
