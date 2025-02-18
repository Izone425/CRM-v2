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

class PrTodaySalespersonTable extends Component implements HasForms, HasTable
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

    public function getTodayProspects()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser');

        $salespersonId = auth()->user()->role_id == 3 && $this->selectedUser ? $this->selectedUser : auth()->id();

        return Lead::query()
            ->where('salesperson', $salespersonId) // Filter by salesperson
            ->whereDate('follow_up_date', today());
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('1s')
            ->query($this->getTodayProspects())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Active (25 Above) - ' . $this->getActiveBigCompanyLeads()->count() . ' Records') // Display count
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
                TextColumn::make('activityLogs.description')
                    ->label('Latest Activity')
                    ->limit(30)
                    ->wrap()
                    ->formatStateUsing(fn ($record) => $record->activityLogs->sortByDesc('created_at')->first()?->description ?? 'No activity'),
                TextColumn::make('remark')
                    ->label('Remark'),
            ])
            ->actions([
                ActionGroup::make([
                    // LeadActions::getAddDemoAction(),
                    // LeadActions::getAddRFQ(),
                    // LeadActions::getAddFollowUp(),
                    // LeadActions::getAddAutomation(),
                    // LeadActions::getArchiveAction(),
                    LeadActions::getDemoViewAction(),
                    // LeadActions::getTransferCallAttempt(),
                ])
                ->button()
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson_dashboard.pr-today-salesperson-table');
    }
}
