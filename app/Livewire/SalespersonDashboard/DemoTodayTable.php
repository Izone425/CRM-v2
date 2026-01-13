<?php

namespace App\Livewire\SalespersonDashboard;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\Appointment;
use App\Models\Lead;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Livewire\Attributes\On;

class DemoTodayTable extends Component implements HasForms, HasTable
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

    public function getTodayDemos()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = Appointment::whereDate('date', today())
            ->selectRaw('appointments.*, leads.created_at as lead_created_at, DATEDIFF(NOW(), leads.created_at) as pending_days')
            ->join('leads', 'appointments.lead_id', '=', 'leads.id')
            ->where('appointments.status', 'New');

        // Filter based on selectedUser
        if ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereIn('leads.salesperson', $salespersonIds);
        } elseif (is_numeric($this->selectedUser)) {
            $query->where('leads.salesperson', $this->selectedUser);
        } else {
            $query->where('leads.salesperson', auth()->id());
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getTodayDemos())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Active (25 Above) - ' . $this->getActiveBigCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 2)->pluck('name', 'id')->toArray())
                    ->placeholder('Select Salesperson')
                    ->hidden(fn () => auth()->user()->role_id !== 3),
            ])
            ->columns([
                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),
                TextColumn::make('lead.company_size_label')  // Changed from lead.company_size to use the accessor
                    ->label('Company Size')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("
                            CASE
                                WHEN leads.company_size = '1-24' THEN 1
                                WHEN leads.company_size = '25-99' THEN 2
                                WHEN leads.company_size = '100-500' THEN 3
                                WHEN leads.company_size = '501 and Above' THEN 4
                                ELSE 5
                            END $direction
                        ");
                    })
                    ->formatStateUsing(function ($state) {
                        return $state ?? 'N/A';
                    }),
                TextColumn::make('appointment_type')
                    ->label('Appointment Type')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("
                            CASE
                                WHEN company_size = '1-24' THEN 1
                                WHEN company_size = '25-99' THEN 2
                                WHEN company_size = '100-500' THEN 3
                                WHEN company_size = '501 and Above' THEN 4
                                ELSE 5
                            END $direction
                        ");
                    })
                    ->formatStateUsing(function ($state) {
                        if (strtolower($state) === 'onsite') {
                            return '<span style="color: red; font-weight: bold;">' . e($state) . '</span>';
                        }
                        return $state ?? 'N/A';
                    })
                    ->html(),
                TextColumn::make('type')
                    ->label('Demo Type')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("
                            CASE
                                WHEN company_size = '1-24' THEN 1
                                WHEN company_size = '25-99' THEN 2
                                WHEN company_size = '100-500' THEN 3
                                WHEN company_size = '501 and Above' THEN 4
                                ELSE 5
                            END $direction
                        ");
                    }),
                TextColumn::make('start_time')
                    ->label('Time')
                    ->sortable()
                    ->formatStateUsing(fn ($record) =>
                        Carbon::parse($record->start_time)->format('h:i A') .
                        ' - ' .
                        Carbon::parse($record->end_time)->format('h:i A')
                ),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getLeadDetailActionInDemo(),
                    LeadActions::getWhatsappAction(),
                    LeadActions::getRescheduleDemoAction(),
                    LeadActions::getCancelDemoAction()
                ])
                ->button()
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson_dashboard.demo-today-table');
    }
}
