<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\Lead;
use App\Models\HardwareHandover;
use App\Models\User;
use App\Services\CategoryService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Livewire\Attributes\On;

class HardwareHandoverAddon extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

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

    #[On('refresh-hardwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getNewHardwareHandovers()
    {
        $query = HardwareHandover::query();

        if (auth()->user()->role_id === 2) {
            // Salespersons (role_id 2) can see Draft, New, Approved, and Completed
            $query->whereIn('status', ['Completed']);

            // But only THEIR OWN records
            $userId = auth()->id();
            $query->whereHas('lead', function ($leadQuery) use ($userId) {
                $leadQuery->where('salesperson', $userId);
            });
        } else {
            // Other users (admin, managers) can only see New, Approved, and Completed
            $query->whereIn('status', ['Rejected', 'Draft']);
            // But they can see ALL records
        }

        // Salesperson filter logic
        if (auth()->user()->role_id === 3 || auth()->user()->role_id === 1) {
            // Role 3 users can see all handovers regardless of salesperson
            // No filtering needed here - we'll skip the salesperson filters
        } else {
            // Apply normal salesperson filtering for other roles
            if ($this->selectedUser === 'all-salespersons') {
                // Keep as is - show all salespersons' handovers
                $salespersonIds = User::where('role_id', 2)->pluck('id');
                $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                    $leadQuery->whereIn('salesperson', $salespersonIds);
                });
            } elseif (is_numeric($this->selectedUser)) {
                // Validate that the selected user exists and is a salesperson
                $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

                if ($userExists) {
                    $selectedUser = $this->selectedUser; // Create a local variable
                    $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                        $leadQuery->where('salesperson', $selectedUser);
                    });
                } else {
                    // Invalid user ID or not a salesperson, fall back to default
                    $query->whereHas('lead', function ($leadQuery) {
                        $leadQuery->where('salesperson', auth()->id());
                    });
                }
            } else {
                // Default: show current user's handovers
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id() ?? 0); // Avoid null
                });
            }
        }

        $query->orderByRaw("CASE
            WHEN status = 'New' THEN 1
            WHEN status = 'Approved' THEN 2
            WHEN status = 'Completed' THEN 3
            ELSE 4
        END")
        ->orderBy('created_at', 'desc');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Rejected' => 'Rejected',
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Completed: Installation' => 'Completed: Installation',
                        'Completed: Courier' => 'Completed: Courier',
                        'Completed Migration' => 'Completed Migration',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id',15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::where('role_id', '4')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            // ->filters([
            //     // Filter for Creator
            //     SelectFilter::make('created_by')
            //         ->label('Created By')
            //         ->multiple()
            //         ->options(User::pluck('name', 'id')->toArray())
            //         ->placeholder('Select User'),

            //     // Filter by Company Name
            //     SelectFilter::make('company_name')
            //         ->label('Company')
            //         ->searchable()
            //         ->options(HardwareHandover::distinct()->pluck('company_name', 'company_name')->toArray())
            //         ->placeholder('Select Company'),
            // ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandover $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $fullName . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('invoice_type')
                    ->label('Invoice Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                        default => ucfirst($state ?? 'Unknown')
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        'No Stock' => new HtmlString('<span style="color: red;">No Stock</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
                ])
                ->actions([
                    ActionGroup::make([
                        Action::make('view')
                            ->label('View')
                            ->icon('heroicon-o-eye')
                            ->color('secondary')
                            ->modalHeading(false)
                            ->modalWidth('6xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            // Use a callback function instead of arrow function for more control
                            ->modalContent(function (HardwareHandover $record): View {

                                // Return the view with the record using $this->record pattern
                                return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                            }),
                    ])
                    ->button()
                    ->color('primary')
                    ->label('Actions')
                ]);
    }

    public function render()
    {
        return view('livewire.hardware-handover-addon');
    }
}
