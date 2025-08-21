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
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class TechnicianNew extends Component implements HasForms, HasTable
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
            ->where('status', 'New')
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
                        return 'OR_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
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

                    Action::make('accept_repair')
                        ->label('Accept Repair')
                        ->modalWidth('5xl')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (AdminRepair $record): bool => $record->status === 'New' && auth()->user()->role_id !== 1)
                        ->form([
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
                                                    ->disabled() // Make the field read-only
                                                    ->required(),

                                                TextInput::make('device_serial')
                                                    ->label('Serial Number')
                                                    ->columnSpan(1)
                                                    ->disabled() // Make the field read-only
                                            ]),

                                        Select::make('spare_parts')
                                            ->label('Spare Parts Required')
                                            ->allowHtml()
                                            ->searchable()
                                            ->multiple()
                                            ->preload()
                                            ->optionsLimit(50)
                                            ->loadingMessage('Loading spare parts...')
                                            ->noSearchResultsMessage('No spare parts found')
                                            ->hintAction(function (Select $component) {
                                                // Import the correct Action class at the top of your file
                                                return \Filament\Forms\Components\Actions\Action::make('select_all')
                                                    ->label('Select All Parts for this Model')
                                                    ->icon('heroicon-o-check-circle')
                                                    ->action(function (callable $get) use ($component) {
                                                        // Fix: Access the device model directly from the state path
                                                        $statePath = $component->getContainer()->getParentComponent()->getStatePath();
                                                        $deviceModelPath = $statePath . '.device_model';

                                                        // Use Livewire's get() method instead of evaluate()
                                                        $deviceModel = $get('device_model');

                                                        if (empty($deviceModel)) {
                                                            Notification::make()
                                                                ->title('No device model selected')
                                                                ->warning()
                                                                ->send();
                                                            return;
                                                        }

                                                        // Get all spare part IDs for this specific device model only
                                                        $spareParts = SparePart::where('is_active', true)
                                                            ->where('device_model', $deviceModel)
                                                            ->pluck('id')
                                                            ->toArray();

                                                        // Set the state to include only parts for this device model
                                                        $component->state($spareParts);

                                                        Notification::make()
                                                            ->title("Selected all parts for {$deviceModel}")
                                                            ->success()
                                                            ->send();
                                                    });
                                            })
                                            ->options(function (callable $get) {
                                                // Get the selected device model for this repeater item
                                                $deviceModel = $get('device_model');

                                                // If no model is selected, return empty array
                                                if (empty($deviceModel)) {
                                                    return [];
                                                }

                                                // Query spare parts for this specific model
                                                $spareParts = \App\Models\SparePart::where('is_active', true)
                                                    ->where('device_model', $deviceModel)
                                                    ->orderBy('name')
                                                    ->limit(100)
                                                    ->get();

                                                // Format the options
                                                return $spareParts->mapWithKeys(function ($part) {
                                                    return [$part->id => static::getSparePartOptionHtml($part)];
                                                })->toArray();
                                            })
                                            ->getSearchResultsUsing(function (string $search, callable $get) {
                                                // Get the selected device model for this item
                                                $deviceModel = $get('device_model');

                                                // If no model is selected, return empty array
                                                if (empty($deviceModel)) {
                                                    return [];
                                                }

                                                // Search for parts matching this model and search term
                                                $spareParts = \App\Models\SparePart::where('is_active', true)
                                                    ->where('device_model', $deviceModel)
                                                    ->where(function ($query) use ($search) {
                                                        $query->where('name', 'like', "%{$search}%")
                                                            ->orWhere('autocount_code', 'like', "%{$search}%");
                                                    })
                                                    ->orderBy('name')
                                                    ->limit(50)
                                                    ->get();

                                                // Format the results
                                                return $spareParts->mapWithKeys(function ($part) {
                                                    return [$part->id => static::getSparePartOptionHtml($part)];
                                                })->toArray();
                                            })
                                            ->getOptionLabelUsing(function ($value) {
                                                // Get clean label for selected value
                                                $part = \App\Models\SparePart::find($value);
                                                if (!$part) return null;

                                                // Return name and device model
                                                return "{$part->name} ({$part->device_model})";
                                            })
                                            ->disabled(fn (callable $get) => empty($get('device_model')))
                                            ->optionsLimit(100),

                                        // Repeater for repair remarks - similar to your example
                                        Repeater::make('repair_remarks')
                                            ->label('Repair Remarks')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        Textarea::make('remark')
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => Str::upper($state))
                                                            ->afterStateUpdated(fn($state) => Str::upper($state))
                                                            ->hiddenLabel(true)
                                                            ->label(function (?string $state, $livewire) {
                                                                // Get the current array key from the state path
                                                                $statePath = $livewire->getFormStatePath();
                                                                $matches = [];
                                                                if (preg_match('/repair_remarks\.(\d+)\./', $statePath, $matches)) {
                                                                    $index = (int) $matches[1];
                                                                    return 'Remark ' . ($index + 1);
                                                                }

                                                                return 'Repair Assessment';
                                                            })
                                                            ->placeholder('Enter repair assessment here')
                                                            ->autosize()
                                                            ->rows(3)
                                                            ->required(),

                                                        FileUpload::make('attachments')
                                                            ->hiddenLabel(true)
                                                            ->disk('public')
                                                            ->directory('repair-attachments/assessments')
                                                            ->visibility('public')
                                                            ->multiple()
                                                            ->maxFiles(3)
                                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                                            ->openable()
                                                            ->downloadable()
                                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, AdminRepair $record): string {
                                                                $repairId = 'OR_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                                                                $extension = $file->getClientOriginalExtension();
                                                                $timestamp = now()->format('YmdHis');
                                                                $random = rand(1000, 9999);

                                                                return "{$repairId}-assessment-{$timestamp}-{$random}.{$extension}";
                                                            }),
                                                    ])
                                            ])
                                            ->itemLabel(function (array $state, callable $get) {
                                                static $counter = 1;
                                                return 'Remark ' . $counter++;
                                            })
                                            // ->addActionLabel('Add Remark')
                                            ->addable(false)
                                            ->maxItems(5)
                                            ->defaultItems(1)
                                            ->default([['remark' => '', 'attachments' => []]]),
                                    ])
                                    ->itemLabel(function (array $state): ?string {
                                        $label = $state['device_model'] ?? 'Device';
                                        if (!empty($state['device_serial'])) {
                                            $label .= ' (SN: ' . $state['device_serial'] . ')';
                                        }
                                        return $label;
                                    })
                                    ->default(function (AdminRepair $record) {
                                        // Create a default item for each device in the repair record
                                        $items = [];

                                        if ($record->devices) {
                                            $devices = is_string($record->devices)
                                                ? json_decode($record->devices, true)
                                                : $record->devices;

                                            if (is_array($devices)) {
                                                foreach ($devices as $device) {
                                                    if (!empty($device['device_model'])) {
                                                        $items[] = [
                                                            'device_model' => $device['device_model'],
                                                            'device_serial' => $device['device_serial'] ?? 'N/A', // Include serial number
                                                            'repair_remarks' => [
                                                                [
                                                                    'remark' => '',
                                                                    'attachments' => []
                                                                ]
                                                            ],
                                                            'spare_parts' => []
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                        // Handle legacy format
                                        elseif ($record->device_model) {
                                            $items[] = [
                                                'device_model' => $record->device_model,
                                                'device_serial' => $record->device_serial ?? 'N/A',
                                                'repair_remarks' => [
                                                    [
                                                        'remark' => '',
                                                        'attachments' => []
                                                    ]
                                                ],
                                                'spare_parts' => []
                                            ];
                                        }

                                        // Return the items array or create a default empty structure if it's empty
                                        return !empty($items) ? $items : [];
                                    })
                                    ->addable(false)
                                    ->deletable(false)
                                    ->columns(1)
                            ]),
                        ])
                        ->action(function (AdminRepair $record, array $data): void {
                            // Update repair record with status
                            $record->update([
                                'status' => 'Accepted',
                                'updated_by' => auth()->id(),
                            ]);

                            // Process the device repair assessments
                            $updatedRemarks = [];
                            $sparePartsNeeded = [];

                            // Get the original devices from the record for reference
                            $originalDevices = [];
                            if ($record->devices) {
                                $devices = is_string($record->devices) ? json_decode($record->devices, true) : $record->devices;
                                if (is_array($devices)) {
                                    $originalDevices = $devices;
                                }
                            } elseif ($record->device_model) {
                                $originalDevices[] = [
                                    'device_model' => $record->device_model,
                                    'device_serial' => $record->device_serial ?? 'N/A'
                                ];
                            }

                            // Process each device repair
                            if (!empty($data['device_repairs']) && is_array($data['device_repairs'])) {
                                foreach ($data['device_repairs'] as $index => $deviceRepair) {
                                    // Get the corresponding original device (matched by index)
                                    $originalDevice = $originalDevices[$index] ?? null;

                                    // If we don't have a corresponding device, skip this repair
                                    if (!$originalDevice || empty($originalDevice['device_model'])) {
                                        continue;
                                    }

                                    $deviceModel = $originalDevice['device_model'];
                                    $deviceSerial = $originalDevice['device_serial'] ?? 'Unknown';
                                    $spareParts = [];
                                    $deviceRemarks = [];

                                    // Process spare parts for this device
                                    if (!empty($deviceRepair['spare_parts']) && is_array($deviceRepair['spare_parts'])) {
                                        foreach ($deviceRepair['spare_parts'] as $partId) {
                                            $part = \App\Models\SparePart::find($partId);
                                            if ($part) {
                                                // Add part details to the spare parts array for this device
                                                $spareParts[] = [
                                                    'part_id' => $partId,
                                                    'name' => $part->name,
                                                    'code' => $part->autocount_code ?? '',
                                                ];

                                                // Also maintain the original structure for backwards compatibility
                                                $sparePartsNeeded[] = [
                                                    'part_id' => $partId,
                                                    'device_model' => $deviceModel
                                                ];
                                            }
                                        }
                                    }

                                    // Process all remarks for this device
                                    if (!empty($deviceRepair['repair_remarks']) && is_array($deviceRepair['repair_remarks'])) {
                                        foreach ($deviceRepair['repair_remarks'] as $remarkItem) {
                                            if (isset($remarkItem['remark']) && !empty($remarkItem['remark'])) {
                                                $deviceRemarks[] = [
                                                    'remark' => Str::upper($remarkItem['remark']),
                                                    'attachments' => isset($remarkItem['attachments']) && !empty($remarkItem['attachments'])
                                                        ? $remarkItem['attachments']
                                                        : [],
                                                ];
                                            }
                                        }
                                    }

                                    // Add remark for this device with all remarks and spare parts
                                    $updatedRemarks[] = [
                                        'device_model' => $deviceModel,
                                        'device_serial' => $deviceSerial,
                                        'remarks' => $deviceRemarks,
                                        'spare_parts' => $spareParts,
                                    ];
                                }
                            }

                            // Update the repair record with the structured remarks and spare parts
                            $record->update([
                                'repair_remark' => json_encode($updatedRemarks),
                                'spare_parts' => json_encode($sparePartsNeeded) // Keep this for backwards compatibility
                            ]);

                            // Send email notification
                            try {
                                // Format repair ID
                                $repairId = 'OR_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                                // Get company name
                                $companyName = $record->company_name ?? 'Unknown Company';

                                $repair = $record;

                                // Get technician name
                                $technicianName = auth()->user()->name ?? 'Unknown Technician';

                                $pdfPath = app(\App\Http\Controllers\GenerateRepairHandoverPdfController::class)->generateInBackground($repair);
                                $pdfUrl = $pdfPath ? url('storage/' . $pdfPath) : null;

                                // Get submitted by info (from the created_by field)
                                $submittedBy = null;
                                if ($record->created_by) {
                                    $creator = \App\Models\User::find($record->created_by);
                                    $submittedBy = $creator ? $creator->name : 'System User';
                                } else {
                                    $submittedBy = 'System User';
                                }

                                // Email data structure
                                $emailData = [
                                    'repair_id' => $repairId,
                                    'company_name' => $companyName,
                                    'technician' => $technicianName,
                                    'status' => 'Accepted',
                                    'accepted_at' => now()->format('d M Y, h:i A'),
                                    'submitted_by' => $submittedBy,
                                    'submitted_at' => $record->created_at ? $record->created_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A'),
                                    'device_repairs' => [],
                                    'pdf_url' => $pdfUrl,
                                ];

                                // Process device repairs for email
                                foreach ($updatedRemarks as $deviceRepair) {
                                    $deviceModel = $deviceRepair['device_model'];

                                    // Combine all remarks into one text for email
                                    $remarkText = '';
                                    if (!empty($deviceRepair['remarks'])) {
                                        foreach ($deviceRepair['remarks'] as $index => $remark) {
                                            $remarkText .= ($index + 1) . '. ' . $remark['remark'] . "\n\n";
                                        }
                                    }

                                    if (empty($remarkText)) {
                                        $remarkText = 'No assessment provided';
                                    }

                                    // Get spare parts details
                                    $spareParts = [];
                                    if (!empty($deviceRepair['spare_parts'])) {
                                        foreach ($deviceRepair['spare_parts'] as $sparePartData) {
                                            if (isset($sparePartData['part_id'])) {
                                                $part = \App\Models\SparePart::find($sparePartData['part_id']);
                                                if ($part) {
                                                    // Format image URL if available
                                                    $imageUrl = null;
                                                    if ($part->picture_url) {
                                                        $imageUrl = $part->picture_url;
                                                        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                                            if (str_starts_with($imageUrl, 'storage/')) {
                                                                $imageUrl = url($imageUrl);
                                                            } else {
                                                                $imageUrl = url('storage/' . $imageUrl);
                                                            }
                                                        }
                                                    }

                                                    $spareParts[] = [
                                                        'name' => $part->name,
                                                        'model' => $part->device_model,
                                                        'image_url' => $imageUrl,
                                                    ];
                                                }
                                            }
                                        }
                                    }

                                    // Add this device repair to the email data
                                    $emailData['device_repairs'][] = [
                                        'device_model' => $deviceModel,
                                        'device_serial' => $deviceRepair['device_serial'] ?? 'N/A',
                                        'assessment' => $remarkText,
                                        'spare_parts' => $spareParts
                                    ];
                                }

                                // Recipients
                                $recipients = [
                                    'admin.timetec.hr@timeteccloud.com',
                                    'izzuddin@timeteccloud.com',
                                ];

                                // Send the email
                                \Illuminate\Support\Facades\Mail::send(
                                    'emails.repair_status_changed',
                                    $emailData,
                                    function ($message) use ($recipients, $repairId, $companyName) {
                                        $message->from(auth()->user()->email, auth()->user()->name);
                                        $message->to($recipients);
                                        $message->subject("REPAIR HANDOVER ID {$repairId} | {$companyName}");
                                    }
                                );
                            } catch (\Exception $e) {
                                // Log error but don't stop the process
                                \Illuminate\Support\Facades\Log::error("Failed to send repair assessment email: " . $e->getMessage());
                            }

                            // Show success notification
                            Notification::make()
                                ->title('Repair assessment completed')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Accept Repair Handover')
                        ->modalSubmitActionLabel('Accept Repair'),
                ])->button()
            ]);
    }

    protected static function getSparePartOptionHtml(\App\Models\SparePart $part): string
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
                </div>
            </div>
        ';
        // return '
        //     <div class="flex items-center w-full gap-2">
        //         <div class="flex-shrink-0 w-8 h-8">
        //             <img src="' . e($imageUrl) . '" class="object-cover w-full h-full rounded"
        //                 onerror="this.onerror=null; this.src=\'' . e(url('images/no-image.jpg')) . '\'" />
        //         </div>
        //         <div class="flex-grow truncate">
        //             <div class="font-medium truncate">' . e($part->name) . '</div>
        //             <div class="text-xs text-gray-500 truncate">' . e($part->device_model) . '</div>
        //         </div>
        //         <div class="flex-shrink-0">
        //             <button type="button"
        //                 onclick="event.stopPropagation(); window.open(\'' . e($fullImageUrl) . '\', \'_blank\'); return false;"
        //                 class="px-1 py-1 text-xs rounded text-primary-600 hover:text-primary-800">
        //                 <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        //                     <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        //                     <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        //                 </svg>
        //             </button>
        //         </div>
        //     </div>
        // ';
    }

    public function render()
    {
        return view('livewire.technician-new');
    }
}
