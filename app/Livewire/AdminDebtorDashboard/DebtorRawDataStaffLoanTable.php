<?php

namespace App\Livewire\AdminDebtorDashboard;

use App\Models\DebtorAging;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Illuminate\Support\Number;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class DebtorRawDataStaffLoanTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

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

    #[On('refresh-adminrepair-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DebtorAging::query()
                ->where('outstanding', '!=', 0)
                ->where('debtor_code', 'like', '%STL%')
            )
            ->columns([
                TextColumn::make('debtor_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('support')
                    ->label('Support')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('balance_in_rm')
                    ->label('Outstanding (RM)')
                    ->numeric(2)
                    ->money('MYR')
                    ->state(function ($record) {
                        // Calculate the balance in RM by multiplying outstanding by exchange_rate
                        if ($record->currency_code === 'MYR') {
                            // If already in MYR, return as is
                            return $record->outstanding;
                        }

                        // Apply exchange rate conversion
                        if ($record->outstanding && $record->exchange_rate) {
                            return $record->outstanding * $record->exchange_rate;
                        }

                        return 0;
                    })
                    ->sortable(),
            ])
            ->filters([
                // Filter by Year
                Filter::make('invoice_year')
                    ->form([
                        Select::make('year')
                            ->label('Invoice Year')
                            ->options(function() {
                                // Get all years from invoice_date, from current year back to 5 years
                                $currentYear = (int)date('Y');
                                $years = [];
                                for ($i = $currentYear; $i >= $currentYear - 2; $i--) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['year']) && $data['year']) {
                            return $query->whereYear('invoice_date', $data['year']);
                        }
                        return $query;
                    }),

                // Filter by Month
                Filter::make('invoice_month')
                    ->form([
                        Select::make('month')
                            ->label('Invoice Month')
                            ->options([
                                '1' => 'January',
                                '2' => 'February',
                                '3' => 'March',
                                '4' => 'April',
                                '5' => 'May',
                                '6' => 'June',
                                '7' => 'July',
                                '8' => 'August',
                                '9' => 'September',
                                '10' => 'October',
                                '11' => 'November',
                                '12' => 'December',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['month']) && $data['month']) {
                            return $query->whereMonth('invoice_date', $data['month']);
                        }
                        return $query;
                    }),

                // Filter by Salesperson
                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->searchable()
                    ->multiple()
                    ->preload(),

                // Filter by Amount Range
                Filter::make('amount_range')
                    ->form([
                        Select::make('amount_filter_type')
                            ->label('Amount Filter Type')
                            ->options([
                                'above' => 'Above Amount',
                                'below' => 'Below Amount',
                                'between' => 'Between Amounts',
                            ])
                            ->reactive(),

                        TextInput::make('min_amount')
                            ->label(function (callable $get) {
                                return $get('amount_filter_type') === 'between' ? 'Minimum Amount' : 'Amount';
                            })
                            ->numeric()
                            ->visible(function (callable $get) {
                                return in_array($get('amount_filter_type'), ['above', 'below', 'between']);
                            }),

                        TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->visible(function (callable $get) {
                                return $get('amount_filter_type') === 'between';
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['amount_filter_type'])) {
                            if ($data['amount_filter_type'] === 'above' && isset($data['min_amount'])) {
                                return $query->where('outstanding', '>=', $data['min_amount']);
                            }

                            if ($data['amount_filter_type'] === 'below' && isset($data['min_amount'])) {
                                return $query->where('outstanding', '<=', $data['min_amount']);
                            }

                            if ($data['amount_filter_type'] === 'between' &&
                                isset($data['min_amount']) && isset($data['max_amount'])) {
                                return $query->whereBetween('outstanding', [$data['min_amount'], $data['max_amount']]);
                            }
                        }
                        return $query;
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5]);
    }

    public function render()
    {
        return view('livewire.admin-debtor-dashboard.debtor-raw-data-staff-loan-table');
    }
}
