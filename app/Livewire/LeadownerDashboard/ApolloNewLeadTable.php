<?php

namespace App\Livewire\LeadownerDashboard;

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
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class ApolloNewLeadTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser; // Allow dynamic filtering
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

    public function getPendingLeadsQuery()
    {
        $query = Lead::query()
            ->where('lead_code', 'LinkedIn')
            ->where('categories', 'New')
            ->whereNull('salesperson') // Still keeping this condition unless you want to include assigned ones too
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days');

        if ($this->selectedUser === 'all-lead-owners') {
            $leadOwnerNames = User::where('role_id', 1)->pluck('name');
            $query->whereIn('lead_owner', $leadOwnerNames);
        } elseif ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereIn('salesperson', $salespersonIds);
        } elseif ($this->selectedUser) {
            $selectedUser = User::find($this->selectedUser);

            if ($selectedUser) {
                if ($selectedUser->role_id == 1) {
                    $query->where('lead_owner', $selectedUser->name);
                } elseif ($selectedUser->role_id == 2) {
                    $query->where('salesperson', $selectedUser->id);
                }
            }
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->query($this->getPendingLeadsQuery())
            ->emptyState(fn () => view('components.empty-state-question'))
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
                SelectFilter::make('lead_owner')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 1)->pluck('name', 'name')->toArray())
                    ->placeholder('Select Lead Owner')
                    ->hidden(fn () => auth()->user()->role_id !== 3),
            ])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
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

                TextColumn::make('lead_code')
                    ->label('Lead Source')
                    ->sortable(),

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
                TextColumn::make('pending_days')
                    ->label('Pending Days')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->pending_days . ' days')
                    ->color(fn ($record) => $record->pending_days == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getViewAction(),
                    LeadActions::getAssignToMeAction(),
                    LeadActions::getAssignLeadAction(),
                    LeadActions::getViewReferralDetailsAction(),
                ])
                ->button()
                ->color(fn (Lead $record) => $record->follow_up_needed ? 'warning' : 'danger'),
            ])
            ->bulkActions([
                BulkAction::make('Assign to Me')
                    ->label('Assign Selected Leads to Me')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $this->bulkAssignToMe($records))
                    ->color('primary'),
            ]);
    }

    public function bulkAssignToMe($records)
    {
        $user = auth()->user();

        foreach ($records as $record) {
            // Update the lead owner and related fields
            $record->update([
                'lead_owner' => $user->name,
                'categories' => 'Active',
                'stage' => 'Transfer',
                'lead_status' => 'New',
                'pickup_date' => now(),
            ]);

            // Update the latest activity log
            $latestActivityLog = ActivityLog::where('subject_id', $record->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . $user->name) {
                $latestActivityLog->update([
                    'description' => 'Lead assigned to Lead Owner: ' . $user->name,
                ]);

                activity()
                    ->causedBy($user)
                    ->performedOn($record);
            }
        }

        Notification::make()
            ->title(count($records) . ' Leads Assigned Successfully')
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.leadowner_dashboard.apollo-new-lead-table');
    }
}
