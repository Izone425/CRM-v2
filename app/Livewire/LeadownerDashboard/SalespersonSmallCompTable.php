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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Livewire\Attributes\On;

class SalespersonSmallCompTable extends Component implements HasForms, HasTable
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

    public function getActiveSmallCompanyLeadsWithSalesperson()
    {
        return Lead::query()
            ->whereNotNull('salesperson') // Ensure salesperson is NOT NULL
            ->where('company_size', '=', '1-24') // Only small companies (1-24)
            ->where('categories', '!=', 'Inactive') // Exclude Inactive leads
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_time'); // Default sorting by latest created leads
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getActiveSmallCompanyLeadsWithSalesperson())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'SalesPerson (1-24) - ' . $this->getActiveSmallCompanyLeadsWithSalesperson()->count() . ' Records') // Display count
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

                Filter::make('created_at')
                ->form([
                    DateRangePicker::make('date_range')
                        ->label('')
                        ->placeholder('Select created date range'),
                ])
                ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range from the "start - end" format
                        [$start, $end] = explode(' - ', $data['date_range']);

                        // Ensure valid dates
                        $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                        $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                        // Apply the filter
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                })
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
                TextColumn::make('stage')
                    ->label('Stage')
                    ->sortable(),
                // TextColumn::make('pending_time')
                //     ->label('Pending Days')
                //     ->sortable()
                //     ->formatStateUsing(fn ($record) => $record->created_at->diffInDays(now()) . ' days')
                //     ->color(fn ($record) => $record->created_at->diffInDays(now()) == 0 ? 'draft' : 'danger'),
            ])
            ->actions([
                ActionGroup::make([
                    // LeadActions::getCancelDemoAction()
                    //     ->visible(fn (Lead $record) => $record->lead_status == 'Demo-Assigned' && $record->lead_owner == auth()->user()->name),
                    LeadActions::getLeadDetailAction(),
                    LeadActions::getViewAction(),
                ])
                ->button()
                ->color(fn (Lead $record) => $record->follow_up_needed ? 'warning' : 'danger')
            ]);
    }

    public function render()
    {
        return view('livewire.leadowner_dashboard.salesperson-small-comp-table');
    }
}
