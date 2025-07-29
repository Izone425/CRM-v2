<?php

namespace App\Livewire\SalespersonAudit;

use App\Models\Lead;
use App\Models\User;
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

class SalespersonSequenceSmallRfq extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $rfqCount = 0;

    // Company sizes considered "small"
    protected $smallCompanySizes = ['1-24'];

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->loadCount();
    }

    public function loadCount()
    {
        // Count RFQs for small companies
        $this->rfqCount = Lead::query()
            ->whereIn('company_size', $this->smallCompanySizes)
            ->whereHas('activities', function ($q) {
                $q->whereRaw("LOWER(description) LIKE ?", ['%rfq only%']);
            })
            ->count();
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->loadCount();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    public function getTableQuery()
    {
        // For RFQs, we need to look at activity logs
        $query = Lead::query()
            ->whereIn('company_size', $this->smallCompanySizes)
            ->whereHas('activities', function ($q) {
                $q->whereRaw("LOWER(description) LIKE ?", ['%rfq only%']);
            })
            ->with(['companyDetail', 'activities' => function ($q) {
                $q->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
                  ->latest();
            }]);

        // Filter only leads where RFQ was added by a user with role_id 1
        $query->whereHas('activities', function ($q) {
            $q->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
              ->whereIn('causer_id', function($subquery) {
                  $subquery->select('id')
                      ->from('users')
                      ->where('role_id', 1);
              });
        });

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->paginated([10, 25, 50])
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
                                fn (Builder $q, $date): Builder => $q->whereHas(
                                    'activities',
                                    fn ($actQuery) => $actQuery->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
                                        ->whereDate('created_at', '>=', $date)
                                ),
                            )
                            ->when(
                                $data['to_date'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereHas(
                                    'activities',
                                    fn ($actQuery) => $actQuery->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
                                        ->whereDate('created_at', '<=', $date)
                                ),
                            );
                    }),

                SortFilter::make("sort_by"),
            ])
            ->columns([
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

    public function render()
    {
        return view('livewire.salesperson_audit.salesperson-sequence-small-rfq');
    }
}
