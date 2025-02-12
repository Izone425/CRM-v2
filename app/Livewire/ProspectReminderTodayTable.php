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

class ProspectReminderTodayTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getProspectTodayQuery()
    {
        $leadOwner = auth()->user()->role_id == 3 && $this->selectedUser
            ? User::find($this->selectedUser)->name
            : auth()->user()->name;

        return Lead::query()
            ->whereDate('follow_up_date', today())
            ->where('lead_owner', $leadOwner)
            ->whereNull('salesperson')
            // ->when($this->sortColumnProspect === 'company_size', function ($query) {
            //     return $query->orderByRaw("
            //         CASE
            //             WHEN company_size = '1-24' THEN 1
            //             WHEN company_size = '25-99' THEN 2
            //             WHEN company_size = '100-500' THEN 3
            //             WHEN company_size = '501 and above' THEN 4
            //             ELSE 5
            //         END " . $this->sortDirectionProspect);
            // })
            // ->when($this->sortColumnProspect === 'created_at', function ($query) {
            //     return $query->orderBy('created_at', $this->sortDirectionProspect);
            // })
            ->orderBy('follow_up_date', 'asc'); // Default sorting by follow-up date
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query($this->getProspectTodayQuery())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->heading(fn () => 'Prospect Reminder (Today) - ' . $this->getProspectTodayQuery()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
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
                TextColumn::make('created_at')
                    ->label('Created Time')
                    ->dateTime('d M Y, h:i A')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A')),
                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->default('0 Day'),
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
                ->color('warning'),
            ]);
    }

    public function render()
    {
        return view('livewire.pending-lead-table');
    }
}
