<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateRepairPdfController;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\AdminRepair;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class AdminRepairCompleted extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexDeviceCounter = 0;
    protected static ?int $indexRemarkCounter = 0;

    public $selectedUser;
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

    #[On('refresh-adminrepair-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);

        $this->resetTable();
    }

    public function getTableQuery(): Builder
    {
        $query = AdminRepair::query()
            ->where('status', 'Completed')
            ->orderBy('created_at', 'desc');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'Awaiting Parts' => 'Awaiting Parts',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            return 'From: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                ' To: ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }
                        return null;
                    }),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewRepairDetails')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (AdminRepair $record): View {
                                return view('components.repair-detail')
                                    ->with('record', $record);
                            })
                    ),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('days_elapsed')
                    ->label('Total Days')
                    ->state(function (AdminRepair $record) {
                        if (!$record->created_at) {
                            return '0 days';
                        }

                        $createdDate = Carbon::parse($record->created_at);
                        $today = Carbon::now();
                        $diffInDays = $createdDate->diffInDays($today);

                        return $diffInDays . ' ' . Str::plural('day', $diffInDays);
                    }),

                TextColumn::make('created_by')
                    ->label('Submitted By')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        $user = User::find($state);
                        return $user ? $user->name : 'Unknown User';
                    }),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();

                            if ($company) {
                                $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                                $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                                return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                        target="_blank"
                                        title="' . e($company->company_name) . '"
                                        class="inline-block"
                                        style="color:#338cf0;">
                                        ' . $company->company_name . '
                                    </a>');
                            }
                        }

                        // If we have a state but no company was found by lead_id
                        if ($state) {
                            $shortened = strtoupper(Str::limit($state, 20, '...'));
                            return "<span title='" . e($state) . "'>{$state}</span>";
                        }

                        return 'N/A';
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'New' => 'danger',
                        'In Progress' => 'warning',
                        'Awaiting Parts' => 'info',
                        'Resolved' => 'success',
                        'Closed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    // View detail action
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->modalHeading(' ')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (AdminRepair $record): View {
                            return view('components.repair-detail')
                                ->with('record', $record);
                        }),
                    ])->button()
            ]);
    }

    public function render()
    {
        return view('livewire.admin-repair-completed');
    }
}
