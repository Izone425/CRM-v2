<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;

class NewLeadTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser; // Allow dynamic filtering

    public function getPendingLeadsQuery()
    {
        return Lead::query()
            ->where('categories', 'New') // Filter only new leads
            // ->when($this->sortColumnNewLeads === 'companyDetail.company_name', function ($query) {
            //     return $query->leftJoin('company_details', 'leads.company_id', '=', 'company_details.id')
            //         ->orderBy('company_details.company_name', $this->sortDirectionNewLeads);
            // })
            // ->when($this->sortColumnNewLeads === 'company_size', function ($query) {
            //     return $query->orderByRaw("
            //         CASE
            //             WHEN company_size = '1-24' THEN 1
            //             WHEN company_size = '25-99' THEN 2
            //             WHEN company_size = '100-500' THEN 3
            //             WHEN company_size = '501 and above' THEN 4
            //             ELSE 5
            //         END " . $this->sortDirectionNewLeads);
            // })
            // ->when(!in_array($this->sortColumnNewLeads, ['companyDetail.company_name', 'company_size']), function ($query) {
            //     return $query->orderBy($this->sortColumnNewLeads, $this->sortDirectionNewLeads);
            // })
            ->orderBy('created_at', 'desc'); // Default sorting by latest created leads
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->heading('New Leads')
            ->heading(fn () => 'New Leads - ' . $this->getPendingLeadsQuery()->count() . ' Records') // Display count
            ->query($this->getPendingLeadsQuery()) // Use the new query method
            ->emptyState(fn () => view('components.empty-state-question'))
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
                TextColumn::make('details')->label('Details'),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getViewAction(),
                    LeadActions::getAssignToMeAction(),
                ])
                ->button()
                ->color('warning'),
            ]);
    }

    public function render()
    {
        return view('livewire.new-lead-table');
    }
}
