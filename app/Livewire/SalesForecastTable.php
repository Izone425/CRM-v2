<?php

namespace App\Livewire;

use App\Models\Lead;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Filament\Tables\Filters\SelectFilter;

class SalesForecastTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
    public $selectedMonth;
    public $totals = [
        'hot' => 0,
        'warm' => 0,
        'cold' => 0,
    ];

    public static function canAccess(): bool
    {
        return auth()->user()->role_id != '2';
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser, $selectedMonth)
    {
        $this->selectedUser = $selectedUser === "" ? null : $selectedUser;
        $this->selectedMonth = $selectedMonth === "" ? null : $selectedMonth;

        session(['selectedUser' => $this->selectedUser]);
        session(['selectedMonth' => $this->selectedMonth]);

        $this->calculateTotals();
        $this->resetTable();
    }

    public function getFilteredLeadsQuery(): Builder
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser', null);
        $this->selectedMonth = $this->selectedMonth ?? session('selectedMonth', null);

        $query = Lead::query()
            ->with('companyDetail')
            ->join('company_details', 'leads.company_name', '=', 'company_details.id')
            ->whereIn('lead_status', ['Hot', 'Warm', 'Cold'])
            ->select('leads.*', 'company_details.company_name')
            ->selectRaw('DATEDIFF(NOW(), leads.created_at) as pending_days');

        if ($this->selectedUser !== null) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth !== null) {
            $query->whereMonth('leads.created_at', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('leads.created_at', Carbon::parse($this->selectedMonth)->year);
        }

        return $query;
    }

    // public function calculateTotals()
    // {
    //     $this->selectedUser = $this->selectedUser ?? session('selectedUser', null);
    //     $this->selectedMonth = $this->selectedMonth ?? session('selectedMonth', null);

    //     $query = Lead::query()->whereIn('lead_status', ['Hot', 'Warm', 'Cold']);

    //     if ($this->selectedUser !== null) {
    //         $query->where('salesperson', $this->selectedUser);
    //     }

    //     if ($this->selectedMonth !== null) {
    //         $query->whereMonth('created_at', Carbon::parse($this->selectedMonth)->month)
    //               ->whereYear('created_at', Carbon::parse($this->selectedMonth)->year);
    //     }

    //     $totals = $query->selectRaw("
    //         COUNT(CASE WHEN lead_status = 'Hot' THEN 1 END) as hot_count,
    //         SUM(CASE WHEN lead_status = 'Hot' THEN deal_amount ELSE 0 END) as hot,
    //         COUNT(CASE WHEN lead_status = 'Warm' THEN 1 END) as warm_count,
    //         SUM(CASE WHEN lead_status = 'Warm' THEN deal_amount ELSE 0 END) as warm,
    //         COUNT(CASE WHEN lead_status = 'Cold' THEN 1 END) as cold_count,
    //         SUM(CASE WHEN lead_status = 'Cold' THEN deal_amount ELSE 0 END) as cold
    //     ")->first();

    //     $this->totals = [
    //         'hot' => $totals->hot ?? 0,
    //         'hot_count' => $totals->hot_count ?? 0,
    //         'warm' => $totals->warm ?? 0,
    //         'warm_count' => $totals->warm_count ?? 0,
    //         'cold' => $totals->cold ?? 0,
    //         'cold_count' => $totals->cold_count ?? 0,
    //     ];
    // }
    public function calculateTotals()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser', null);
        $this->selectedMonth = $this->selectedMonth ?? session('selectedMonth', null);

        $query = Lead::query()->whereIn('lead_status', ['Hot', 'Warm', 'Cold']);

        if ($this->selectedUser !== null) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth !== null) {
            $query->whereMonth('created_at', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('created_at', Carbon::parse($this->selectedMonth)->year);
        }

        $totals = $query->selectRaw("
            COUNT(CASE WHEN lead_status = 'Hot' THEN 1 END) as hot_count,
            SUM(CASE WHEN lead_status = 'Hot' THEN deal_amount ELSE 0 END) as hot,
            COUNT(CASE WHEN lead_status = 'Warm' THEN 1 END) as warm_count,
            SUM(CASE WHEN lead_status = 'Warm' THEN deal_amount ELSE 0 END) as warm,
            COUNT(CASE WHEN lead_status = 'Cold' THEN 1 END) as cold_count,
            SUM(CASE WHEN lead_status = 'Cold' THEN deal_amount ELSE 0 END) as cold
        ")->first();

        $this->totals = [
            'hot' => $totals->hot ?? 0,
            'hot_count' => $totals->hot_count ?? 0,
            'warm' => $totals->warm ?? 0,
            'warm_count' => $totals->warm_count ?? 0,
            'cold' => $totals->cold ?? 0,
            'cold_count' => $totals->cold_count ?? 0,
        ];
    }
    public function mount()
    {
        $this->calculateTotals();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getFilteredLeadsQuery())
            ->defaultSort('lead_status')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('lead_status')
                    ->label('Lead Status')
                    ->multiple()
                    ->options([
                        'Hot' => 'Hot',
                        'Warm' => 'Warm',
                        'Cold' => 'Cold',
                    ]),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
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
                TextColumn::make('lead_status')
                    ->label('Lead Status')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("
                            FIELD(lead_status, 'Hot', 'Warm', 'Cold') $direction
                        ");
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Hot' => 'danger',
                        'Warm' => 'warning',
                        'Cold' => 'gray',
                        default => 'secondary',
                    }),
                TextColumn::make('from_new_demo')
                    ->label('FROM NEW DEMO')
                    ->getStateUsing(fn (Lead $record) =>
                        ($days = $record->calculateDaysFromNewDemo()) !== '-'
                            ? $days . ' days'
                            : $days
                    ),

                TextColumn::make('deal_amount')
                    ->label('Deal Amount')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? 'RM ' . number_format($state, 2) : '-'),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkAction::make('resetLeadStatusToCold')
                    ->label('Reset to Cold')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        foreach ($records as $record) {
                            $record->update([
                                'lead_status' => 'Cold',
                            ]);

                            $latestActivityLog = \App\Models\ActivityLog::where('subject_id', $record->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                $latestActivityLog->update([
                                    'description' => 'Lead status reset to Cold by Manager',
                                ]);
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Lead Status Updated')
                            ->success()
                            ->body(count($records) . ' leads have been reset to Cold.')
                            ->send();
                    }),

                \Filament\Tables\Actions\BulkAction::make('changeLeadStatus')
                    ->label('Change Lead Status')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        \Filament\Forms\Components\Select::make('lead_status')
                            ->label('New Lead Status')
                            ->options([
                                'Hot' => 'Hot',
                                'Warm' => 'Warm',
                                'Cold' => 'Cold',
                            ])
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data) {
                        foreach ($records as $record) {
                            $record->update([
                                'lead_status' => $data['lead_status'],
                            ]);

                            $latestActivityLog = \App\Models\ActivityLog::where('subject_id', $record->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                $latestActivityLog->update([
                                    'description' => 'Lead status changed to ' . $data['lead_status'] . ' by Manager',
                                ]);
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Lead Status Updated')
                            ->success()
                            ->body(count($records) . ' leads updated to status: ' . $data['lead_status'])
                            ->send();
                    }),
            ]);
    }
}
