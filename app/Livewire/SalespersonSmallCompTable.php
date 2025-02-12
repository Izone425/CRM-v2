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

class SalespersonSmallCompTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getActiveSmallCompanyLeadsWithSalesperson()
    {
        return Lead::query()
            ->whereNotNull('salesperson') // Ensure salesperson is NOT NULL
            ->where('company_size', '=', '1-24') // Only small companies (1-24)
            ->where('categories', '!=', 'Inactive') // Exclude Inactive leads
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time') // Calculate Pending Time
            // ->when($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === 'pending_time', function ($query) {
            //     return $query->orderBy('pending_time', $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson);
            // })
            // ->when($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === 'salesperson', function ($query) {
            //     return $query->orderBy('salesperson', $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson);
            // })
            // ->when($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === 'company_size', function ($query) {
            //     return $query->orderByRaw("
            //         CASE
            //             WHEN company_size = '1-24' THEN 1
            //             WHEN company_size = '25-99' THEN 2
            //             WHEN company_size = '100-500' THEN 3
            //             WHEN company_size = '501 and above' THEN 4
            //             ELSE 5
            //         END " . $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson);
            // })
            // ->when($this->sortColumnActiveSmallCompanyLeadsWithSalesperson === 'stage', function ($query) {
            //     return $query->orderBy('stage', $this->sortDirectionActiveSmallCompanyLeadsWithSalesperson);
            // });
            ->orderBy('created_at', 'desc'); // Default sorting by latest created leads
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query($this->getActiveSmallCompanyLeadsWithSalesperson())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->heading(fn () => 'SalesPerson (1-24) - ' . $this->getActiveSmallCompanyLeadsWithSalesperson()->count() . ' Records') // Display count
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
                TextColumn::make('stage')
                    ->label('Company Stage'),
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
                ->color('warning'),
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson-small-comp-table');
    }
}
