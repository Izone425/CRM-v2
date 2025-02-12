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
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ActiveBigCompTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getActiveBigCompanyLeads()
    {
        return Lead::query()
            ->where('company_size', '!=', '1-24') // Exclude small companies
            ->whereNull('salesperson') // Salesperson must be NULL
            ->whereNotNull('lead_owner')
            ->where('categories', '!=', 'Inactive') // Exclude Inactive leads
            ->where(function ($query) {
                $query->whereNull('done_call') // Include NULL values
                    ->orWhere('done_call', 0); // Include 0 values
            })
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time') // Calculate Pending Days
            // ->when($this->sortColumnActiveBigCompanyLeads === 'pending_time', function ($query) {
            //     return $query->orderBy('pending_time', $this->sortDirectionActiveBigCompanyLeads);
            // })
            // ->when($this->sortColumnActiveBigCompanyLeads === 'company_size', function ($query) {
            //     return $query->orderByRaw("
            //         CASE
            //             WHEN company_size = '1-24' THEN 1
            //             WHEN company_size = '25-99' THEN 2
            //             WHEN company_size = '100-500' THEN 3
            //             WHEN company_size = '501 and above' THEN 4
            //             ELSE 5
            //         END " . $this->sortDirectionActiveBigCompanyLeads);
            // })
            // ->when($this->sortColumnActiveBigCompanyLeads === 'call_attempts', function ($query) {
            //     return $query->orderBy('call_attempts', $this->sortDirectionActiveBigCompanyLeads);
            // })
            ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query($this->getActiveBigCompanyLeads())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->heading(fn () => 'Active (25 Above) - ' . $this->getActiveBigCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
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
                    ->label('Company Size'),
                TextColumn::make('call_attempt')
                    ->label('Call Attempt'),
                TextColumn::make('pending_time')
                    ->label('Pending Days')
                    ->formatStateUsing(fn ($record) => $record->created_at->diffInDays(now()) . ' days')
                    ->color(fn ($record) => $record->created_at->diffInDays(now()) == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getAddDemoAction(),
                    LeadActions::getAddRFQ(),
                    LeadActions::getAddFollowUp(),
                    LeadActions::getAddAutomation(),
                    LeadActions::getArchiveAction(),
                    LeadActions::getViewAction(),
                    LeadActions::getTransferCallAttempt(),
                ])
                ->button()
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.active-big-comp-table');
    }
}
