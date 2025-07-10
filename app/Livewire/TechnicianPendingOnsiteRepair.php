<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateRepairPdfController;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\AdminRepair;
use App\Models\SparePart;
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
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
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
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class TechnicianPendingOnsiteRepair extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

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
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
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
                    ->searchable()
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
                    ->sortable()
                    ->searchable(),

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
                    ->label('Status'),
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
                    Action::make('completeRepair')
                        ->label('Complete Repair')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalWidth('5xl')
                        ->modalHeading('Complete Onsite Repair')
                        ->form($this->getCompleteRepairForm())
                        ->action(function (AdminRepair $record, array $data): void {
                            DB::beginTransaction();

                            try {
                                // Debug incoming data
                                Log::info('Complete Repair Form Data', [
                                    'hasDeviceRepairs' => isset($data['device_repairs']),
                                    'deviceRepairsCount' => isset($data['device_repairs']) ? count($data['device_repairs']) : 0,
                                    'dataKeys' => array_keys($data)
                                ]);

                                // Validate device_repairs data
                                if (!isset($data['device_repairs']) || !is_array($data['device_repairs']) || empty($data['device_repairs'])) {
                                    throw new \Exception('No device repair data was provided');
                                }

                                // Fix missing device models if needed
                                foreach ($data['device_repairs'] as $index => &$repair) {
                                    // Check for missing device model
                                    if (!isset($repair['device_model']) || empty($repair['device_model'])) {
                                        Log::warning("Found missing device_model at index {$index}, setting default");
                                        $repair['device_model'] = 'Generic Device ' . ($index + 1);
                                    }

                                    Log::info("Device Repair {$index} data", [
                                        'model' => $repair['device_model'],
                                        'serial' => $repair['device_serial'] ?? 'N/A',
                                        'usedParts' => isset($repair['used_spare_parts']) ? count($repair['used_spare_parts']) : 0,
                                        'unusedParts' => isset($repair['unused_spare_parts']) ? count($repair['unused_spare_parts']) : 0
                                    ]);
                                }

                                // Process spare parts for each device
                                $finalSpareParts = [];
                                $unusedSpareParts = [];

                                // Update repair record with completion data
                                $this->updateRepairRecord($record, $data, $finalSpareParts, $unusedSpareParts);

                                // Update appointment status if exists
                                $this->updateRelatedAppointment($record, $data);

                                // Log activity
                                $this->logRepairActivity($record);

                                $this->sendCompletionNotificationEmail($record);

                                DB::commit();

                                // Show success notification
                                Notification::make()
                                    ->title('Repair Completed')
                                    ->success()
                                    ->body('The onsite repair has been successfully completed')
                                    ->send();

                                // Refresh tables
                                $this->dispatch('refresh-adminrepair-tables');

                            } catch (\Exception $e) {
                                DB::rollBack();

                                // Log error
                                Log::error("Error completing repair: " . $e->getMessage());

                                // Show error notification
                                Notification::make()
                                    ->title('Error Completing Repair')
                                    ->danger()
                                    ->body('An error occurred while processing your request: ' . $e->getMessage())
                                    ->send();
                            }
                        }),
                ])->button(),
            ]);
    }

    protected function getCompleteRepairForm(): array
    {
        return [
            Section::make('Repair Assessment')
                ->schema([
                    Repeater::make('device_repairs')
                        ->label('Device Repairs')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('device_model')
                                        ->label('Device Model')
                                        ->columnSpan(1)
                                        ->disabled()
                                        ->required()
                                        ->rules(['required', 'string', 'min:2']),

                                    TextInput::make('device_serial')
                                        ->label('Serial Number')
                                        ->columnSpan(1)
                                        ->disabled()
                                ]),

                            Select::make('unused_spare_parts')
                                ->label('Spare Parts Not Used (Select to remove)')
                                ->allowHtml()
                                ->searchable()
                                ->multiple()
                                ->preload()
                                ->columnSpanFull() // Ensure full width for better visibility
                                ->helperText('Select spare parts that were NOT actually used during the repair')
                                ->reactive()
                                ->options(function (Get $get, AdminRepair $record) {
                                    // Get the original parts from the repair record
                                    $originalParts = $get('used_spare_parts_original') ?? [];

                                    if (empty($originalParts)) {
                                        return [];
                                    }

                                    // Get spare part details from the database
                                    $spareParts = SparePart::whereIn('id', $originalParts)->get();

                                    return $spareParts->mapWithKeys(function ($part) {
                                        return [$part->id => $this->getSparePartOptionHtml($part)];
                                    })->toArray();
                                })
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    // When unused_spare_parts changes, update used_spare_parts accordingly
                                    $originalParts = $get('used_spare_parts_original') ?? [];
                                    $currentUnusedParts = is_array($state) ? $state : [];

                                    // Calculate which parts should be shown as "used" (original minus unused)
                                    $newUsedParts = array_values(array_diff($originalParts, $currentUnusedParts));

                                    // Update used_spare_parts with new value
                                    $set('used_spare_parts', $newUsedParts);
                                }),

                            // Then update the used_spare_parts select to properly show only actually used parts
                            Select::make('used_spare_parts')
                                ->label('Spare Parts Used')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->disableOptionWhen(fn ($value, $label, $state) => true) // Make all options non-deletable
                                ->disabled() // Make the entire select field non-editable
                                ->columnSpanFull() // Ensure full width for better visibility
                                ->options(function (Get $get) {
                                    // Get only the parts that are currently marked as used
                                    $usedParts = $get('used_spare_parts') ?? [];
                                    $unusedParts = $get('unused_spare_parts') ?? [];

                                    // Make sure we're only showing actually used parts (not in unused list)
                                    $actuallyUsedParts = array_diff($usedParts, $unusedParts);

                                    if (empty($actuallyUsedParts)) {
                                        return [];
                                    }

                                    try {
                                        // Get part details for display
                                        $parts = SparePart::whereIn('id', $actuallyUsedParts)
                                            ->get()
                                            ->mapWithKeys(function ($part) {
                                                $displayName = $part->name . ' (' . ($part->autocount_code ?? $part->device_model ?? 'N/A') . ')';
                                                return [$part->id => $displayName];
                                            })
                                            ->toArray();

                                        return $parts;
                                    } catch (\Exception $e) {
                                        Log::error("Error fetching spare parts: " . $e->getMessage());
                                        return [];
                                    }
                                })
                                ->reactive() // Make it reactive to changes
                                ->dehydrated(true),

                            // Make sure we keep the original parts list in the hidden field
                            Hidden::make('used_spare_parts_original')
                                ->default(function (Get $get, AdminRepair $record) {
                                    $deviceModel = $get('device_model');

                                    // If no device model, return empty array
                                    if (!$deviceModel) {
                                        return [];
                                    }

                                    // Get spare parts from the repair record's spare_parts column
                                    try {
                                        if ($record->spare_parts) {
                                            $spareParts = is_string($record->spare_parts)
                                                ? json_decode($record->spare_parts, true)
                                                : $record->spare_parts;

                                            // Filter parts for this specific device model
                                            $deviceParts = array_filter($spareParts, function($part) use ($deviceModel) {
                                                return isset($part['device_model']) &&
                                                    strtoupper(trim($part['device_model'])) === strtoupper(trim($deviceModel));
                                            });

                                            // Extract just the part_id values
                                            return array_map(function($part) {
                                                return (int)$part['part_id'];
                                            }, $deviceParts);
                                        }

                                        return [];
                                    } catch (\Exception $e) {
                                        Log::error("Failed to get spare parts from record: " . $e->getMessage());
                                        return [];
                                    }
                                })
                                ->dehydrated(true),
                        ])
                        ->itemLabel(function (array $state): ?string {
                            $label = $state['device_model'] ?? 'Device';
                            if (!empty($state['device_serial'])) {
                                $label .= ' (SN: ' . $state['device_serial'] . ')';
                            }
                            return $label;
                        })
                        ->default(function (AdminRepair $record) {
                            return $this->getDefaultDeviceRepairs($record);
                        })
                        ->addable(false)
                        ->deletable(false)
                        ->columns(1)
                ]),

            Section::make('Documents Upload')
                ->description('Upload required documents for repair completion')
                ->schema([
                    Grid::make(2)
                    ->schema([
                        FileUpload::make('delivery_order')
                            ->label('Delivery Order')
                            ->disk('public')
                            ->directory('repairs/delivery_orders')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->multiple()
                            ->maxFiles(5)
                            ->required(),

                        FileUpload::make('repair_form')
                            ->label('Repair Form')
                            ->disk('public')
                            ->directory('repairs/repair_forms')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->multiple()
                            ->maxFiles(5)
                    ]),
                ]),

            Section::make('Additional Information')
                ->schema([
                    Grid::make(2)
                    ->schema([
                        Textarea::make('onsite_repair_remark')
                            ->label('Onsite Repair Remark')
                            ->placeholder('Enter any additional notes about the repair completion')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->afterStateHydrated(fn($state) => Str::upper($state))
                            ->afterStateUpdated(fn($state) => Str::upper($state))
                            ->rows(3),

                        FileUpload::make('repair_attachments')
                            ->label('Repair Images & Attachments')
                            ->disk('public')
                            ->directory('repairs/repair_images')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'application/pdf'])
                            ->multiple()
                            ->maxFiles(10)
                    ]),
                ])
        ];
    }

    protected function getDefaultDeviceRepairs(AdminRepair $record): array
    {
        // Create a default item for each device in the repair record
        $items = [];

        // Debug start with more details
        Log::info("getDefaultDeviceRepairs for record ID: {$record->id}", [
            'has_repair_remark' => !empty($record->repair_remark),
            'has_devices' => !empty($record->devices),
            'device_model' => $record->device_model ?? 'N/A',
            'record_data' => [
                'id' => $record->id,
                'status' => $record->status,
                'devices_json' => is_string($record->devices) ? substr($record->devices, 0, 100) : 'Not string'
            ]
        ]);

        // First check repair_remark field
        if (!empty($record->repair_remark)) {
            try {
                $parsedRemarks = json_decode($record->repair_remark, true);

                if (is_array($parsedRemarks)) {
                    foreach ($parsedRemarks as $index => $deviceRemark) {
                        if (isset($deviceRemark['device_model']) && !empty($deviceRemark['device_model'])) {
                            // Extract spare parts
                            $spareParts = [];
                            if (!empty($deviceRemark['spare_parts']) && is_array($deviceRemark['spare_parts'])) {
                                foreach ($deviceRemark['spare_parts'] as $part) {
                                    if (isset($part['part_id'])) {
                                        $spareParts[] = (int)$part['part_id'];
                                    }
                                }
                            }

                            // Get additional spare parts from the database if needed
                            if (empty($spareParts)) {
                                try {
                                    $spareParts = SparePart::where('is_active', true)
                                        ->where('device_model', $deviceRemark['device_model'])
                                        ->pluck('id')
                                        ->toArray();
                                } catch (\Exception $e) {
                                    Log::warning("Failed to get spare parts: " . $e->getMessage());
                                }
                            }

                            // Add device with verified model
                            $items[] = [
                                'device_model' => trim($deviceRemark['device_model']),
                                'device_serial' => $deviceRemark['device_serial'] ?? 'N/A',
                                'used_spare_parts' => $spareParts,
                                'unused_spare_parts' => [],
                                'repair_remarks' => $this->extractRemarks($deviceRemark)
                            ];

                            Log::info("Added device from repair_remark", [
                                'index' => $index,
                                'model' => trim($deviceRemark['device_model']),
                                'serial' => $deviceRemark['device_serial'] ?? 'N/A',
                                'spareParts' => count($spareParts)
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error parsing repair_remark: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // If no items from repair_remarks, check devices array
        if (empty($items) && !empty($record->devices)) {
            try {
                $devices = is_string($record->devices)
                    ? json_decode($record->devices, true)
                    : (is_array($record->devices) ? $record->devices : null);

                if (is_array($devices)) {
                    foreach ($devices as $index => $device) {
                        if (!empty($device['device_model'])) {
                            $deviceModel = trim($device['device_model']);

                            // Get spare parts for this model from database
                            $spareParts = [];
                            try {
                                $spareParts = SparePart::where('is_active', true)
                                    ->where('device_model', $deviceModel)
                                    ->pluck('id')
                                    ->toArray();
                            } catch (\Exception $e) {
                                Log::warning("Failed to get spare parts for model {$deviceModel}: " . $e->getMessage());
                            }

                            $items[] = [
                                'device_model' => $deviceModel,
                                'device_serial' => $device['device_serial'] ?? 'N/A',
                                'used_spare_parts' => $spareParts,
                                'unused_spare_parts' => [],
                                'repair_remarks' => ''
                            ];

                            Log::info("Added device from devices array", [
                                'index' => $index,
                                'model' => $deviceModel,
                                'serial' => $device['device_serial'] ?? 'N/A',
                                'spareParts' => count($spareParts)
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error parsing devices: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        if (!empty($record->spare_parts)) {
            try {
                $spareParts = is_string($record->spare_parts)
                    ? json_decode($record->spare_parts, true)
                    : $record->spare_parts;

                if (is_array($spareParts)) {
                    // Filter parts for this specific device model
                    $deviceParts = array_filter($spareParts, function($part) use ($deviceModel) {
                        return isset($part['device_model']) &&
                            strtoupper(trim($part['device_model'])) === strtoupper(trim($deviceModel));
                    });

                    // Extract just the part_id values
                    $devicePartIds = array_map(function($part) {
                        return (int)$part['part_id'];
                    }, $deviceParts);

                    if (!empty($devicePartIds)) {
                        // Use these part IDs instead of querying the database
                        $spareParts = $devicePartIds;
                        Log::info("Using spare parts from repair record", [
                            'count' => count($spareParts),
                            'model' => $deviceModel
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error parsing spare_parts: " . $e->getMessage());
            }
        }

        // Handle legacy format as last resort
        if (empty($items) && !empty($record->device_model)) {
            $deviceModel = trim($record->device_model);

            // Get spare parts for this model
            $spareParts = [];
            try {
                $spareParts = SparePart::where('is_active', true)
                    ->where('device_model', $deviceModel)
                    ->pluck('id')
                    ->toArray();
            } catch (\Exception $e) {
                Log::warning("Failed to get spare parts for legacy model {$deviceModel}: " . $e->getMessage());
            }

            $items[] = [
                'device_model' => $deviceModel,
                'device_serial' => $record->device_serial ?? 'N/A',
                'used_spare_parts' => $spareParts,
                'unused_spare_parts' => [],
                'repair_remarks' => ''
            ];

            Log::info("Added device from legacy format", [
                'model' => $deviceModel,
                'serial' => $record->device_serial ?? 'N/A',
                'spareParts' => count($spareParts)
            ]);
        }

        // Always ensure we have at least one item with a valid device model
        if (empty($items)) {
            Log::warning("No device information found, using fallback with generic device");
            $items[] = [
                'device_model' => 'Generic Device',  // Changed from 'Unknown Device' to ensure a non-empty value
                'device_serial' => 'N/A',
                'used_spare_parts' => [],
                'unused_spare_parts' => [],
                'repair_remarks' => ''
            ];
        }

        // Double-check all items have a device_model value before returning
        foreach ($items as $index => &$item) {
            if (empty($item['device_model'])) {
                $item['device_model'] = 'Generic Device ' . ($index + 1);
                Log::warning("Found empty device_model at index {$index}, setting to generic name");
            }
        }

        Log::info("Returning device repairs", [
            'count' => count($items),
            'first_item_model' => $items[0]['device_model'] ?? 'NONE'
        ]);

        return $items;
    }

    // Helper function to extract remarks
    private function extractRemarks(array $deviceRemark): string
    {
        $remarks = '';
        if (!empty($deviceRemark['remarks']) && is_array($deviceRemark['remarks'])) {
            $remarkTexts = [];
            foreach ($deviceRemark['remarks'] as $remark) {
                if (!empty($remark['remark'])) {
                    $remarkTexts[] = $remark['remark'];
                }
            }
            $remarks = implode("\n", $remarkTexts);
        }
        return $remarks;
    }

    protected function updateRepairRecord(AdminRepair $record, array $data, array &$finalSpareParts, array &$unusedSpareParts): void
    {
        // Debug logging
        Log::info('Starting updateRepairRecord method', ['data' => array_keys($data)]);

        // Process the device repairs first to populate $finalSpareParts and $unusedSpareParts
        if (!empty($data['device_repairs']) && is_array($data['device_repairs'])) {
            Log::info('Processing device repairs', ['count' => count($data['device_repairs'])]);

            foreach ($data['device_repairs'] as $index => $deviceRepair) {
                // Ensure all required keys exist
                if (!isset($deviceRepair['device_model']) || empty($deviceRepair['device_model'])) {
                    Log::warning("Missing or empty device_model in repair index {$index}");
                    continue; // Skip this device if device_model is missing
                }

                $deviceModel = $deviceRepair['device_model'];
                $deviceSerial = $deviceRepair['device_serial'] ?? 'N/A';

                Log::info("Processing device", [
                    'index' => $index,
                    'model' => $deviceModel,
                    'serial' => $deviceSerial
                ]);

                // Get used and unused spare parts - ensure they're arrays
                $usedParts = isset($deviceRepair['used_spare_parts']) ? (is_array($deviceRepair['used_spare_parts']) ? $deviceRepair['used_spare_parts'] : []) : [];
                $notUsedParts = isset($deviceRepair['unused_spare_parts']) ? (is_array($deviceRepair['unused_spare_parts']) ? $deviceRepair['unused_spare_parts'] : []) : [];

                // If there are no used parts at all, try to get them from the model
                if (empty($usedParts)) {
                    try {
                        Log::info("No used parts found, fetching from database for model: {$deviceModel}");
                        $usedParts = SparePart::where('is_active', true)
                            ->where('device_model', $deviceModel)
                            ->pluck('id')
                            ->toArray();
                    } catch (\Exception $e) {
                        Log::error("Failed to get spare parts for model {$deviceModel}: " . $e->getMessage());
                    }
                }

                Log::info("Spare parts counts", [
                    'used' => count($usedParts),
                    'notUsed' => count($notUsedParts)
                ]);

                // Make sure we have integers, not strings
                $usedParts = array_map('intval', array_filter($usedParts));
                $notUsedParts = array_map('intval', array_filter($notUsedParts));

                // Process actually used parts (those in used_spare_parts but not in unused_spare_parts)
                $actuallyUsedParts = array_diff($usedParts, $notUsedParts);

                Log::info("Actually used parts count", ['count' => count($actuallyUsedParts)]);

                foreach ($actuallyUsedParts as $partId) {
                    try {
                        $part = SparePart::find($partId);

                        if ($part) {
                            // Add to final spare parts list
                            $finalSpareParts[] = [
                                'part_id' => $partId,
                                'part_name' => $part->name ?? 'Unknown Part',
                                'device_model' => $deviceModel,
                                'device_serial' => $deviceSerial,
                            ];

                            Log::info("Added part to finalSpareParts", [
                                'part_id' => $partId,
                                'part_name' => $part->name ?? 'Unknown Part'
                            ]);
                        } else {
                            Log::warning("Part not found: {$partId}");
                        }
                    } catch (\Exception $e) {
                        Log::error("Error processing spare part ID {$partId}: " . $e->getMessage());
                    }
                }

                // Process unused parts
                foreach ($notUsedParts as $partId) {
                    try {
                        $part = SparePart::find($partId);

                        if ($part) {
                            // Add to unused parts list
                            $unusedSpareParts[] = [
                                'part_id' => $partId,
                                'part_name' => $part->name ?? 'Unknown Part',
                                'device_model' => $deviceModel,
                                'device_serial' => $deviceSerial,
                            ];

                            Log::info("Added part to unusedSpareParts", [
                                'part_id' => $partId,
                                'part_name' => $part->name ?? 'Unknown Part'
                            ]);
                        } else {
                            Log::warning("Unused part not found: {$partId}");
                        }
                    } catch (\Exception $e) {
                        Log::error("Error processing unused spare part ID {$partId}: " . $e->getMessage());
                    }
                }
            }
        } else {
            Log::warning('No device_repairs data found or not an array');
        }

        // Now update the repair record
        $updateData = [
            'onsite_repair_remark' => $data['onsite_repair_remark'] ?? null,
            'status' => 'Completed',
            'completed_date' => now(),
        ];

        if (!empty($unusedSpareParts)) {
            $updateData['spare_parts_unused'] = json_encode($unusedSpareParts);
            Log::info("Adding spare_parts_unused to updateData", ['count' => count($unusedSpareParts)]);
        } else {
            Log::warning("No unusedSpareParts to save");
            $updateData['spare_parts_unused'] = json_encode([]);
        }

        // Process file uploads
        if (!empty($data['delivery_order'])) {
            $updateData['delivery_order_files'] = json_encode($data['delivery_order']);
        }

        if (!empty($data['repair_form'])) {
            $updateData['repair_form_files'] = json_encode($data['repair_form']);
        }

        // Check if repair_attachments exists instead of repair_images
        if (!empty($data['repair_attachments'])) {
            $updateData['repair_image_files'] = json_encode($data['repair_attachments']);
        }

        // Update repair record
        $record->update($updateData);
    }

    protected function updateRelatedAppointment(AdminRepair $record, array $data): void
    {
        $appointment = \App\Models\RepairAppointment::where('repair_handover_id', $record->id)
            ->where('status', '!=', 'Completed')
            ->latest()
            ->first();

        if ($appointment) {
            $appointment->update([
                'status' => 'Done',
                'completion_date' => now(),
                'completion_remarks' => $data['onsite_repair_remark'] ?? null
            ]);
        }
    }

    protected function logRepairActivity(AdminRepair $record): void
    {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'Completed Onsite Repair',
            'description' => "Completed onsite repair for ID: {$record->id}",
            'subject_type' => \App\Models\AdminRepair::class,
            'subject_id' => $record->id,
        ]);
    }

    protected function getSparePartOptionHtml(SparePart $part): string
    {
        $imageUrl = $part->picture_url ?? url('images/no-image.jpg');
        $fullImageUrl = $imageUrl; // Keep the original URL for the full view

        return '
            <div class="flex items-center w-full gap-2">
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

    protected function sendCompletionNotificationEmail(AdminRepair $record): void
    {
        try {
            // Format the repair ID properly
            $repairId = 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

            // Get company name
            $companyName = $record->company_name ?? 'Unknown Company';

            // Create email content structure
            $emailContent = [
                'repair_id' => $repairId,
                'company' => [
                    'name' => $companyName,
                ],
                'status' => 'Completed',
                'completed_by' => auth()->user()->name ?? 'Unknown',
                'completed_date' => now()->format('d M Y, h:i A'),
                'pdf_url' => $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null,
                'is_completion_notification' => true, // Flag to indicate this is a completion notification
            ];

            // Define recipients
            $recipients = [
                'admin.timetec.hr@timeteccloud.com',
                'izzuddin@timeteccloud.com'
            ];

            // Send email notification
            $authUser = auth()->user();
            \Illuminate\Support\Facades\Mail::send(
                'emails.repair_completion_notification', // Create this view (see below)
                ['emailContent' => $emailContent],
                function ($message) use ($recipients, $authUser, $repairId, $companyName) {
                    $message->from($authUser->email, $authUser->name)
                        ->to($recipients)
                        ->subject("REPAIR HANDOVER ID {$repairId} : COMPLETED");
                }
            );

            // Log success
            \Illuminate\Support\Facades\Log::info("Completion notification sent for repair ticket #{$record->id}");

        } catch (\Exception $e) {
            // Log error but don't stop the process
            \Illuminate\Support\Facades\Log::error("Failed to send completion email for repair #{$record->id}: {$e->getMessage()}");
        }
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
            str_contains($model, 'TIME BEACON') => 1,
            str_contains($model, 'NFC') => 1,
            // Default case
            default => 1,
        };
    }

    public function render()
    {
        return view('livewire.technician-pending-onsite-repair');
    }
}
