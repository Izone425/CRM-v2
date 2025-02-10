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

class PendingLeadTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getNewLeadsQuery()
    {
        $leadOwner = auth()->user()->role_id == 3 && $this->selectedUser
            ? User::find($this->selectedUser)->name
            : auth()->user()->name;

        return Lead::query()
            ->where('stage', 'Transfer')
            ->where('lead_owner', $leadOwner)
            ->where('lead_status', 'New')
            ->selectRaw('*, DATEDIFF(updated_at, created_at) as pending_days') // Calculate pending days
            // ->when($this->sortColumnPendingTasks === 'company_size', function ($query) {
            //     return $query->orderByRaw("
            //         CASE
            //             WHEN company_size = '1-24' THEN 1
            //             WHEN company_size = '25-99' THEN 2
            //             WHEN company_size = '100-500' THEN 3
            //             WHEN company_size = '501 and above' THEN 4
            //             ELSE 5
            //         END " . $this->sortDirectionPendingTasks);
            // })
            // ->when($this->sortColumnPendingTasks === 'created_at', function ($query) {
            //     return $query->orderBy('created_at', $this->sortDirectionPendingTasks);
            // })
            // ->when($this->sortColumnPendingTasks === 'pending_days', function ($query) {
            //     return $query->orderBy('pending_days', $this->sortDirectionPendingTasks);
            // })
            ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query($this->getNewLeadsQuery())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->heading(fn () => 'My Pending Tasks - ' . $this->getNewLeadsQuery()->count() . ' Records') // Display count
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
                ])
                ->button()
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.pending-lead-table');
    }
}
