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

class CallAttemptSmallCompTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getFollowUpSmallCompanyLeads()
    {
        return Lead::query()
            ->where('done_call', '=', '1')
            ->whereNull('salesperson') // Salesperson is NULL
            ->where('company_size', '=', '1-24') // Only small companies (1-24)
            ->where('categories', '!=', 'Inactive') // Exclude Inactive leads
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getFollowUpSmallCompanyLeads())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Call Attempt (1-24) - ' . $this->getFollowUpSmallCompanyLeads()->count() . ' Records') // Display count
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
                TextColumn::make('created_at')
                    ->label('Created Time')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('j F Y, g:i A')),
                TextColumn::make('call_attempt')
                    ->label('Call Attempt')
                    ->sortable(),
                // TextColumn::make('pending_time')
                //     ->label('Pending Days')
                //     ->sortable()
                //     ->formatStateUsing(fn ($record) => $record->created_at->diffInDays(now()) . ' days')
                //     ->color(fn ($record) => $record->created_at->diffInDays(now()) == 0 ? 'draft' : 'danger'),
            ])
            ->headerActions($this->headerActions())
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
                ->color(fn (Lead $record) => $record->follow_up_needed ? 'warning' : 'danger')
            ]);
    }

    public function headerActions(): array
    {
        return [
            Action::make('reset_done_call')
                ->label('Reset Done Calls')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn () => $this->getFollowUpSmallCompanyLeads()->count() > 0)
                ->requiresConfirmation()
                ->modalHeading('Reset Done Calls')
                ->modalDescription('Are you sure you want to reset "Done Calls" to 0? This action cannot be undone.')
                ->action(function () {
                    DB::beginTransaction(); // Start transaction

                    try {
                        $affectedRows = Lead::where('done_call', '=', '1')
                            ->whereNull('salesperson')
                            ->where('done_call', '=', '1')
                            ->where('company_size', '=', '1-24')
                            ->update(['done_call' => 0]);

                        // If no leads were updated, show a warning
                        if ($affectedRows === 0) {
                            Notification::make()
                                ->title('No Done Calls Were Reset')
                                ->warning()
                                ->send();
                            DB::rollBack(); // Rollback since nothing changed
                            return;
                        }

                        DB::commit(); // Commit transaction

                        // Show success notification
                        Notification::make()
                            ->title('Done Calls Reset Successfully')
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack(); // Rollback on failure

                        Notification::make()
                            ->title('Error Resetting Done Calls')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    public function render()
    {
        return view('livewire.call-attempt-small-comp-table');
    }
}
