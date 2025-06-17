<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\Lead;
use App\Models\User;
use Filament\Forms\Components\TextInput;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class InactiveBigCompTable1 extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function getInactiveBigCompanyLeads()
    {
        return Lead::query()
            ->where('categories', 'Inactive') // Only Inactive leads
            ->where('lead_status', '!=', 'Closed')
            ->where('done_call', '0')
            ->whereNull('salesperson')
            ->where('company_size', '!=', '1-24') // Exclude small companies (1-24)
            ->selectRaw('*, DATEDIFF(updated_at, created_at) as pending_days');
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getInactiveBigCompanyLeads())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Inactive (25 Above) - ' . $this->getInactiveBigCompanyLeads()->count() . ' Records') // Display count
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
                Filter::make('created_month')
                    ->form([
                        TextInput::make('created_month')
                            ->type('month')
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['created_month'], function ($query, $date) {
                            // Extract month and year from the selected date
                            $month = Carbon::parse($date)->format('m');
                            $year = Carbon::parse($date)->format('Y');

                            // Filter records for the entire month
                            return $query->whereYear('created_at', $year)
                                        ->whereMonth('created_at', $month);
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_month'] ?? null) {
                            $indicators['created_month'] = 'Created in ' . Carbon::parse($data['created_month'])->format('F Y');
                        }

                        return $indicators;
                    })
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
                TextColumn::make('call_attempt')
                    ->label('Call Attempt')
                    ->sortable(),
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
            ->actions([
                ActionGroup::make([
                    LeadActions::getLeadDetailAction(),
                    LeadActions::getViewAction(),
                    LeadActions::getInactiveTransferCallAttempt(),
                ])
                ->button()
                ->color(fn (Lead $record) => $record->follow_up_needed ? 'warning' : 'primary')
            ]);
    }

    public function render()
    {
        return view('livewire.inactive-big-comp-table1');
    }
}
