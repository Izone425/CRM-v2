<?php

namespace App\Livewire;

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
use Livewire\Attributes\On;

class DemoTodayTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getTodayDemos()
    {
        // Ensure selectedUser is fetched from session if not set
        $this->selectedUser = $this->selectedUser ?? session('selectedUser');

        $salespersonId = ($this->selectedUser && auth()->user()->role_id == 3) ? $this->selectedUser : auth()->id();

        return Appointment::whereDate('date', today()) // Filter by today's date in Appointment
        ->selectRaw('appointments.*, leads.created_at as lead_created_at, DATEDIFF(NOW(), leads.created_at) as pending_days')
        ->join('leads', 'appointments.lead_id', '=', 'leads.id') // Assuming there is a 'lead_id' in appointments
        ->where('leads.salesperson', $salespersonId)
        ->where('status', 'New');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getTodayDemos())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Active (25 Above) - ' . $this->getActiveBigCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 10, '...'));
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
                    LeadActions::getRescheduleDemoAction()
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
