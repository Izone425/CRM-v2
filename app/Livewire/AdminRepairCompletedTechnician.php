<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateRepairHandoverPdfController;
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

class AdminRepairCompletedTechnician extends Component implements HasForms, HasTable
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
            ->where('status', 'Completed Technician Repair')
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
                            ->modalHeading(false)
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
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (AdminRepair $record): View {
                            return view('components.repair-detail')
                                ->with('record', $record);
                        }),
                    Action::make('complete_repair')
                        ->label(fn(): HtmlString => new HtmlString('Mark as Completed <br>Technician Repair'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalHeading('Complete Admin Repair')
                        ->modalWidth('3xl')
                        ->form(function (AdminRepair $record) {
                            // Get device warranty info
                            $deviceWarranty = [];
                            if ($record->devices_warranty) {
                                $deviceWarranty = is_string($record->devices_warranty)
                                    ? json_decode($record->devices_warranty, true)
                                    : $record->devices_warranty;
                            }

                            $schema = [];

                            if (empty($deviceWarranty)) {
                                // Show placeholder if no device warranty info is available
                                $schema[] = Placeholder::make('no_devices')
                                    ->content('No device warranty information available. Please update the repair record first.')
                                    ->extraAttributes(['class' => 'text-center p-4 bg-gray-100 rounded-lg']);
                            } else {
                                foreach ($deviceWarranty as $index => $device) {
                                    $hasWarranty = isset($device['warranty_status']) &&
                                                strtolower($device['warranty_status']) !== 'out of warranty';

                                    $schema[] = Section::make("Device #" . ($index + 1))
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make("devices.{$index}.device_model")
                                                        ->label('Device Model')
                                                        ->default($device['device_model'] ?? '')
                                                        ->disabled(),

                                                    TextInput::make("devices.{$index}.device_serial")
                                                        ->label('Serial Number')
                                                        ->default($device['device_serial'] ?? '')
                                                        ->disabled(),

                                                    TextInput::make("devices.{$index}.warranty_status")
                                                        ->label('Warranty Status')
                                                        ->default($device['warranty_status'] ?? 'Unknown')
                                                        ->disabled()
                                                        ->extraAttributes(function () use ($hasWarranty) {
                                                            return [
                                                                'class' => $hasWarranty
                                                                    ? 'border-green-500 bg-green-50'
                                                                    : 'border-red-500 bg-red-50'
                                                            ];
                                                        }),
                                                ]),

                                            Section::make('Used Spare Parts')
                                                ->schema([
                                                    Placeholder::make("devices.{$index}.used_spare_parts_info")
                                                    ->hiddenLabel()
                                                    ->content(function () use ($record, $index, $device) {
                                                        // Get all spare parts from repair_remark
                                                        $allParts = [];
                                                        $deviceInfo = [];

                                                        // First collect all parts and device info from repair_remark
                                                        if(!empty($record->repair_remark)) {
                                                            $deviceRepairs = is_string($record->repair_remark)
                                                                ? json_decode($record->repair_remark, true)
                                                                : $record->repair_remark;

                                                            if(is_array($deviceRepairs)) {
                                                                foreach($deviceRepairs as $repair) {
                                                                    if(!empty($repair['device_model']) && !empty($repair['spare_parts'])) {
                                                                        foreach($repair['spare_parts'] as $part) {
                                                                            if(!empty($part['part_id'])) {
                                                                                $partId = $part['part_id'];
                                                                                $partName = $part['name'] ?? 'Unknown Part';

                                                                                // Store part in allParts
                                                                                $allParts[$partId] = [
                                                                                    'part_id' => $partId,
                                                                                    'part_name' => $partName,
                                                                                    'device_model' => $repair['device_model'],
                                                                                    'device_serial' => $repair['device_serial'] ?? 'N/A'
                                                                                ];

                                                                                // Also store in deviceInfo for lookup
                                                                                $deviceInfo[$partId] = [
                                                                                    'device_model' => $repair['device_model'],
                                                                                    'device_serial' => $repair['device_serial'] ?? 'N/A'
                                                                                ];
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        // Get unused parts
                                                        $unusedParts = !empty($record->spare_parts_unused)
                                                            ? (is_string($record->spare_parts_unused)
                                                                ? json_decode($record->spare_parts_unused, true)
                                                                : $record->spare_parts_unused)
                                                            : [];

                                                        // Build a lookup for unused parts by part_id
                                                        $unusedPartIds = [];
                                                        if(is_array($unusedParts)) {
                                                            foreach($unusedParts as $part) {
                                                                if(isset($part['part_id'])) {
                                                                    $unusedPartIds[$part['part_id']] = true;
                                                                }
                                                            }
                                                        }

                                                        // Calculate actually used parts (all parts minus unused parts)
                                                        $spareParts = [];
                                                        foreach($allParts as $partId => $part) {
                                                            // Only include if not in unused parts
                                                            if(!isset($unusedPartIds[$partId])) {
                                                                $spareParts[] = $part;
                                                            }
                                                        }

                                                        // If we have specific spare_parts_used data, override with that
                                                        $explicitUsedParts = !empty($record->spare_parts_used)
                                                            ? (is_string($record->spare_parts_used)
                                                                ? json_decode($record->spare_parts_used, true)
                                                                : $record->spare_parts_used)
                                                            : [];

                                                        if(!empty($explicitUsedParts) && is_array($explicitUsedParts)) {
                                                            $spareParts = $explicitUsedParts;
                                                        }

                                                        // Filter to only include parts for this specific device
                                                        $deviceSerial = $device['device_serial'] ?? null;
                                                        if ($deviceSerial) {
                                                            $spareParts = array_filter($spareParts, function($part) use ($deviceSerial) {
                                                                return ($part['device_serial'] ?? 'N/A') == $deviceSerial;
                                                            });
                                                        }

                                                        // Group spare parts by device serial number
                                                        $sparePartsBySerial = [];
                                                        foreach($spareParts as $part) {
                                                            $serial = $part['device_serial'] ?? 'N/A';
                                                            if(!isset($sparePartsBySerial[$serial])) {
                                                                $sparePartsBySerial[$serial] = [
                                                                    'device_model' => $part['device_model'] ?? 'N/A',
                                                                    'device_serial' => $serial,
                                                                    'parts' => []
                                                                ];
                                                            }
                                                            $sparePartsBySerial[$serial]['parts'][] = $part;
                                                        }

                                                        // Generate the HTML output
                                                        $html = '<div class="mb-6">';
                                                        $html .= '<h4 class="pb-2 mb-2 font-semibold text-gray-700 border-b text-md" style="color: green;">Spare Parts Used</h4>';

                                                        if(!empty($spareParts) && count($spareParts) > 0) {
                                                            $html .= '<div class="space-y-4">';

                                                            foreach($sparePartsBySerial as $serial => $deviceGroup) {
                                                                $html .= '<div class="mb-3">';
                                                                $html .= '<div class="px-4 py-2 font-medium text-white bg-green-600 rounded-t-lg" style="background-color: green;">';
                                                                $html .= 'Device: ' . htmlspecialchars($deviceGroup['device_model']) . ' (S/N: ' . htmlspecialchars($serial) . ')';
                                                                $html .= '</div>';

                                                                // Left-right grid for spare parts
                                                                $html .= '<div class="grid grid-cols-1 gap-2 p-4 border border-gray-300 md:grid-cols-2" style="background-color: #0080001c;">';

                                                                // Left column
                                                                $html .= '<div>';
                                                                foreach($deviceGroup['parts'] as $index => $part) {
                                                                    if($index % 2 == 0) {
                                                                        $html .= '<div class="items-start mb-2">';
                                                                        $html .= '<span class="mr-2 text-lg">•</span>';
                                                                        $html .= '<span>' . htmlspecialchars($part['part_name'] ?? 'N/A') . '</span>';
                                                                        $html .= '</div>';
                                                                    }
                                                                }
                                                                $html .= '</div>';

                                                                // Right column
                                                                $html .= '<div>';
                                                                foreach($deviceGroup['parts'] as $index => $part) {
                                                                    if($index % 2 == 1) {
                                                                        $html .= '<div class="items-start mb-2">';
                                                                        $html .= '<span class="mr-2 text-lg">•</span>';
                                                                        $html .= '<span>' . htmlspecialchars($part['part_name'] ?? 'N/A') . '</span>';
                                                                        $html .= '</div>';
                                                                    }
                                                                }
                                                                $html .= '</div>';

                                                                $html .= '</div>'; // End grid
                                                                $html .= '</div>'; // End device group
                                                            }

                                                            $html .= '</div>'; // End space-y-4
                                                        } else {
                                                            $html .= '<p class="italic text-gray-500">No spare parts were used</p>';
                                                        }

                                                        $html .= '</div>'; // End mb-6

                                                        return new HtmlString($html);
                                                    }),
                                                ])
                                                ->collapsible(),

                                            // Required files section
                                            Section::make('Required Files')
                                                ->schema([
                                                    // CSO File is mandatory for all
                                                    Grid::make(2)
                                                    ->schema([
                                                        FileUpload::make("devices.{$index}.cso_file")
                                                            ->label('Computing Sales Order (CSO)')
                                                            ->required()
                                                            ->disk('public')
                                                            ->directory('repairs/cso_files')
                                                            ->acceptedFileTypes(['application/pdf'])
                                                            ->maxSize(10240) // 10MB
                                                            ->openable()
                                                            ->downloadable()
                                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file) use ($record, $device, $index) {
                                                                $serialNum = $device['device_serial'] ?? 'unknown';
                                                                $dateStr = now()->format('Ymd');
                                                                $repairId = 'OR_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                                                                return "{$repairId}_device_{$index}_CSO_{$serialNum}_{$dateStr}.{$file->getClientOriginalExtension()}";
                                                            }),

                                                        FileUpload::make("devices.{$index}.invoice_sparepart")
                                                            ->label('Spare Parts Invoice')
                                                            ->disk('public')
                                                            ->directory('repairs/spareparts_invoices')
                                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                            ->maxSize(10240) // 10MB
                                                            ->openable()
                                                            ->downloadable()
                                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file) use ($record, $device, $index) {
                                                                $serialNum = $device['device_serial'] ?? 'unknown';
                                                                $dateStr = now()->format('Ymd');
                                                                $repairId = 'OR_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                                                                return "{$repairId}_device_{$index}_SpareParts_Invoice_{$serialNum}_{$dateStr}.{$file->getClientOriginalExtension()}";
                                                            }),
                                                    ]),

                                                    Select::make("devices.{$index}.quotation_selection")
                                                        ->label('Select Quotation')
                                                        ->visible(!$hasWarranty)
                                                        ->options(function () use ($record) {
                                                            $options = [];
                                                            // Get all quotations related to the same lead_id without filtering by type
                                                            if ($record->lead_id) {
                                                                $leadQuotations = \App\Models\Quotation::where('lead_id', $record->lead_id)
                                                                    ->where('status', 'accepted')
                                                                    ->get();

                                                                foreach ($leadQuotations as $quotation) {
                                                                    $prefix = $quotation->quotation_type ? strtoupper($quotation->quotation_type) . ' - ' : '';
                                                                    $refNo = $quotation->quotation_reference_no ?? $quotation->quotation_no ?? 'Quotation #' . $quotation->id;

                                                                    $label = $prefix . $refNo;

                                                                    if ($quotation->created_at) {
                                                                        $label .= ' (' . $quotation->created_at->format('d M Y') . ')';
                                                                    }

                                                                    $options[$quotation->id] = $label;
                                                                }
                                                            }

                                                            // If no options, add a placeholder
                                                            if (empty($options)) {
                                                                $options['none'] = 'No quotations available';
                                                            }

                                                            return $options;
                                                        })
                                                        ->searchable()
                                                        ->preload()
                                                ]),
                                        ])
                                        ->collapsible();
                                }
                            }

                            return $schema;
                        })
                        ->action(function (AdminRepair $record, array $data) {
                            // Process device data and files
                            if (isset($data['devices']) && is_array($data['devices'])) {
                                // Get the original device warranty info
                                $deviceWarranty = [];
                                if ($record->devices_warranty) {
                                    $deviceWarranty = is_string($record->devices_warranty)
                                        ? json_decode($record->devices_warranty, true)
                                        : $record->devices_warranty;
                                }

                                // Process each device
                                foreach ($data['devices'] as $index => $device) {
                                    // Skip if this index doesn't exist in original device warranty data
                                    if (!isset($deviceWarranty[$index])) {
                                        continue;
                                    }

                                    // Check if this device is in warranty
                                    $hasWarranty = isset($deviceWarranty[$index]['warranty_status']) &&
                                                strtolower($deviceWarranty[$index]['warranty_status']) !== 'out of warranty';

                                    // Add CSO file to device data
                                    if (isset($device['cso_file'])) {
                                        $deviceWarranty[$index]['cso_file'] = $device['cso_file'];
                                    }

                                    if (isset($device['invoice_sparepart'])) {
                                        $deviceWarranty[$index]['invoice_sparepart'] = $device['invoice_sparepart'];
                                    }

                                    // Process quotation for out-of-warranty devices
                                    if (!$hasWarranty && isset($device['quotation_selection'])) {
                                        $selection = $device['quotation_selection'];
                                        $deviceWarranty[$index]['quotation_id'] = $selection;
                                    }
                                }

                                // Update the record with the processed device warranty data
                                $record->update([
                                    'status' => 'Completed Admin Repair',
                                    'devices_warranty' => json_encode($deviceWarranty),
                                    'completed_at' => now(), // Use the appropriate field name for your model
                                ]);

                                // Log the updated data for debugging
                                Log::info('Updated device warranty data', [
                                    'repair_id' => $record->id,
                                    'devices_warranty' => $deviceWarranty
                                ]);

                                // Generate PDF if needed
                                try {
                                    $pdfController = new GenerateRepairHandoverPdfController();
                                    $pdfPath = $pdfController->generateInBackground($record);

                                    if ($pdfPath && $pdfPath !== $record->repair_pdf) {
                                        $record->update(['repair_pdf' => $pdfPath]);
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Failed to regenerate repair PDF", [
                                        'repair_id' => $record->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                // Send notification
                                Notification::make()
                                    ->title('Repair Completed')
                                    ->success()
                                    ->body('The repair has been marked as completed successfully.')
                                    ->send();
                            } else {
                                // Handle case when no device data is provided
                                Notification::make()
                                    ->title('Error')
                                    ->danger()
                                    ->body('No device data was provided. Please try again.')
                                    ->send();
                            }
                        })
                        ->modalSubmitActionLabel('Complete Repair')
                    ])->button()
            ]);
    }

    public function render()
    {
        return view('livewire.admin-repair-completed-technician');
    }
}
