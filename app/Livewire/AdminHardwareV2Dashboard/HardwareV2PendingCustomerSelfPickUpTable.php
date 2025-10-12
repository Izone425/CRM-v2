<?php
// filepath: /var/www/html/timeteccrm/app/Livewire/AdminHardwareV2Dashboard/HardwareV2NewTable.php

namespace App\Livewire\AdminHardwareV2Dashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\HardwareHandoverV2;
use App\Models\Lead;
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
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class HardwareV2PendingCustomerSelfPickUpTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;
    protected static ?int $indexRepeater3 = 0;
    protected static ?int $indexRepeater4 = 0;

    public $selectedUser;
    public $lastRefreshTime;
    public $currentDashboard;

    public function mount($currentDashboard = null)
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->currentDashboard = $currentDashboard ?? 'HardwareAdminV2';
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

    #[On('refresh-HardwareHandoverV2-tables')]
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

    public function getNewHardwareHandovers()
    {
        return HardwareHandoverV2::query()
            ->whereIn('status', ['Pending Customer: Self Pick-Up'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Pending Customer: Self Pick-Up'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);

        return $query->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Completed Migration' => 'Completed Migration',
                        'Pending Payment' => 'Pending Payment',
                        'Completed: Internal Installation' => 'Completed: Internal Installation',
                        'Completed: External Installation' => 'Completed: External Installation',
                        'Completed: Courier' => 'Completed: Courier',
                        'Completed: Self Pick Up' => 'Completed: Self Pick Up',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15) // Exclude Testing Account
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
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandoverV2 $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        if ($record->handover_pdf) {
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        return '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('6xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandoverV2 $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandoverV2 $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    }),

                TextColumn::make('implementer')
                    ->label('Implementer'),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 30, '...'));
                        $encryptedId = Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('invoice_type')
                    ->label('Invoice Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                        default => ucfirst($state ?? 'Unknown')
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Pending Stock' => new HtmlString('<span style="color: orange;">Pending Stock</span>'),
                        'Pending Migration' => new HtmlString('<span style="color: purple;">Pending Migration</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),

                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading('Hardware Handover Details')
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HardwareHandoverV2 $record): View {
                            return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),
                    Action::make('complete_self_pickup')
                        ->label('Complete Self Pick-up')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalHeading('Complete the Task')
                        ->modalWidth('3xl')
                        ->form([
                            Section::make('Self Pick-up Details')
                                ->description('Complete the self pick-up process by filling in the required details')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            DatePicker::make('self_pickup_date')
                                                ->label('Self Pick-up Date')
                                                ->required()
                                                ->native(false)
                                                ->displayFormat('d/m/Y')
                                                ->maxDate(now()) // Cannot set future date
                                                ->helperText('Select the actual date when customer picked up the hardware')
                                                ->default(now())
                                                ->columnSpan(1),

                                            FileUpload::make('delivery_order')
                                                ->label('Delivery Order')
                                                ->directory('hardware-delivery-orders')
                                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                ->maxSize(10240) // 10MB max
                                                ->required()
                                                ->helperText('Upload delivery order document (PDF, JPG, PNG - max 10MB)')
                                                ->columnSpan(1),
                                        ]),

                                    Textarea::make('self_pickup_remark')
                                        ->label('Self Pick-up Remark')
                                        ->placeholder('Optional remarks about the self pick-up (e.g., Who collected, any special notes)')
                                        ->maxLength(500)
                                        ->rows(4)
                                        ->columnSpanFull()
                                        ->helperText('Optional notes about the self pick-up process')
                                        ->extraAlpineAttributes([
                                            'x-on:input' => '
                                                const start = $el.selectionStart;
                                                const end = $el.selectionEnd;
                                                const value = $el.value;
                                                $el.value = value.toUpperCase();
                                                $el.setSelectionRange(start, end);
                                            '
                                        ])
                                        ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                ]),
                        ])
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            try {
                                // Get existing category2 data
                                $existingCategory2 = $record->category2 ? json_decode($record->category2, true) : [];

                                // Ensure it's an array
                                if (!is_array($existingCategory2)) {
                                    $existingCategory2 = [];
                                }

                                // Add self pick-up completion data to category2
                                $existingCategory2['self_pickup_date'] = $data['self_pickup_date'];
                                $existingCategory2['delivery_order'] = $data['delivery_order'];
                                $existingCategory2['self_pickup_remark'] = $data['self_pickup_remark'] ?? '';

                                // Add completion metadata
                                $existingCategory2['self_pickup_completed'] = true;
                                $existingCategory2['self_pickup_completed_at'] = now()->toISOString();
                                $existingCategory2['self_pickup_completed_by'] = auth()->id();

                                // Update the record with category2 data and new status
                                $record->update([
                                    'category2' => json_encode($existingCategory2),
                                    'status' => 'Completed: Self Pick-Up',
                                    'completed_at' => now(),
                                ]);

                                Log::info("Self pick-up completed for handover {$record->id}", [
                                    'user_id' => auth()->id(),
                                    'self_pickup_date' => $data['self_pickup_date'],
                                    'delivery_order' => $data['delivery_order'],
                                    'self_pickup_remark' => $data['self_pickup_remark'] ?? '',
                                ]);

                                Notification::make()
                                    ->title('Self Pick-up Completed')
                                    ->body('Self pick-up has been completed successfully on ' .
                                        Carbon::parse($data['self_pickup_date'])->format('d M Y'))
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Log::error("Error completing self pick-up for handover {$record->id}: " . $e->getMessage());

                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to complete self pick-up. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            in_array($record->status, ['Pending Customer: Self Pick-Up']) &&
                            auth()->user()->role_id !== 2
                        ),

                ])->button()
            ]);
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-pending-customer-self-pick-up-table');
    }
}
