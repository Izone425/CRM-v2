<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
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
use Livewire\Attributes\On;

class ProspectReminderOverdueTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    public $selectedUser; // Selected user's ID

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]); // Store selected user
        } else {
            // Reset to "Your Own Dashboard" (value = 7)
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable(); // Refresh the table
    }

    public function getProspectOverdueQuery()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser');

        $leadOwner = auth()->user()->role_id == 3 && $this->selectedUser
            ? User::find($this->selectedUser)->name
            : auth()->user()->name;

        return Lead::query()
            ->whereDate('follow_up_date', '<', today()) // Overdue follow-ups
            ->where('lead_owner', $leadOwner) // Filter by authenticated user's name
            ->whereNull('salesperson') // Ensure salesperson is NULL
            ->selectRaw('*, DATEDIFF(NOW(), follow_up_date) as pending_days'); // Default sorting by follow-up date
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query($this->getProspectOverdueQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Prospect Reminder (Overdue) - ' . $this->getProspectOverdueQuery()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) =>
                        '<a href="' . url('admin/leads/' . \App\Classes\Encryptor::encrypt($record->id)) . '"
                            target="_blank"
                            class="inline-block"
                            style="color:#338cf0;">
                            ' . strtoupper(Str::limit($state ?? 'N/A', 10, '...')) . '
                        </a>'
                    )
                    ->html(),
                TextColumn::make('company_size_label')
                    ->label('Company Size')
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
                // TextColumn::make('created_at')
                //     ->label('Created Time')
                //     ->sortable()
                //     ->dateTime('d M Y, h:i A')
                //     ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A')),
                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->follow_up_date->diffInDays(now()) . ' days')
                    ->color(fn ($record) => $record->follow_up_date->diffInDays(now()) == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getAddDemoAction(),
                    LeadActions::getAddRFQ(),
                    LeadActions::getAddFollowUp(),
                    LeadActions::getAddAutomation(),
                    LeadActions::getArchiveAction(),
                    LeadActions::getViewAction(),
                    LeadActions::getViewRemark(),
                ])
                ->button()
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.prospect-reminder-overdue-table');
    }
}
