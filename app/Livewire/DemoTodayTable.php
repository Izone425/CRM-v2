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

class DemoTodayTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getTodayDemos()
    {
        $salespersonId = auth()->user()->role_id == 3 && $this->selectedUser ? $this->selectedUser : auth()->id();

        return Appointment::whereDate('date', today()) // Filter by today's date in Appointment
            ->whereHas('lead', function ($query) use ($salespersonId) { // Ensure Lead exists
                $query->where('salesperson', $salespersonId) // Salesperson check from Lead
                    ->where('status', 'new'); // Status check from Lead
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
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
                    ->formatStateUsing(fn ($state, $record) =>
                        '<a href="' . url('admin/leads/' . \App\Classes\Encryptor::encrypt($record->lead->id)) . '"
                            target="_blank"
                            class="inline-block"
                            style="color:#338cf0;">
                            ' . strtoupper(Str::limit($state ?? 'N/A', 10, '...')) . '
                        </a>'
                    )
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
                        Carbon::parse($record->date)->format('d M Y') . ' | ' . // Format date
                        Carbon::parse($record->start_time)->format('h:i A') .
                        ' - ' .
                        Carbon::parse($record->end_time)->format('h:i A')
                ),
                TextColumn::make('pending_time')
                    ->label('Pending Days')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->created_at->diffInDays(now()) . ' days')
                    ->color(fn ($record) => $record->created_at->diffInDays(now()) == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    // LeadActions::getAddDemoAction(),
                    // LeadActions::getAddRFQ(),
                    // LeadActions::getAddFollowUp(),
                    // LeadActions::getAddAutomation(),
                    // LeadActions::getArchiveAction(),
                    // LeadActions::getViewAction(),
                    // LeadActions::getTransferCallAttempt(),
                ])
                ->button()
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.demo-today-table');
    }
}
