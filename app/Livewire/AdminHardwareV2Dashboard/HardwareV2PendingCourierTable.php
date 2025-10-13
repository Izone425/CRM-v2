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

class HardwareV2PendingCourierTable extends Component implements HasForms, HasTable
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
            ->whereIn('status', ['Pending: Courier'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Pending: Courier'])
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
                        'Rejected' => 'Rejected',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Pending Payment' => 'Pending Payment',
                        'Pending: Courier' => 'Pending: Courier',
                        'Completed: Courier' => 'Completed: Courier',
                        'Pending Admin: Self Pick-Up' => 'Pending Admin: Self Pick-Up',
                        'Pending Customer: Self Pick-Up' => 'Pending Customer: Self Pick-Up',
                        'Completed: Self Pick-Up' => 'Completed: Self Pick-Up',
                        'Pending: External Installation' => 'Pending: External Installation',
                        'Completed: External Installation' => 'Completed: External Installation',
                        'Pending: Internal Installation' => 'Pending: Internal Installation',
                        'Completed: Internal Installation' => 'Completed: Internal Installation',
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
                            ->modalHeading(false)
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
                    ->label('Category 1')
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
                        ->modalHeading(false)
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HardwareHandoverV2 $record): View {
                            return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('courier_completion')
                        ->label('Complete Courier')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->modalHeading('Courier Details')
                        ->modalWidth('3xl')
                        ->form(function (HardwareHandoverV2 $record) {
                            // Get courier addresses from category data
                            $courierAddresses = $this->getCourierAddresses($record);

                            return [
                                Section::make('Courier Information')
                                    ->description("Total Courier Addresses: " . count($courierAddresses))
                                    ->collapsible()
                                    ->schema([
                                        // Display the addresses for reference
                                        Grid::make(1)
                                            ->schema([
                                                Textarea::make('addresses_summary')
                                                    ->label('Courier Addresses Summary')
                                                    ->default(function () use ($courierAddresses) {
                                                        return collect($courierAddresses)->map(function ($address, $index) {
                                                            return "Address " . ($index + 1) . ":\n" . $address['address'];
                                                        })->join("\n\n");
                                                    })
                                                    ->disabled()
                                                    ->rows(max(3, count($courierAddresses) * 2))
                                                    ->columnSpanFull()
                                                    ->helperText('These are the courier addresses that need delivery details'),
                                            ]),
                                    ]),

                                Section::make('Courier Details')
                                    ->description('Fill courier information for each address')
                                    ->schema([
                                        Repeater::make('courier_details')
                                            ->label('Courier Details for Each Address')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        Textarea::make('address_info')
                                                            ->label('Courier Address')
                                                            ->disabled()
                                                            ->rows(3)
                                                            ->columnSpanFull()
                                                            ->helperText('This address information is for reference'),

                                                        DatePicker::make('courier_date')
                                                            ->label('Courier Date')
                                                            ->required()
                                                            ->native(false)
                                                            ->displayFormat('d/m/Y')
                                                            ->helperText('Select the courier delivery date'),

                                                        TextInput::make('courier_tracking')
                                                            ->label('Courier Tracking Number')
                                                            ->required()
                                                            ->placeholder('Enter tracking number (e.g., TT123456789MY)')
                                                            ->maxLength(255)
                                                            ->helperText('Courier tracking/reference number (alphanumeric)')
                                                            ->rule('regex:/^[a-zA-Z0-9]+$/'),
                                                    ]),

                                                FileUpload::make('courier_document')
                                                    ->label('Courier Document')
                                                    ->directory('hardware-courier-docs')
                                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                    ->maxSize(10240) // 10MB max
                                                    ->required()
                                                    ->helperText('Upload courier receipt/document (PDF, JPG, PNG - max 10MB)')
                                                    ->columnSpanFull(),

                                                Textarea::make('courier_remark')
                                                    ->label('Courier Remark')
                                                    ->placeholder('Optional remarks about the courier delivery (e.g., Special instructions, delivery notes)')
                                                    ->maxLength(500)
                                                    ->rows(3)
                                                    ->columnSpanFull()
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
                                            ])
                                            ->defaultItems(count($courierAddresses))
                                            ->minItems(count($courierAddresses))
                                            ->maxItems(count($courierAddresses))
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string =>
                                                isset($state['address_info'])
                                                    ? 'Address ' . ((int)filter_var($state['address_info'], FILTER_SANITIZE_NUMBER_INT) ?: 1)
                                                    : 'Courier Address'
                                            )
                                            ->default(function () use ($courierAddresses) {
                                                return collect($courierAddresses)->map(function ($address, $index) {
                                                    return [
                                                        'address_info' => "Address " . ($index + 1) . ":\n" . $address['address']
                                                    ];
                                                })->toArray();
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ];
                        })
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            try {
                                $existingCategory2 = $record->category2 ? json_decode($record->category2, true) : [];

                                if (!is_array($existingCategory2)) {
                                    $existingCategory2 = [];
                                }

                                // Merge courier data into courier addresses in category2
                                if (isset($existingCategory2['courier_addresses']) && is_array($existingCategory2['courier_addresses'])) {
                                    foreach ($data['courier_details'] as $index => $courierData) {
                                        if (isset($existingCategory2['courier_addresses'][$index])) {
                                            // Add courier fields to the existing address object
                                            $existingCategory2['courier_addresses'][$index]['courier_date'] = $courierData['courier_date'];
                                            $existingCategory2['courier_addresses'][$index]['courier_tracking'] = $courierData['courier_tracking'];
                                            $existingCategory2['courier_addresses'][$index]['courier_document'] = $courierData['courier_document'];
                                            $existingCategory2['courier_addresses'][$index]['courier_remark'] = $courierData['courier_remark'] ?? '';
                                        }
                                    }
                                }

                                // Add completion metadata to category2
                                $existingCategory2['courier_completed'] = true;
                                $existingCategory2['courier_completed_at'] = now()->toISOString();
                                $existingCategory2['courier_completed_by'] = auth()->id();

                                // Update the record with merged category data and new status
                                $record->update([
                                    'category2' => json_encode($existingCategory2),
                                    'status' => 'Completed: Courier',
                                    'completed_at' => now(),
                                ]);

                                Log::info("Courier completed for handover {$record->id}", [
                                    'user_id' => auth()->id(),
                                    'courier_details_count' => count($data['courier_details']),
                                    'updated_addresses' => count($existingCategory2['courier_addresses'] ?? []),
                                ]);

                                Notification::make()
                                    ->title('Courier Completed')
                                    ->body('All courier details have been recorded successfully.')
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Log::error("Error saving courier data for handover {$record->id}: " . $e->getMessage());

                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to save courier details. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            $record->status === 'Pending: Courier' && auth()->user()->role_id !== 2
                        ),
                ])->button()
            ]);
    }

    private function getCourierAddresses(HardwareHandoverV2 $record): array
    {
        $courierAddresses = [];

        // Check category2 for courier addresses
        if ($record->category2) {
            try {
                $category2Data = json_decode($record->category2, true);

                Log::info("=== Courier Handover {$record->id} Debug ===");
                Log::info("Raw category2 data: " . $record->category2);
                Log::info("JSON decode result: ", $category2Data ?: ['JSON_DECODE_FAILED']);

                if (is_array($category2Data)) {
                    // Check for courier_addresses array structure
                    if (isset($category2Data['courier_addresses']) && is_array($category2Data['courier_addresses'])) {
                        Log::info("Found courier_addresses array with " . count($category2Data['courier_addresses']) . " items");

                        foreach ($category2Data['courier_addresses'] as $index => $item) {
                            if (isset($item['address']) && !empty($item['address'])) {
                                // Clean up the address (remove escape slashes and format newlines)
                                $cleanAddress = str_replace(['\\/', '\\n'], ['/', "\n"], $item['address']);

                                $courierAddresses[] = [
                                    'address' => $cleanAddress,
                                ];

                                Log::info("Added courier address {$index}: " . $cleanAddress);
                            }
                        }
                    }
                    // Fallback: Check for other possible structures
                    else {
                        Log::info("Available keys in category2Data: " . implode(', ', array_keys($category2Data)));

                        // Check if addresses are stored differently
                        foreach ($category2Data as $key => $item) {
                            if (is_array($item) && isset($item['courier_address']) && !empty($item['courier_address'])) {
                                $courierAddresses[] = [
                                    'address' => $item['courier_address'],
                                ];
                                Log::info("Added fallback courier address from key {$key}: " . $item['courier_address']);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error parsing category 2 data for courier handover {$record->id}: " . $e->getMessage());
            }
        }

        // If no courier addresses found, create a default entry
        if (empty($courierAddresses)) {
            Log::warning("No courier addresses found for handover {$record->id}, using default");
            $courierAddresses[] = [
                'address' => 'Courier Address (Not specified)',
            ];
        }

        Log::info("Final extracted courier addresses for handover {$record->id}: ", $courierAddresses);
        return $courierAddresses;
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-pending-courier-table');
    }
}
