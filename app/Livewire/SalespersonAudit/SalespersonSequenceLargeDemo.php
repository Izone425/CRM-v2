<?php

namespace App\Livewire\SalespersonAudit;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Lead;
use Carbon\Carbon;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Filament\Filters\SortFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class SalespersonSequenceLargeDemo extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedStat = 'demo';
    public $lastRefreshTime;
    public $demoCount = 0;
    public $rfqCount = 0;

    // Company sizes considered "large"
    protected $largeCompanySizes = ['100-500'];

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->loadCounts();
    }

    public function loadCounts()
    {
        // Count demos for large companies
        $this->demoCount = Appointment::query()
            ->whereIn('status', ['New', 'Done'])
            ->whereHas('lead', function ($query) {
                $query->whereIn('company_size', $this->largeCompanySizes);
            })
            ->count();
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->loadCounts();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('updateSelectedTab')]
    public function updateSelectedTab($tab)
    {
        $this->selectedStat = $tab;
        $this->resetTable();
    }

    public function getTableQuery()
    {
        $startDate = Carbon::parse('2025-06-30');

        $query = Appointment::query()
            ->whereIn('status', ['New', 'Done'])
            ->whereIn('salesperson', [12, 6, 9])
            ->whereHas('lead', function ($query) use ($startDate) {
                $query->whereIn('company_size', $this->largeCompanySizes)
                    ->where('created_at', '>=', $startDate);
            })
            ->whereIn('causer_id', function($query) {
                $query->select('id')
                    ->from('users')
                    ->where('role_id', 1);
            })
            ->with(['lead', 'lead.companyDetail']);

        return $query;
    }
    public function table(Table $table): Table
    {
        $tableBuilder = $table
            ->poll('300s')
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 15])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', 2)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder('All Salespersons')
                    ->multiple(),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('From Date'),
                        DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate(
                                    $this->selectedStat === 'demo' ? 'date' : 'created_at',
                                    '>=',
                                    $date
                                ),
                            )
                            ->when(
                                $data['to_date'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate(
                                    $this->selectedStat === 'demo' ? 'date' : 'created_at',
                                    '<=',
                                    $date
                                ),
                            );
                    }),

                SortFilter::make("sort_by"),
            ]);

        if ($this->selectedStat === 'demo') {
            // Set up columns for Demo table
            $tableBuilder->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Demo Date')
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('d M Y') : '-')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->lead && $record->lead->companyDetail) {
                            $shortened = strtoupper(Str::limit($record->lead->companyDetail->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($record->lead->companyDetail->company_name) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>');
                        }
                        return "-";
                    })
                    ->html(),

                TextColumn::make('lead.company_size')
                    ->label('Company Size')
                    ->badge()
                    ->color('success'),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->formatStateUsing(function ($state) {
                        return User::find($state)?->name ?? $state;
                    }),

                TextColumn::make('causer_id')
                    ->label('Created By')
                    ->formatStateUsing(function ($state) {
                        return User::find($state)?->name ?? '-';
                    }),
            ]);
        } else {
            // Set up columns for RFQ table
            $tableBuilder->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('activities.0.created_at')
                    ->label('RFQ Date')
                    ->formatStateUsing(function ($state, $record) {
                        $activity = $record->activities->first();
                        return $activity ? Carbon::parse($activity->created_at)->format('d M Y') : '-';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            DB::raw("(SELECT MAX(created_at) FROM activity_log WHERE subject_id = leads.id AND description LIKE '%rfq only%')"),
                            $direction
                        );
                    }),

                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->companyDetail) {
                            $shortened = strtoupper(Str::limit($record->companyDetail->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($record->companyDetail->company_name) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>');
                        }
                        return "-";
                    })
                    ->html(),

                TextColumn::make('company_size')
                    ->label('Company Size')
                    ->badge()
                    ->color('success'),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->formatStateUsing(function ($state) {
                        return User::find($state)?->name ?? $state;
                    }),

                TextColumn::make('activities.0.causer_id')
                    ->label('Created By')
                    ->formatStateUsing(function ($state, $record) {
                        $activity = $record->activities->first();
                        return $activity ? User::find($activity->causer_id)?->name ?? '-' : '-';
                    }),
            ]);
        }

        return $tableBuilder;
    }

    public function render()
    {
        return view('livewire.salesperson_audit.salesperson-sequence-large-demo');
    }
}
