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
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
            ->where('categories', 'New')
            ->where('salesperson', null)
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days');
            // ->orderBy('created_at', 'desc');
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
            ])
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
        return view('livewire.new-lead-table');
    }
}
