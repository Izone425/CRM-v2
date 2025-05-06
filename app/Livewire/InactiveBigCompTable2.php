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
use Filament\Tables\Filters\SelectFilter;

class InactiveBigCompTable2 extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getInactiveSmallCompanyLeads()
    {
        return Lead::query()
            ->where('categories', 'Inactive') // Only Inactive leads
            ->where('done_call', '1')
            ->whereNull('salesperson')
            ->where('company_size', '!=', '1-24') // Exclude small companies (1-24)
            ->selectRaw('*, DATEDIFF(updated_at, created_at) as pending_days');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getInactiveSmallCompanyLeads())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Inactive (1-24) - ' . $this->getInactiveSmallCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Filter for Lead Owner
                SelectFilter::make('lead_owner')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 1)->pluck('name', 'name')->toArray())
                    ->placeholder('Select Lead Owner'),
                SelectFilter::make('lead_status')
                    ->label('')
                    ->multiple()
                    ->options([
                        'Junk' => 'Junk',
                        'On Hold' => 'On Hold',
                        'Lost' => 'Lost',
                        'No Response' => 'No Response',
                    ])
                    ->placeholder('Select Lead Status'),
            ])
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
                TextColumn::make('created_at')
                    ->label('Created Time')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('j F Y, g:i A')),
                TextColumn::make('lead_status')
                    ->label('Lead Status')
                    ->sortable(),
                // TextColumn::make('pending_days')
                //     ->label('Pending Days')
                //     ->sortable()
                //     ->formatStateUsing(fn ($record) => $record->created_at->diffInDays($record->updated_at) . ' days')
                //     ->color(fn ($record) => $record->created_at->diffInDays($record->updated_at) == 0 ? 'draft' : 'danger'),
            ])
            ->headerActions($this->headerActions())
            ->actions([
                ActionGroup::make([
                    LeadActions::getLeadDetailAction(),
                    LeadActions::getViewAction(),
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
                ->visible(fn () => $this->getInactiveSmallCompanyLeads()->count() > 0)
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
        return view('livewire.inactive-big-comp-table2');
    }
}
