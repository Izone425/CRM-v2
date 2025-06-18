<?php

namespace App\Livewire;

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
use Livewire\Attributes\On;

class FollowUpLead extends Component implements HasForms, HasTable
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

    public function getFollowUpLead()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser');

        $salespersonId = auth()->user()->role_id == 3 && $this->selectedUser ? $this->selectedUser : auth()->id();

        return Lead::query()
            ->where('salesperson', $salespersonId)
            ->where('stage', 'Follow Up')
            ->where('manual_follow_up_count', '>=', 12)
            ->whereRaw('DATEDIFF(NOW(), created_at) > 97')
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getFollowUpLead())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Active (25 Above) - ' . $this->getActiveBigCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 10, '...'));
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
                TextColumn::make('lead_status')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->default('0')
                    ->formatStateUsing(fn ($state) => $state . ' ' . ($state == 0 ? 'Day' : 'Days'))
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getNoResponseAction(),
                    LeadActions::getLeadDetailAction(),
                    LeadActions::getViewAction(),
                    LeadActions::getViewRemark(),
                ])
                ->button()
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.follow-up-lead');
    }
}
