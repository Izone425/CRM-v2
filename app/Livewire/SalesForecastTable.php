<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Models\Quotation;
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
use Filament\Actions\Action;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\Filter;
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
            ->paginated([10, 25, 50, 100, 'all'])
            ->filters([
                SelectFilter::make('lead_status')
                    ->label('Lead Status')
                    ->multiple()
                    ->options([
                        'Hot' => 'Hot',
                        'Warm' => 'Warm',
                        'Cold' => 'Cold',
                    ]),
                Filter::make('deal_amount')
                    ->form([
                        \Filament\Forms\Components\Select::make('type')
                            ->label('Filter Type')
                            ->options([
                                'above' => 'Above Amount',
                                'below' => 'Below Amount',
                                'between' => 'Between Amounts',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('max_amount', $state !== 'between' ? null : 10000)),

                        \Filament\Forms\Components\TextInput::make('min_amount')
                            ->label(fn (callable $get) => $get('type') === 'below' ? 'Maximum Amount' : 'Minimum Amount')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter amount in RM')
                            ->default(10000),

                        \Filament\Forms\Components\TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter amount in RM')
                            ->visible(fn (callable $get) => $get('type') === 'between'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['type']) || empty($data['min_amount'])) {
                            return $query;
                        }

                        return match ($data['type']) {
                            'above' => $query->where('deal_amount', '>=', $data['min_amount']),
                            'below' => $query->where(function ($query) use ($data) {
                                $query->where('deal_amount', '<=', $data['min_amount'])
                                    ->orWhereNull('deal_amount');
                            }),
                            'between' => $query->whereBetween('deal_amount', [
                                $data['min_amount'],
                                $data['max_amount'] ?? $data['min_amount']
                            ]),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['type']) || empty($data['min_amount'])) {
                            return null;
                        }

                        return match ($data['type']) {
                            'above' => 'Deal amount ≥ RM ' . number_format($data['min_amount'], 2),
                            'below' => 'Deal amount ≤ RM ' . number_format($data['min_amount'], 2),
                            'between' => 'Deal amount between RM ' . number_format($data['min_amount'], 2) .
                                        ' and RM ' . number_format($data['max_amount'] ?? $data['min_amount'], 2),
                            default => null,
                        };
                    }),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->getStateUsing(fn (Lead $record) => \App\Models\User::find($record->salesperson)?->name ?? '-'),
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
                TextColumn::make('created_at')
                    ->label('Lead Created On')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('first_demo')
                    ->label('Demo Date')
                    ->date('d M Y')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // We need to join with appointments table to sort by the first demo date
                        return $query
                            ->leftJoin('appointments', function ($join) {
                                $join->on('leads.id', '=', 'appointments.lead_id')
                                     ->whereRaw('appointments.id = (
                                         SELECT MIN(a2.id)
                                         FROM appointments as a2
                                         WHERE a2.lead_id = leads.id
                                         ORDER BY a2.date ASC
                                     )');
                            })
                            ->orderBy('appointments.date', $direction)
                            ->select('leads.*'); // Make sure we only select from the leads table
                    })
                    ->getStateUsing(function (Lead $record) {
                        try {
                            // Get the first demo appointment
                            $firstDemo = $record->demoAppointment()->orderBy('date', 'asc')->first();

                            // Only log and access date if $firstDemo is not null
                            if ($firstDemo) {
                                return $firstDemo->date;
                            }

                            return null;
                        } catch (\Exception $e) {
                            info('Error getting first demo date: ' . $e->getMessage());
                            return null;
                        }
                    })
                    ->placeholder('No demo scheduled'),

                TextColumn::make('from_new_demo')
                    ->label('From New Demo')
                    ->getStateUsing(fn (Lead $record) =>
                        ($days = $record->calculateDaysFromNewDemo()) !== '-'
                            ? $days . ' days'
                            : $days
                    )
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Join with appointments to sort by days from new demo
                        return $query
                            ->leftJoin('appointments', function ($join) {
                                $join->on('leads.id', '=', 'appointments.lead_id')
                                    ->whereRaw('appointments.id = (
                                        SELECT MIN(a2.id)
                                        FROM appointments as a2
                                        WHERE a2.lead_id = leads.id
                                        ORDER BY a2.created_at ASC
                                    )');
                            })
                            ->orderByRaw("CASE WHEN appointments.id IS NULL THEN 1 ELSE 0 END")
                            ->orderBy('appointments.created_at', $direction === 'desc' ? 'asc' : 'desc')
                            ->select('leads.*'); // Make sure we only select from the leads table
                    }),

                TextColumn::make('deal_amount')
                    ->label('Deal Amount')
                    ->sortable()
                    ->default('RM 0')
                    ->formatStateUsing(function ($state) {
                        if (is_null($state) || $state === '') {
                            return '-';
                        }

                        // Convert to float to ensure numeric value
                        $amount = floatval($state);
                        return 'RM ' . number_format($amount, 2);
                    }),
            ])
            ->actions([
                ActionsAction::make('viewLatestQuotation')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->size('extra-large')
                    ->url(function (Lead $record) {
                        // First try to get a quotation marked as final
                        $finalQuotation = $record->quotations()
                            ->where('mark_as_final', 1)
                            ->latest()
                            ->first();

                        // If there is a final quotation, return its route
                        if ($finalQuotation) {
                            return route('pdf.print-quotation-v2', $finalQuotation);
                        }

                        // Fallback to the most recent quotation if no final one exists
                        $latestQuotation = $record->quotations()->latest()->first();
                        if ($latestQuotation) {
                            return route('pdf.print-quotation-v2', $latestQuotation);
                        }

                        return null; // No quotation available
                    })
                    ->visible(function (Lead $record) {
                        // Only show this action if the lead has quotations
                        return $record->quotations()->exists();
                    })
                    ->openUrlInNewTab(),
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
