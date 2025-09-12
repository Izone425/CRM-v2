<?php
namespace App\Livewire\SalespersonDashboard;

use App\Models\DebtorAging;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;

class UnpaidDebtorsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-salesperson-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    protected $salespersonMapping = [
        6 => 'MUIM',
        7 => 'YASMIN',
        8 => 'FARHANAH',
        9 => 'JOSHUA',
        10 => 'AZIZ',
        11 => 'BARI',
        12 => 'VINCE',
        5 => 'Salesperson',
    ];

    protected $salespeople = [
        'MUIM',
        'YASMIN',
        'FARHANAH',
        'JOSHUA',
        'AZIZ',
        'BARI',
        'VINCE',
        'Salesperson'
    ];

    public function render()
    {
        return view('livewire.salesperson_dashboard.unpaid-debtors-table');
    }

    public function getDebtorCount()
    {
        return $this->getBaseQuery()->count();
    }

    public function getInvoiceCount()
    {
        return $this->getBaseQuery()->count();
    }

    public function getTotalAmount()
    {
        return $this->getBaseQuery()->sum(DB::raw('
            CASE
                WHEN currency_code = "MYR" THEN outstanding
                WHEN outstanding IS NOT NULL AND exchange_rate IS NOT NULL THEN outstanding * exchange_rate
                ELSE 0
            END
        '));
    }

    protected function getBaseQuery()
    {
        $query = DebtorAging::query();

        // Filter for unpaid debtors
        $query->whereRaw('outstanding = invoice_amount')
            ->where('outstanding', '>', 0);

        // Always filter to only show records from your salespeople list
        $query->whereIn('salesperson', $this->salespeople);

        // Get selected user from session or current selection
        $selectedUser = $this->selectedUser ?? session('selectedUser') ?? 'current-user';

        // Further filter based on user selection
        if ($selectedUser === 'all-salespersons') {
            // Already filtered to all salespeople above, so no additional filter needed
        } else {
            // Check if selected user is numeric (an ID)
            if (is_numeric($selectedUser)) {
                // Map the user ID to salesperson name using our mapping array
                $salespersonName = $this->salespersonMapping[$selectedUser] ?? null;

                if ($salespersonName) {
                    $query->where('salesperson', $salespersonName);
                }
            } else if (auth()->user()->role_id === 2) {
                // For salesperson role, get their mapped name based on ID
                $userId = auth()->id();
                $salespersonName = $this->salespersonMapping[$userId] ?? auth()->user()->name;
                $query->where('salesperson', $salespersonName);
            } else {
                // For admin/managers, allow selection of specific salesperson by name
                $query->where('salesperson', $selectedUser);
            }
        }

        return $query;
    }

    protected function determinePaymentStatus($record)
    {
        // For this table, all records are unpaid
        return 'UnPaid';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getBaseQuery())
            ->defaultSort('invoice_date', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->searchable()
                    ->sortable()
                    ->visible(auth()->user()->role_id !== 2), // Hide for salesperson role

                TextColumn::make('invoice_type')
                    ->label('Type')
                    ->getStateUsing(function (DebtorAging $record): string {
                        if (strpos($record->invoice_number, 'EPIN') === 0) {
                            return 'Product';
                        } elseif (strpos($record->invoice_number, 'EHIN') === 0) {
                            return 'HRDF';
                        } else {
                            return 'Other';
                        }
                    }),

                TextColumn::make('outstanding_rm')
                    ->label('Outstanding (RM)')
                    ->getStateUsing(function (DebtorAging $record): float {
                        return $record->currency_code === 'MYR'
                            ? $record->outstanding
                            : ($record->outstanding * $record->exchange_rate);
                    })
                    ->money('MYR')
                    ->alignRight(),
            ])
            ->filters([
                SelectFilter::make('invoice_type')
                    ->options([
                        'hrdf' => 'HRDF',
                        'product' => 'Product',
                    ])
                    ->label('Invoice Type')
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'hrdf') {
                            return $query->where('invoice_number', 'like', 'EHIN%');
                        } elseif ($data['value'] === 'product') {
                            return $query->where('invoice_number', 'like', 'EPIN%');
                        }
                    }),

                Filter::make('invoice_date')
                    ->form([
                        DatePicker::make('invoice_date_from')
                            ->label('From')
                            ->placeholder('From'),
                        DatePicker::make('invoice_date_until')
                            ->label('Until')
                            ->placeholder('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['invoice_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '>=', $date),
                            )
                            ->when(
                                $data['invoice_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '<=', $date),
                            );
                    }),
            ]);
    }
}
