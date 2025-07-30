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

class SalespersonSequenceLargeRfqRank2 extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $rfqCount = 0;
    public $rank1 = [];
    public $rankUsers = [];

    // Company sizes considered "large"
    protected $largeCompanySizes = ['100-500'];

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        $rank1Names = ['Muhammad Khoirul Bariah', 'Abdul Aziz', 'Yasmin', 'Farhanah Jamil'];
        $this->rank1 = User::whereIn('name', $rank1Names)->pluck('id')->toArray();
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

    public function getTableQuery()
    {
        // Use rankUsers property which will contain either the passed rank or the default rank1
        $userIds = !empty($this->rankUsers) ? $this->rankUsers : [11, 10, 7, 8]; // Fallback IDs

        // Make sure you're querying the Spatie Activity model
        return \Spatie\Activitylog\Models\Activity::query()
            ->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
            ->whereIn('properties->attributes->salesperson', $userIds)
            ->where(function($query) {
                foreach ($this->mediumCompanySizes as $size) {
                    $query->orWhere('properties->attributes->company_size', $size);
                }
            })
            ->with(['subject', 'causer']);  // Load both relationships
    }

    public function table(Table $table): Table
    {
        return $table
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
                    ->rowIndex()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('RFQ Date')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('d M Y'))
                    ->sortable(),

                TextColumn::make('subject_id')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {  // Changed $leadId to $state for consistency
                        if ($state) {  // This is the subject_id value
                            // Get company details directly using subject_id (which is the lead ID)
                            $companyDetail = \App\Models\CompanyDetail::where('lead_id', $state)->first();

                            if ($companyDetail) {
                                $shortened = strtoupper(Str::limit($companyDetail->company_name, 20, '...'));
                                $encryptedId = \App\Classes\Encryptor::encrypt($state);

                                return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($companyDetail->company_name) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>');
                            }
                        }

                        // Fallback: try to get from properties if subject_id approach failed
                        $properties = $record->properties;
                        if ($properties) {
                            $properties = is_string($properties) ? json_decode($properties, true) : $properties;
                            $leadId = $properties['attributes']['id'] ?? null;

                            if ($leadId) {
                                $companyDetail = \App\Models\CompanyDetail::where('lead_id', $leadId)->first();
                                if ($companyDetail) {
                                    $shortened = strtoupper(Str::limit($companyDetail->company_name, 20, '...'));
                                    $encryptedId = \App\Classes\Encryptor::encrypt($leadId);

                                    return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                        target="_blank"
                                        title="' . e($companyDetail->company_name) . '"
                                        class="inline-block"
                                        style="color:#338cf0;">
                                        ' . $shortened . '
                                    </a>');
                                }
                            }
                        }

                        return "-";
                    })
                    ->html(),

                TextColumn::make('properties')
                    ->label('Salesperson')
                    ->formatStateUsing(function ($state, $record) {
                        // First try to get from properties
                        $properties = is_string($state) ? json_decode($state, true) : $state;
                        $spId = $properties['attributes']['salesperson'] ?? null;
                        info($properties);
                        if ($spId) {
                            $user = User::find($spId);
                            if ($user) {
                                return $user->name;
                            }
                        }

                        // If no salesperson in properties, try to get from the lead
                        if ($record->subject_id) {
                            $lead = Lead::find($record->subject_id);
                            if ($lead && $lead->salesperson) {
                                $user = User::find($lead->salesperson);
                                if ($user) {
                                    return $user->name;
                                }
                                return $lead->salesperson;
                            }
                        }

                        return '-';
                    }),

                TextColumn::make('causer_id')
                    ->label('Created By')
                    ->formatStateUsing(fn ($state) => User::find($state)?->name ?? '-'),
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson_audit.salesperson-sequence-large-rfq-rank2');
    }
}
