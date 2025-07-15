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

class AdminRepairPendingOnsiteRepair extends Component implements HasForms, HasTable
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
            ->where('status', 'Pending Onsite Repair')
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

    protected static function getSparePartOptionHtml(\App\Models\SparePart $part): string
    {
        $imageUrl = $part->picture_url ?? url('images/no-image.jpg');
        $fullImageUrl = $imageUrl; // Keep the original URL for the full view

        return '
            <div class="flex items-center w-full gap-2">
                <div class="flex-shrink-0 w-8 h-8">
                    <img src="' . e($imageUrl) . '" class="object-cover w-full h-full rounded"
                        onerror="this.onerror=null; this.src=\'' . e(url('images/no-image.jpg')) . '\'" />
                </div>
                <div class="flex-grow truncate">
                    <div class="font-medium truncate">' . e($part->name) . '</div>
                    <div class="text-xs text-gray-500 truncate">' . e($part->device_model) . '</div>
                </div>
                <div class="flex-shrink-0">
                    <button type="button"
                        onclick="event.stopPropagation(); window.open(\'' . e($fullImageUrl) . '\', \'_blank\'); return false;"
                        class="px-1 py-1 text-xs rounded text-primary-600 hover:text-primary-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>
        ';
    }

    protected function getDeviceWarrantyYears(string $deviceModel): int
    {
        // Standardize the model name for comparison (uppercase and trim spaces)
        $model = strtoupper(trim($deviceModel));

        // Map device models to their warranty periods
        return match (true) {
            str_contains($model, 'TC10') => 2,
            str_contains($model, 'TC20') => 2,
            str_contains($model, 'FACE ID 5') => 2,
            str_contains($model, 'FACE ID 6') => 2,
            str_contains($model, 'TC100C') => 2,
            str_contains($model, 'TIME BEACON') => 1,
            str_contains($model, 'NFC') => 1,
            // Default case
            default => 1,
        };
    }

    public function render()
    {
        return view('livewire.admin-repair-pending-onsite-repair');
    }
}
