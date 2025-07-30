<?php

namespace App\Livewire\SalespersonDashboard;

use App\Classes\Encryptor;
use App\Enums\LeadStatusEnum;
use App\Filament\Actions\LeadActions;
use App\Models\ActivityLog;
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

class PROverdueSalespersonTable extends Component implements HasForms, HasTable
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

    public function getOverdueProspects()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = Lead::query()
            ->where('categories', '!=', 'Inactive')
            ->where('lead_status', '!=', 'Demo-Assigned')
            ->whereDate('follow_up_date', '<', today())
            ->where('follow_up_counter', true)
            ->whereNotIn('salesperson', [18, 21, 25])
            ->selectRaw('*, DATEDIFF(NOW(), follow_up_date) as pending_days');

        // Apply salesperson filter
        if ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereIn('salesperson', $salespersonIds);
        } elseif (is_numeric($this->selectedUser)) {
            $query->where('salesperson', $this->selectedUser);
        } else {
            $query->where('salesperson', auth()->id());
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getOverdueProspects())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Active (25 Above) - ' . $this->getActiveBigCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5, 25])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 2)->pluck('name', 'id')->toArray())
                    ->placeholder('Select Salesperson')
                    ->hidden(fn () => auth()->user()->role_id !== 3),
                SelectFilter::make('lead_status')
                    ->label('Lead Status')
                    ->multiple()
                    ->options([
                        'Pending Demo' => 'Pending Demo',
                        'RFQ-Follow Up' => 'RFQ-Follow Up',
                        'Demo Cancelled' => 'Demo Cancelled',
                        'Hot' => 'Hot',
                        'Warm' => 'Warm',
                        'Cold' => 'Cold',
                    ]),
            ])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),
                TextColumn::make('company_size_label')  // Changed from lead.company_size to use the accessor
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
                TextColumn::make('lead_status')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $this->getWeekdayCount($record->follow_up_date, now()) . ' days')
                    ->color(fn ($record) => $this->getWeekdayCount($record->follow_up_date, now()) == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getAddFollowUp(),
                    LeadActions::getLeadDetailAction(),
                    LeadActions::getViewAction(),
                    LeadActions::getViewRemark(),
                ])
                ->button()
                ->color('warning'),
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson_dashboard.pr-overdue-salesperson-table');
    }

    private function getWeekdayCount($startDate, $endDate)
    {
        $weekdayCount = 0;
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        while ($currentDate->lte($endDate)) {
            if (!$currentDate->isWeekend()) {
                $weekdayCount++;
            }
            $currentDate->addDay();
        }

        return $weekdayCount;
    }
}
