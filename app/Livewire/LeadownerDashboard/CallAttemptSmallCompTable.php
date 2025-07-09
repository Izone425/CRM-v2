<?php

namespace App\Livewire\LeadownerDashboard;

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
use Livewire\Attributes\On;

class CallAttemptSmallCompTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

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

    #[On('refresh-leadowner-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

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
            ->poll('300s')
            ->query($this->getFollowUpSmallCompanyLeads())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Call Attempt (1-24) - ' . $this->getFollowUpSmallCompanyLeads()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('company_size_label') // Use the correct filter key
                    ->label('')
                    ->options([
                        'Small' => 'Small',
                        'Medium' => 'Medium',
                        'Large' => 'Large',
                        'Enterprise' => 'Enterprise',
                    ])
                    ->multiple() // Enables multi-selection
                    ->placeholder('Select Company Size')
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['values'])) { // 'values' stores multiple selections
                            $sizeMap = [
                                'Small' => '1-24',
                                'Medium' => '25-99',
                                'Large' => '100-500',
                                'Enterprise' => '501 and Above',
                            ];

                            // Convert selected sizes to DB values
                            $dbValues = collect($data['values'])->map(fn ($size) => $sizeMap[$size] ?? null)->filter();

                            if ($dbValues->isNotEmpty()) {
                                $query->whereHas('companyDetail', function ($query) use ($dbValues) {
                                    $query->whereIn('company_size', $dbValues);
                                });
                            }
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return !empty($data['values'])
                            ? 'Company Size: ' . implode(', ', $data['values'])
                            : null;
                    }),
                // Filter for Lead Owner
                SelectFilter::make('lead_owner')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 1)->pluck('name', 'name')->toArray())
                    ->placeholder('Select Lead Owner'),
            ])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
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
                TextColumn::make('company_size_label')
                    ->label('Company Size')
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
        return view('livewire.leadowner_dashboard.call-attempt-small-comp-table');
    }
}
