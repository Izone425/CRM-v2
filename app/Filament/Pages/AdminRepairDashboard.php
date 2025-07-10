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
                            Select::make('company_name')
                                ->label('Company Name')
                                ->columnSpan(2)
                                ->options(function () {
                                    $options = collect();

                                    // 1. Get leads marked as visible in repairs that have company details
                                    $visibleLeads = \App\Models\Lead::where('visible_in_repairs', true)
                                        ->with('companyDetail')
                                        ->orderBy('id', 'desc')
                                        ->get();

                                    foreach ($visibleLeads as $lead) {
                                        if ($lead->companyDetail && $lead->companyDetail->company_name) {
                                            // For leads without handovers, use a special prefix
                                            $leadId = 'LD_' . str_pad($lead->id, 6, '0', STR_PAD_LEFT);
                                            $companyName = $lead->companyDetail->company_name;

                                            $options->push([
                                                // Store value with prefix to identify as lead
                                                'id' => 'lead_' . $lead->id,
                                                'label' => $leadId . ' | ' . $companyName,
                                                'sort_key' => $lead->id
                                            ]);
                                        }
                                    }

                                    // 2. Get handovers with direct company_name values
                                    $directCompanyHandovers = \App\Models\SoftwareHandover::whereNotNull('company_name')
                                        ->where('company_name', '!=', '')
                                        ->orderBy('id', 'desc')
                                        ->get();

                                    foreach ($directCompanyHandovers as $handover) {
                                        $handoverId = 'SW_' . str_pad($handover->id, 6, '0', STR_PAD_LEFT);
                                        $companyName = $handover->company_name;

                                        $options->push([
                                            // Store value with prefix to identify as handover
                                            'id' => 'handover_' . $handover->id,
                                            'label' => $handoverId . ' | ' . $companyName,
                                            'sort_key' => $handover->id
                                        ]);
                                    }

                                    // Sort by ID (descending) and remove duplicates
                                    return $options
                                        ->sortByDesc('sort_key')
                                        ->pluck('label', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->required()
                                ->default(fn (?AdminRepair $record = null) =>
                                    $record?->software_handover_id ?? null)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        // Check if we're dealing with a lead ID
                                        if (is_string($state) && strpos($state, 'lead_') === 0) {
                                            // Extract the lead ID
                                            $leadId = (int)str_replace('lead_', '', $state);
                                            $lead = \App\Models\Lead::with('companyDetail')->find($leadId);

                                            if ($lead) {
                                                // Set software_handover_id to null since this is from a lead
                                                $set('software_handover_id', null);
                                                $set('lead_id', $lead->id);

                                                if ($lead->companyDetail) {
                                                    // Set company_id for compatibility with existing code
                                                    $set('company_id', $lead->companyDetail->id);

                                                    // Set other fields from company details
                                                    if (!empty($lead->companyDetail->name)) {
                                                        $set('pic_name', $lead->companyDetail->name ?? $lead->name);
                                                    }

                                                    if (!empty($lead->companyDetail->contact_no)) {
                                                        $set('pic_phone', $lead->companyDetail->contact_no);
                                                    }

                                                    if (!empty($lead->companyDetail->email)) {
                                                        $set('pic_email', $lead->companyDetail->email);
                                                    }

                                                    // Build and set address
                                                    $address = $lead->companyDetail->company_address1 ?? '';

                                                    // Add address2 if it exists
                                                    if (!empty($lead->companyDetail->company_address2)) {
                                                        $address .= ", " . $lead->companyDetail->company_address2;
                                                    }

                                                    // Add postcode and state
                                                    if (!empty($lead->companyDetail->postcode) || !empty($lead->companyDetail->state)) {
                                                        $address .= ", " .
                                                            ($lead->companyDetail->postcode ?? '') . " " .
                                                            ($lead->companyDetail->state ?? '');
                                                    }

                                                    // Set the address field with uppercase text
                                                    $set('address', Str::upper($address));
                                                } else {
                                                    // Fallback to using lead ID if no companyDetail exists
                                                    $set('company_id', $lead->id);

                                                    // Set fields directly from lead
                                                    $set('pic_name', $lead->pic_name ?? '');
                                                    $set('pic_phone', $lead->pic_phone ?? '');
                                                    $set('pic_email', $lead->pic_email ?? '');

                                                    // If we have any address fields on the lead directly, use those
                                                    if (!empty($lead->address)) {
                                                        $set('address', Str::upper($lead->address));
                                                    } else {
                                                        // If no address on lead, we can leave it empty or set a placeholder
                                                        $set('address', '');
                                                    }
                                                }

                                                // Set fields from lead if they're empty from companyDetail
                                                if (empty($lead->companyDetail) || empty($lead->companyDetail->name)) {
                                                    $set('pic_name', $lead->name ?? '');
                                                }

                                                if (empty($lead->companyDetail) || empty($lead->companyDetail->contact_no)) {
                                                    $set('pic_phone', $lead->phone ?? '');
                                                }

                                                if (empty($lead->companyDetail) || empty($lead->companyDetail->email)) {
                                                    $set('pic_email', $lead->email ?? '');
                                                }
                                            }
                                        }
                                        // Check if we're dealing with a handover ID
                                        elseif (is_string($state) && strpos($state, 'handover_') === 0) {
                                            // Extract the handover ID
                                            $handoverId = (int)str_replace('handover_', '', $state);
                                            $handover = \App\Models\SoftwareHandover::find($handoverId);

                                            if ($handover) {
                                                // Set software_handover_id for later use
                                                $set('software_handover_id', $handover->id);

                                                // Find the related company via handover -> lead -> companyDetail
                                                $company = null;

                                                // First try direct company relation if it exists
                                                if ($handover->company_id) {
                                                    $company = CompanyDetail::find($handover->company_id);
                                                }
                                                // Then try through lead relationship
                                                elseif ($handover->lead && $handover->lead->companyDetail) {
                                                    $company = $handover->lead->companyDetail;
                                                }

                                                // If we found a company, set all the fields
                                                if ($company) {
                                                    // Set company_id for compatibility with existing code
                                                    $set('company_id', $company->id);

                                                    // Set lead_id if available
                                                    if ($handover->lead_id) {
                                                        $set('lead_id', $handover->lead_id);
                                                    }

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

                                                    // Build and set address
                                                    $address = $company->company_address1 ?? '';

                                                    // Add address2 if it exists
                                                    if (!empty($company->company_address2)) {
                                                        $address .= ", " . $company->company_address2;
                                                    }

                                                    // Add postcode and state
                                                    if (!empty($company->postcode) || !empty($company->state)) {
                                                        $address .= ", " .
                                                            ($company->postcode ?? '') . " " .
                                                            ($company->state ?? '');
                                                    }

                                                    // Set the address field with uppercase text
                                                    $set('address', Str::upper($address));

                                                    // If any fields are still empty, try to get from the related lead
                                                    if (empty($company->contact_person) || empty($company->contact_phone) || empty($company->contact_email)) {
                                                        $lead = $company->lead ?? $handover->lead;

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
                                                } else {
                                                    // If no company was found but we have a handover, try to get details directly from handover
                                                    if (!empty($handover->company_name)) {
                                                        $set('pic_name', $handover->pic_name ?? '');
                                                        $set('pic_phone', $handover->pic_phone ?? '');
                                                        $set('pic_email', $handover->pic_email ?? '');
                                                    }
                                                }
                                            }
                                        }
                                        // Legacy support for non-prefixed IDs
                                        else if (is_numeric($state)) {
                                            // Try as software handover ID
                                            $handover = \App\Models\SoftwareHandover::find($state);

                                            if ($handover) {
                                                // Handle as regular handover (same as the handover_ case above)
                                                // (Copy the code from the handover_ case)
                                                $set('software_handover_id', $handover->id);

                                                // Rest of your handover handling code...
                                            }
                                        }
                                    }
                                }),
                            TextInput::make('zoho_ticket')
                                ->label('Zoho Desk Ticket Number')
                                ->columnSpan(1)
                                ->default(fn (?AdminRepair $record = null) =>
                                    $record?->zoho_ticket ?? null)
                                ->maxLength(50),

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
                        ]),
                        TextArea::make('address')
                            ->label('Address')
                            ->required()
                            ->rows(2)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->afterStateHydrated(fn($state) => Str::upper($state))
                            ->afterStateUpdated(fn($state) => Str::upper($state))
                            ->default(function (?AdminRepair $record = null) {
                                // First check if record has category2 data already
                                if ($record && $record->address) {
                                    return $record->address;
                                }

                                // Get company ID either from existing record or from form state
                                $companyId = $record->company_id ?? $this->company_id ?? request('company_id');

                                if ($companyId) {
                                    // Find the company detail
                                    $companyDetail = \App\Models\CompanyDetail::find($companyId);

                                    if ($companyDetail) {
                                        // Use company details if available
                                        $address = $companyDetail->company_address1 ?? '';

                                        // Add address2 if it exists
                                        if (!empty($companyDetail->company_address2)) {
                                            $address .= ", " . $companyDetail->company_address2;
                                        }

                                        // Add postcode and state
                                        if (!empty($companyDetail->postcode) || !empty($companyDetail->state)) {
                                            $address .= ", " .
                                                ($companyDetail->postcode ?? '') . " " .
                                                ($companyDetail->state ?? '');
                                        }

                                        return $address;
                                    }
                                }
                                return '';
                            })
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
                        ->helperText('Upload videos of the issue for better diagnostics. Maximum 10MB')
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

        // Creating a new record
        $data['created_by'] = auth()->id();
        $data['status'] = 'New'; // Default status for new records

        if (isset($data['company_name'])) {
            // Check if this is a lead ID (starts with 'lead_')
            if (is_string($data['company_name']) && strpos($data['company_name'], 'lead_') === 0) {
                // Extract lead ID from the string
                $leadId = (int)str_replace('lead_', '', $data['company_name']);
                $data['lead_id'] = $leadId;

                // Store software_handover_id as null since this is from a lead
                $data['software_handover_id'] = null;

                // Get the actual company name from the lead's companyDetail
                $lead = \App\Models\Lead::with('companyDetail')->find($leadId);
                if ($lead && $lead->companyDetail && $lead->companyDetail->company_name) {
                    // Update the company_name field with the actual company name
                    $data['company_name'] = $lead->companyDetail->company_name;
                }
            }
            // Check if this is a handover ID (starts with 'handover_')
            elseif (is_string($data['company_name']) && strpos($data['company_name'], 'handover_') === 0) {
                // Extract handover ID from the string
                $handoverId = (int)str_replace('handover_', '', $data['company_name']);
                $data['software_handover_id'] = $handoverId;

                // Try to get lead_id from software handover
                $softwareHandover = \App\Models\SoftwareHandover::find($handoverId);
                if ($softwareHandover) {
                    // Set lead_id if available
                    if ($softwareHandover->lead_id) {
                        $data['lead_id'] = $softwareHandover->lead_id;
                    } elseif ($softwareHandover->lead) {
                        $data['lead_id'] = $softwareHandover->lead->id;
                    } else {
                        $data['lead_id'] = 0;
                    }

                    // IMPORTANT: Set the actual company name
                    $data['company_name'] = $softwareHandover->company_name;
                } else {
                    // Default to avoid database error
                    $data['lead_id'] = 0;
                }
            }
            // If it's a direct company name string
            else {
                // Extract company name from the format "ID | Company Name" if applicable
                $companyNameParts = explode(' | ', $data['company_name']);
                if (count($companyNameParts) > 1) {
                    $data['company_name'] = $companyNameParts[1];
                }

                // Try to find related IDs but keep the company name as is
                $companyDetail = \App\Models\CompanyDetail::where('company_name', $data['company_name'])->first();
                if ($companyDetail && $companyDetail->lead) {
                    $data['lead_id'] = $companyDetail->lead->id;
                } else {
                    $data['lead_id'] = 0;
                }

                $data['software_handover_id'] = null;
            }
        } else {
            // Default to avoid database error
            $data['lead_id'] = 0;
        }

        $data['submitted_at'] = now();

        // Create new record
        $repair = AdminRepair::create($data);

        // Generate repair ID after we have a valid record with an ID
        $repairId = 'RP_250' . str_pad($repair->id, 3, '0', STR_PAD_LEFT);

        try {
            // Get company name
            $companyName = $repair->company_name ?? 'Unknown Company';

            $pdfPath = $repair->handover_pdf ?? app(\App\Http\Controllers\GenerateRepairHandoverPdfController::class)->generateInBackground($repair);
            $pdfUrl = $pdfPath ? url('storage/' . $pdfPath) : null;

            // Create email content structure
            $emailContent = [
                'repair_id' => $repairId,
                'company' => [
                    'name' => $companyName,
                ],
                'pic' => [
                    'name' => $repair->pic_name ?? 'N/A',
                    'phone' => $repair->pic_phone ?? 'N/A',
                    'email' => $repair->pic_email ?? 'N/A',
                ],
                'devices' => [], // New array for devices
                // Keep old structure for backward compatibility
                'device' => [
                    'model' => $repair->device_model ?? 'N/A',
                    'serial' => $repair->device_serial ?? 'N/A',
                ],
                'status' => $repair->status ?? 'New',
                'pdf_url' => $pdfUrl,
                'created_by' => User::find($repair->created_by)->name ?? 'Unknown',
                'submitted_at' => $repair->submitted_at ? $repair->submitted_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A'),
                'remarks' => []
            ];

            // Process devices data for email
            if ($repair->devices) {
                $devices = is_string($repair->devices)
                    ? json_decode($repair->devices, true)
                    : $repair->devices;

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
            if ($repair->remarks) {
                $remarks = is_string($repair->remarks)
                    ? json_decode($repair->remarks, true)
                    : $repair->remarks;

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
                'admin.timetec.hr@timeteccloud.com',
                'support@timeteccloud.com',
                'izzuddin@timeteccloud.com'
            ];

            // Send email notification
            $authUser = auth()->user();
            \Illuminate\Support\Facades\Mail::send(
                'emails.repair_ticket_notification',
                ['emailContent' => $emailContent],
                function ($message) use ($recipients, $authUser, $repairId, $companyName) {
                    $message->from($authUser->email, $authUser->name)
                        ->to($recipients)
                        ->subject("REPAIR HANDOVER ID {$repairId} | {$companyName}");
                }
            );

            Notification::make()
                ->title('Repair ticket submitted')
                ->success()
                ->body('Notification emails have been sent to the support team.')
                ->send();

        } catch (\Exception $e) {
            // Log error but don't stop the process
            \Illuminate\Support\Facades\Log::error("Email sending failed for repair ticket #{$repair->id}: {$e->getMessage()}");

            Notification::make()
                ->title('Repair ticket submitted')
                ->success()
                ->body('Ticket submitted but email notifications failed.')
                ->send();
        }

        return $repair;
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

                TextColumn::make('creator.name')
                    ->label('Submitted By')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        // If relationship or name is null, try to get the user manually
                        if (!$state && $record->created_by) {
                            $user = \App\Models\User::find($record->created_by);
                            return $user ? $user->name : "User #{$record->created_by}";
                        }
                        return $state ?? "Unknown";
                    }),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        // First try to get company name directly from softwareHandover relation
                        if ($state) {
                            return $state;
                        }

                        // If that's null, try to get it from softwareHandover's relationship
                        if ($record->softwareHandover) {
                            if ($record->softwareHandover->lead && $record->softwareHandover->lead->companyDetail) {
                                return $record->softwareHandover->lead->companyDetail->company_name;
                            }
                        }

                        // Fallback to the old method as a last resort
                        return $record->companyDetail->company_name ?? 'Unknown Company';
                    }),

                TextColumn::make('devices')
                    ->label('Devices')
                    ->toggleable(isToggledHiddenByDefault: true)
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
                    ->html(),

                TextColumn::make('zoho_ticket')
                    ->searchable()
                    ->label('Zoho Ticket'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Inactive' => 'gray',
                        'New' => 'danger',
                        'Accepted' => 'danger',
                        'Pending Confirmation' => 'danger',
                        'Pending Onsite Repair' => 'danger',
                        'Completed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('status')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->options([
                                'New' => 'New',
                                'Accepted' => 'Accepted',
                                'Pending Confirmation' => 'Pending Confirmation',
                                'Pending Onsite Repair' => 'Pending Onsite Repair',
                                'Completed' => 'Completed',
                                'Inactive' => 'Inactive',
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
                                    'admin.timetec.hr@timeteccloud.com',
                                    'support@timeteccloud.com',
                                    'izzuddin@timeteccloud.com'
                                ];

                                // Send email notification
                                $authUser = auth()->user();
                                \Illuminate\Support\Facades\Mail::send(
                                    'emails.repair_ticket_notification',
                                    ['emailContent' => $emailContent],
                                    function ($message) use ($recipients, $authUser, $repairId, $companyName) {
                                        $message->from($authUser->email, $authUser->name)
                                            ->to($recipients)
                                            ->subject("REPAIR HANDOVER ID {$repairId} | {$companyName}");
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
                        ->modalHeading(' ')
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
