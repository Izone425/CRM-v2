<?php

namespace App\Filament\Pages;

use App\Models\AdminRepair;
use App\Models\CompanyDetail;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Actions\Action as HeaderAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Support\Colors\Color;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdminRepairDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationLabel = 'Repair Dashboard';
    protected static ?string $title = 'Admin Repair Dashboard';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.admin-repair-dashboard';
    protected static ?int $indexDeviceCounter = 0;
    protected static ?int $indexRemarkCounter = 0;

    // Define the default form for both create and edit operations
    public function defaultForm(?AdminRepair $record = null): array
    {
        // Reset counters when form is called
        self::$indexDeviceCounter = 0;
        self::$indexRemarkCounter = 0;

        return [
            // Section 1: Company & Contact Information
            Section::make('Company & Contact Information')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('company_id')
                                ->label('Company Name')
                                ->columnSpan(1)
                                ->options(function () {
                                    // Get companies with closed deals only and ensure no null company names
                                    return CompanyDetail::whereHas('lead', function ($query) {
                                            $query->where('lead_status', 'Closed');
                                        })
                                        ->whereNotNull('company_name')
                                        ->where('company_name', '!=', '')
                                        ->pluck('company_name', 'id')
                                        ->map(function ($companyName, $id) {
                                            // Ensure we have a string value
                                            return (string)($companyName ?? "Company #$id");
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->required()
                                ->default(fn (?AdminRepair $record = null) =>
                                    $record?->company_id ?? null)
                                ->live()  // Make it a live field that reacts to changes
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        // Get the selected company details
                                        $company = CompanyDetail::find($state);

                                        if ($company) {
                                            // First try to get details from company_details
                                            if (!empty($company->name)) {
                                                $set('pic_name', $company->name);
                                            }

                                            if (!empty($company->contact_no)) {
                                                $set('pic_phone', $company->contact_no);
                                            }

                                            if (!empty($company->email)) {
                                                $set('pic_email', $company->email);
                                            }

                                            // If any fields are still empty, try to get from the related lead
                                            if (empty($company->contact_person) || empty($company->contact_phone) || empty($company->contact_email)) {
                                                $lead = $company->lead;

                                                if ($lead) {
                                                    if (empty($company->contact_person) && !empty($lead->pic_name)) {
                                                        $set('pic_name', $lead->pic_name);
                                                    }

                                                    if (empty($company->contact_phone) && !empty($lead->pic_phone)) {
                                                        $set('pic_phone', $lead->pic_phone);
                                                    }

                                                    if (empty($company->contact_email) && !empty($lead->pic_email)) {
                                                        $set('pic_email', $lead->pic_email);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }),

                            // PIC NAME field
                            TextInput::make('pic_name')
                                ->label('PIC Name')
                                ->columnSpan(1)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->default(fn (?AdminRepair $record = null) =>
                                    $record?->pic_name ?? null)
                                ->required()
                                ->maxLength(255),

                            // PIC PHONE field
                            TextInput::make('pic_phone')
                                ->label('PIC Phone Number')
                                ->columnSpan(1)
                                ->tel()
                                ->default(fn (?AdminRepair $record = null) =>
                                    $record?->pic_phone ?? null)
                                ->required(),

                            // PIC EMAIL field
                            TextInput::make('pic_email')
                                ->label('PIC Email Address')
                                ->columnSpan(1)
                                ->email()
                                ->default(fn (?AdminRepair $record = null) =>
                                    $record?->pic_email ?? null)
                                ->required(),

                            // FIELD 4 – ZOHO TICKET NUMBER
                            TextInput::make('zoho_ticket')
                                ->label('Zoho Desk Ticket Number')
                                ->columnSpan(1)
                                ->default(fn (?AdminRepair $record = null) =>
                                    $record?->zoho_ticket ?? null)
                                ->maxLength(50),
                        ]),
                ]),

            // Section 2: Device Details
            Section::make('Device Details')
                ->schema([
                    Repeater::make('devices')
                        ->hiddenLabel()
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('device_model')
                                        ->label('Device Model')
                                        ->options([
                                            'TC10' => 'TC10',
                                            'TC20' => 'TC20',
                                            'FACE ID 5' => 'FACE ID 5',
                                            'FACE ID 6' => 'FACE ID 6',
                                            'TIME BEACON' => 'TIME BEACON',
                                            'NFC TAG' => 'NFC TAG',
                                        ])
                                        ->searchable()
                                        ->required(),

                                    TextInput::make('device_serial')
                                        ->label('Serial Number')
                                        ->required()
                                        ->maxLength(100),
                                ])
                        ])
                        ->itemLabel(fn() => __('Device') . ' ' . ++self::$indexDeviceCounter)
                        ->addActionLabel('Add Another Device')
                        ->maxItems(5) // Limit to 5 devices
                        ->defaultItems(1) // Start with 1 device
                        ->default(function (?AdminRepair $record = null) {
                            if (!$record) {
                                return [
                                    ['device_model' => '', 'device_serial' => '']
                                ];
                            }

                            // If we have existing devices in JSON format
                            if ($record->devices) {
                                $devices = is_string($record->devices)
                                    ? json_decode($record->devices, true)
                                    : $record->devices;

                                if (is_array($devices) && !empty($devices)) {
                                    return $devices;
                                }
                            }

                            // Fallback to legacy fields if no devices array
                            if ($record->device_model) {
                                return [
                                    ['device_model' => $record->device_model, 'device_serial' => $record->device_serial]
                                ];
                            }

                            return [
                                ['device_model' => '', 'device_serial' => '']
                            ];
                        }),
                ]),

            // Section 3: Remarks
            Section::make('Repair Remarks')
                ->schema([
                    Repeater::make('remarks')
                        ->hiddenLabel()
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('remark')
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->hiddenLabel()
                                        ->placeholder('ENTER REMARKS HERE')
                                        ->rows(3)
                                        ->required(),

                                    FileUpload::make('attachments')
                                        ->hiddenLabel()
                                        ->disk('public')
                                        ->directory('repair-attachments')
                                        ->visibility('public')
                                        ->multiple()
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->openable()
                                        ->previewable()
                                        ->downloadable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                            $timestamp = now()->format('YmdHis');
                                            $random = rand(1000, 9999);
                                            $extension = $file->getClientOriginalExtension();
                                            return "repair-attachment-{$timestamp}-{$random}.{$extension}";
                                        }),
                                ])
                        ])
                        ->itemLabel(fn() => __('Repair Remark') . ' ' . ++self::$indexRemarkCounter)
                        ->addActionLabel('Add Another Remark')
                        ->collapsible()
                        ->maxItems(5)
                        ->defaultItems(1)
                        // Set default remarks data from record
                        ->default(function (?AdminRepair $record = null) {
                            if (!$record || !$record->remarks) {
                                return [
                                    ['remark' => '', 'attachments' => []]
                                ];
                            }

                            try {
                                $remarks = is_string($record->remarks)
                                    ? json_decode($record->remarks, true)
                                    : $record->remarks;

                                if (is_array($remarks)) {
                                    // Process each remark to handle attachments
                                    foreach ($remarks as $key => $remark) {
                                        if (isset($remark['attachments']) && is_string($remark['attachments'])) {
                                            $remarks[$key]['attachments'] = json_decode($remark['attachments'], true) ?: [];
                                        } elseif (!isset($remark['attachments'])) {
                                            $remarks[$key]['attachments'] = [];
                                        }
                                    }
                                    return $remarks;
                                }
                            } catch (\Exception $e) {
                                // Log the error but continue
                                \Illuminate\Support\Facades\Log::error("Error processing remarks: {$e->getMessage()}");
                            }

                            return [
                                ['remark' => '', 'attachments' => []]
                            ];
                        }),
                ]),

            // Section 4: Video Files
            Section::make('Video Files')
                ->schema([
                    FileUpload::make('video_files')
                        ->hiddenLabel()
                        ->helperText('Upload videos of the issue for better diagnostics')
                        ->disk('public')
                        ->directory('repair-videos')
                        ->visibility('public')
                        ->multiple()
                        ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo'])
                        ->openable()
                        ->previewable()
                        ->downloadable()
                        ->default(function (?AdminRepair $record = null) {
                            if (!$record || !$record->video_files) {
                                return [];
                            }

                            try {
                                $videoFiles = is_string($record->video_files)
                                    ? json_decode($record->video_files, true)
                                    : $record->video_files;

                                return $videoFiles ?: [];
                            } catch (\Exception $e) {
                                // Log the error but continue
                                \Illuminate\Support\Facades\Log::error("Error processing video files: {$e->getMessage()}");
                                return [];
                            }
                        })
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                            $timestamp = now()->format('YmdHis');
                            $random = rand(1000, 9999);
                            $extension = $file->getClientOriginalExtension();
                            return "repair-video-{$timestamp}-{$random}.{$extension}";
                        }),
                ]),

            // Hidden fields for record keeping
            Hidden::make('status')
                ->default(function () use ($record) {
                    return $record ? $record->status : 'Draft';
                }),
            Hidden::make('created_by')
                ->default(function () use ($record) {
                    return $record ? $record->created_by : auth()->id();
                }),
            Hidden::make('updated_by')
                ->default(fn() => auth()->id()),
        ];
    }

    // Define header actions (e.g. "New Repair" button)
    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('create_repair')
                ->label('New Task')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create New Task')
                ->modalWidth('4xl')
                ->slideover()
                ->color('primary')
                ->form($this->defaultForm())
                ->action(function (array $data): void {
                    // Process and save the form data
                    $this->processAndSaveRepairData(null, $data);

                    // Provide feedback and refresh the page
                    Notification::make()
                        ->title('Repair ticket created')
                        ->success()
                        ->body('Your repair ticket has been created as a draft.')
                        ->send();

                    // Refresh the page
                    $this->redirect(static::getUrl());
                })
                ->button(),
        ];
    }

    // Process and save form data (common function for create and update)
    protected function processAndSaveRepairData(?AdminRepair $record, array $data): AdminRepair
    {
        try {
            // Process remarks with attachments
            if (isset($data['remarks']) && is_array($data['remarks'])) {
                foreach ($data['remarks'] as $key => $remark) {
                    // Encode attachments array for each remark if it exists
                    if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                        $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                    } else {
                        $data['remarks'][$key]['attachments'] = json_encode([]);
                    }
                }
                // Encode the entire remarks structure
                $data['remarks'] = json_encode($data['remarks']);
            } else {
                $data['remarks'] = json_encode([]);
            }

            // Encode devices array
            if (isset($data['devices']) && is_array($data['devices'])) {
                $data['devices'] = json_encode($data['devices']);

                // Also set the first device to the legacy fields for compatibility
                if (!empty($data['devices'])) {
                    $decoded = json_decode($data['devices'], true);
                    if (is_array($decoded) && !empty($decoded[0])) {
                        $data['device_model'] = $decoded[0]['device_model'] ?? null;
                        $data['device_serial'] = $decoded[0]['device_serial'] ?? null;
                    }
                }
            } else {
                $data['devices'] = json_encode([]);
            }

            // Encode video files array
            if (isset($data['video_files']) && is_array($data['video_files'])) {
                $data['video_files'] = json_encode($data['video_files']);
            } else {
                $data['video_files'] = json_encode([]);
            }

            // Set timestamps and user IDs
            $data['updated_by'] = auth()->id();

            if (!$record) {
                $data['created_by'] = auth()->id();
                $data['status'] = 'Draft'; // Default status for new records

                // Create new record
                $repair = AdminRepair::create($data);
            } else {
                // Update existing record
                $record->update($data);
                $repair = $record;
            }

            return $repair;
        } catch (\Exception $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error("Error saving repair data: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());

            // Rethrow as a user-friendly exception
            throw new \Exception("There was a problem saving the repair data. Please try again or contact support.");
        }
    }

    // Get data for stats display
    public function getTableQuery(): Builder
    {
        $query = AdminRepair::query()
            ->orderBy('created_at', 'desc');

        return $query;
    }

    // Define the table
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
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

                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pic_name')
                    ->label('PIC Name')
                    ->searchable(),

                TextColumn::make('devices')
                    ->label('Devices')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if ($record->devices) {
                            $devices = is_string($record->devices)
                                ? json_decode($record->devices, true)
                                : $record->devices;

                            if (is_array($devices)) {
                                return collect($devices)
                                    ->map(fn ($device) =>
                                        "{$device['device_model']} (SN: {$device['device_serial']})")
                                    ->join('<br>');
                            }
                        }

                        if ($record->device_model) {
                            return "{$record->device_model} (SN: {$record->device_serial})";
                        }

                        return '—';
                    })
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('device_model', 'like', "%{$search}%")
                            ->orWhere('device_serial', 'like', "%{$search}%")
                            ->orWhere('devices', 'like', "%{$search}%");
                    }),

                TextColumn::make('zoho_ticket')
                    ->label('Zoho Ticket')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('status')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->options([
                                'Draft' => 'Draft',
                                'New' => 'New',
                                'In Progress' => 'In Progress',
                                'Awaiting Parts' => 'Awaiting Parts',
                                'Resolved' => 'Resolved',
                                'Closed' => 'Closed',
                            ])
                            ->placeholder('All Statuses')
                            ->label('Status'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['status'],
                            fn (Builder $query, $status): Builder => $query->where('status', $status)
                        );
                    }),

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
            ])
            ->actions([
                ActionGroup::make([
                    // Edit action - uses the same form as create
                    Action::make('edit')
                        ->color('warning')
                        ->icon('heroicon-o-pencil')
                        ->visible(fn (AdminRepair $record): bool => $record->status === 'Draft')
                        ->modalHeading(fn (AdminRepair $record) => "Edit Repair Ticket " . 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT))
                        ->slideOver()
                        ->modalWidth('4xl')
                        ->form($this->defaultForm())
                        ->action(function (array $data, AdminRepair $record): void {
                            // Use the common function to process and save data
                            $this->processAndSaveRepairData($record, $data);

                            // Provide feedback
                            Notification::make()
                                ->title('Repair ticket updated')
                                ->success()
                                ->send();
                        }),

                    // Submit action - changes status from Draft to New
                    Action::make('submit')
                        ->label('Submit')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn (AdminRepair $record): bool => $record->status === 'Draft')
                        ->requiresConfirmation()
                        ->modalHeading('Submit repair ticket')
                        ->modalDescription('Are you sure you want to submit this repair ticket? This will change the status from Draft to New.')
                        ->modalSubmitActionLabel('Yes, submit ticket')
                        ->action(function (AdminRepair $record) {
                            // Update repair status
                            $record->status = 'New';
                            $record->submitted_at = now();
                            $record->save();

                            // Format the repair ID properly
                            $repairId = 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                            try {
                                // Get company name
                                $companyName = $record->companyDetail->company_name ?? 'Unknown Company';

                                // Create email content structure
                                $emailContent = [
                                    'repair_id' => $repairId,
                                    'company' => [
                                        'name' => $companyName,
                                    ],
                                    'pic' => [
                                        'name' => $record->pic_name ?? 'N/A',
                                        'phone' => $record->pic_phone ?? 'N/A',
                                        'email' => $record->pic_email ?? 'N/A',
                                    ],
                                    'devices' => [], // New array for devices
                                    // Keep old structure for backward compatibility
                                    'device' => [
                                        'model' => $record->device_model ?? 'N/A',
                                        'serial' => $record->device_serial ?? 'N/A',
                                    ],
                                    'submitted_at' => $record->submitted_at->format('d M Y, h:i A'),
                                    'remarks' => []
                                ];

                                // Process devices data for email
                                if ($record->devices) {
                                    $devices = is_string($record->devices)
                                        ? json_decode($record->devices, true)
                                        : $record->devices;

                                    if (is_array($devices)) {
                                        foreach ($devices as $device) {
                                            $emailContent['devices'][] = [
                                                'device_model' => $device['device_model'] ?? 'N/A',
                                                'device_serial' => $device['device_serial'] ?? 'N/A'
                                            ];
                                        }
                                    }
                                }

                                // Process remarks data for email
                                if ($record->remarks) {
                                    $remarks = is_string($record->remarks)
                                        ? json_decode($record->remarks, true)
                                        : $record->remarks;

                                    if (is_array($remarks)) {
                                        foreach ($remarks as $key => $remark) {
                                            $formattedRemark = [
                                                'text' => $remark['remark'] ?? '',
                                                'attachments' => []
                                            ];

                                            // Process attachments for this remark
                                            if (isset($remark['attachments'])) {
                                                $attachments = is_string($remark['attachments'])
                                                    ? json_decode($remark['attachments'], true)
                                                    : $remark['attachments'];

                                                if (is_array($attachments)) {
                                                    foreach ($attachments as $attachment) {
                                                        $formattedRemark['attachments'][] = [
                                                            'url' => url('storage/' . $attachment),
                                                            'filename' => basename($attachment)
                                                        ];
                                                    }
                                                }
                                            }

                                            $emailContent['remarks'][] = $formattedRemark;
                                        }
                                    }
                                }

                                // Define recipients
                                $recipients = [
                                    // 'admin.timetec.hr@timeteccloud.com',
                                    // 'support@timeteccloud.com',
                                    // 'izzuddin@timeteccloud.com'
                                    'zilih020906@gmail.com',
                                ];

                                // Send email notification
                                $authUser = auth()->user();
                                \Illuminate\Support\Facades\Mail::send(
                                    'emails.repair_ticket_notification',
                                    ['emailContent' => $emailContent],
                                    function ($message) use ($recipients, $authUser, $repairId, $companyName) {
                                        $message->from($authUser->email, $authUser->name)
                                            ->to($recipients)
                                            ->subject("NEW REPAIR TICKET {$repairId} | {$companyName}");
                                    }
                                );

                                Notification::make()
                                    ->title('Repair ticket submitted')
                                    ->success()
                                    ->body('Notification emails have been sent to the support team.')
                                    ->send();

                            } catch (\Exception $e) {
                                // Log error but don't stop the process
                                \Illuminate\Support\Facades\Log::error("Email sending failed for repair ticket #{$record->id}: {$e->getMessage()}");

                                Notification::make()
                                    ->title('Repair ticket submitted')
                                    ->success()
                                    ->body('Ticket submitted but email notifications failed.')
                                    ->send();
                            }
                        }),

                    // View detail action
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->modalHeading(fn (AdminRepair $record) => "View Repair Ticket " . 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT))
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (AdminRepair $record): View {
                            return view('components.repair-detail')
                                ->with('record', $record);
                        }),
                ])
            ]);
    }
}
