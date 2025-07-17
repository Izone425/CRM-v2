<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminRepairResource\Pages;
use App\Filament\Resources\AdminRepairResource\RelationManagers;
use App\Models\AdminRepair;
use App\Models\CompanyDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdminRepairResource extends Resource
{
    protected static ?string $model = AdminRepair::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationLabel = 'Admin Repair Attachments';
    protected static ?int $indexRemarkCounter = 0;
    protected static ?int $indexDeviceCounter = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)
                ->schema([
                    // FIELD 1 – SEARCH COMPANY NAME (ONLY VISIBLE FOR CLOSED DEAL)
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
                                    return $companyName ?? "Company #$id";
                                })
                                ->toArray();
                        })
                        ->searchable()
                        ->required()
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

                    // PIC NAME field - keep as is
                    TextInput::make('pic_name')
                        ->label('PIC Name')
                        ->columnSpan(1)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->afterStateHydrated(fn($state) => Str::upper($state))
                        ->afterStateUpdated(fn($state) => Str::upper($state))
                        ->required()
                        ->maxLength(255),

                    // PIC PHONE field - keep as is
                    TextInput::make('pic_phone')
                        ->label('PIC Phone Number')
                        ->columnSpan(1)
                        ->tel()
                        ->required(),

                    // PIC EMAIL field - keep as is
                    TextInput::make('pic_email')
                        ->label('PIC Email Address')
                        ->columnSpan(1)
                        ->email()
                        ->required(),
                ]),
                Grid::make(3)
                ->schema([
                    // FIELD 5 – DROP DOWN LIST DEVICE MODEL
                    Section::make('Device Details')
                    ->columnSpan(2) // Make it span 2 columns
                    ->schema([
                        Forms\Components\Repeater::make('devices')
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
                                        'TA100C / HID' => 'TA100C / HID',
                                        'TA100C / R' => 'TA100C / R',
                                        'TA100C / MF' => ' TA100C / MF',
                                        'TA100C / R / W' => 'TA100C / R / W',
                                        'TA100C / MF / W' => 'TA100C / MF / W',
                                        'TA100C / HID / W' => 'TA100C / HID / W',
                                        'TA100C / W' => 'TA100C / W',
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
                        // Handle device data when loading form
                        ->default(function (?AdminRepair $record = null) {
                            if (!$record) {
                                return [
                                    ['device_model' => '', 'device_serial' => '']
                                ];
                            }

                            // If we have existing devices in JSON format
                            if ($record->devices && is_string($record->devices)) {
                                $devices = json_decode($record->devices, true);
                                if (is_array($devices) && !empty($devices)) {
                                    return $devices;
                                }
                            }

                            // If no devices array but we have single device fields populated
                            if ($record->device_model || $record->device_serial) {
                                return [
                                    [
                                        'device_model' => $record->device_model ?? '',
                                        'device_serial' => $record->device_serial ?? ''
                                    ]
                                ];
                            }

                            return [
                                ['device_model' => '', 'device_serial' => '']
                            ];
                        })
                        // Process device data before saving
                        ->mutateDehydratedStateUsing(function ($state) {
                            return json_encode($state);
                        }),
                    ]),

                // Move your Zoho ticket field elsewhere, perhaps under this section
                TextInput::make('zoho_ticket')
                    ->label('Zoho Desk Ticket Number')
                    ->columnSpan(1)
                    ->maxLength(50),
                ]),

                Grid::make(2)
                    ->schema([
                        // FIELD 7 – REMARK DETAILS + REMARK ATTACHMENT
                        Section::make('Repair Remarks')
                        ->columnSpan(1)
                        ->schema([
                            Forms\Components\Repeater::make('remarks')
                            ->label('Repair Remarks')
                            ->hiddenLabel(true)
                            ->schema([
                                Grid::make(2)
                                ->schema([
                                    Textarea::make('remark')
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->hiddenLabel(true)
                                        ->label(function (Forms\Get $get, ?string $state, $livewire) {
                                            // Get the current array key from the state path
                                            $statePath = $livewire->getFormStatePath();
                                            $matches = [];
                                            if (preg_match('/remarks\.(\d+)\./', $statePath, $matches)) {
                                                $index = (int) $matches[1];
                                                return 'Remark ' . ($index + 1);
                                            }
                                            return 'Remark';
                                        })
                                        ->placeholder('Enter repair issue details here')
                                        ->autosize()
                                        ->rows(3)
                                        ->required(),

                                    FileUpload::make('attachments')
                                        ->hiddenLabel(true)
                                        ->disk('public')
                                        ->directory('repair-attachments')
                                        ->visibility('public')
                                        ->multiple()
                                        ->maxFiles(3)
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->openable()
                                        ->downloadable()
                                        // Add these new settings
                                        ->preserveFilenames()
                                        ->enableOpen()
                                        ->enableDownload()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                            // Generate a formatted ID for the file name
                                            $formattedId = 'RP_' . now()->format('Ymd');

                                            // Get extension
                                            $extension = $file->getClientOriginalExtension();

                                            // Generate a unique identifier (timestamp) to avoid overwriting files
                                            $timestamp = now()->format('YmdHis');
                                            $random = rand(1000, 9999);

                                            return "{$formattedId}-REPAIR-{$timestamp}-{$random}.{$extension}";
                                        }),
                                ])
                            ])
                            ->itemLabel(fn() => __('Remark') . ' ' . ++self::$indexRemarkCounter)
                            ->addActionLabel('Add Additional Remark')
                            ->maxItems(5)
                            ->defaultItems(1)
                            // Use default() instead of beforeStateHydrated
                            ->default(function (?AdminRepair $record = null) {
                                // Add debugging
                                \Illuminate\Support\Facades\Log::info('Remarks default function called:', [
                                    'record_exists' => $record ? 'yes' : 'no',
                                    'remarks_type' => $record ? gettype($record->remarks) : 'n/a'
                                ]);

                                if (!$record || !$record->remarks) {
                                    return [];
                                }

                                // Process JSON string
                                if (is_string($record->remarks)) {
                                    $decoded = json_decode($record->remarks, true);

                                    // Check if it's a valid decoded array
                                    if (is_array($decoded)) {
                                        // Process each remark to decode attachments
                                        foreach ($decoded as $key => $remark) {
                                            // If attachments is a JSON string, decode it to an array
                                            if (isset($remark['attachments']) && is_string($remark['attachments'])) {
                                                $decodedAttachments = json_decode($remark['attachments'], true);
                                                if (is_array($decodedAttachments)) {
                                                    $decoded[$key]['attachments'] = $decodedAttachments;
                                                } else {
                                                    // If JSON decode fails, set to empty array
                                                    $decoded[$key]['attachments'] = [];
                                                }
                                            } elseif (!isset($remark['attachments'])) {
                                                // If attachments field doesn't exist, initialize it
                                                $decoded[$key]['attachments'] = [];
                                            }
                                        }

                                        // Log what we've processed
                                        \Illuminate\Support\Facades\Log::info('Decoded remarks with attachments:', [
                                            'remarks_count' => count($decoded),
                                            'sample' => isset($decoded[0]) ? array_keys($decoded[0]) : 'no records'
                                        ]);

                                        return $decoded;
                                    }

                                    // If decoding failed, return empty array
                                    return [];
                                }

                                // If it's already an array, make sure attachments are properly formatted
                                if (is_array($record->remarks)) {
                                    foreach ($record->remarks as $key => $remark) {
                                        if (isset($remark['attachments']) && is_string($remark['attachments'])) {
                                            $decodedAttachments = json_decode($remark['attachments'], true);
                                            if (is_array($decodedAttachments)) {
                                                $record->remarks[$key]['attachments'] = $decodedAttachments;
                                            } else {
                                                $record->remarks[$key]['attachments'] = [];
                                            }
                                        } elseif (!isset($remark['attachments'])) {
                                            $record->remarks[$key]['attachments'] = [];
                                        }
                                    }

                                    return $record->remarks;
                                }

                                return [];
                            })
                            // Use mutateDehydratedStateUsing to handle the data before it's saved
                            ->mutateDehydratedStateUsing(function ($state) {
                                if (is_array($state)) {
                                    // Process attachments in each remark
                                    foreach ($state as $key => $remark) {
                                        if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                                            $state[$key]['attachments'] = json_encode($remark['attachments']);
                                        }
                                    }

                                    // Encode the entire array as JSON
                                    return json_encode($state);
                                }

                                return $state;
                            }),
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // // For handover_pdf, extract filename
                        // if ($record->handover_pdf) {
                        //     // Extract just the filename without extension
                        //     $filename = basename($record->handover_pdf, '.pdf');
                        //     return $filename;
                        // }

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting logic that uses the raw ID value
                        return $query->orderBy('id', $direction);
                    }),

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
                        // If using the new devices field
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

                        // Fallback to legacy fields if no devices array
                        if ($record->device_model) {
                            return "{$record->device_model} (SN: {$record->device_serial})";
                        }

                        return '—';
                    })
                    ->html()
                    ->default(function (?AdminRepair $record = null) {
                        if (!$record) {
                            return [
                                ['device_model' => '', 'device_serial' => '']
                            ];
                        }

                        // If we have existing devices
                        if ($record->devices && is_array($record->devices)) {
                            return $record->devices;
                        }

                        // If no devices array but we have single device fields populated
                        if ($record->device_model || $record->device_serial) {
                            return [
                                [
                                    'device_model' => $record->device_model ?? '',
                                    'device_serial' => $record->device_serial ?? ''
                                ]
                            ];
                        }

                        return [
                            ['device_model' => '', 'device_serial' => '']
                        ];
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('device_model', 'like', "%{$search}%")
                            ->orWhere('device_serial', 'like', "%{$search}%")
                            ->orWhere('devices', 'like', "%{$search}%");
                    }),

                TextColumn::make('device_serial')
                    ->label('Serial Number')
                    ->searchable(),

                TextColumn::make('zoho_ticket')
                    ->label('Zoho Ticket')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'Awaiting Parts' => 'Awaiting Parts',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('viewRepairDetails')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->modalHeading(' ')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (AdminRepair $record): View {
                            return view('components.repair-detail')
                                ->with('record', $record);
                        }),

                    Action::make('uploadNewAttachment')
                        ->label('Upload New Attachment')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->form([
                            FileUpload::make('files')
                                ->required()
                                ->multiple()
                                ->disk('public')
                                ->directory('hardware-handover-attachments')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                ->maxSize(10240) // 10MB
                                ->maxFiles(10)
                                ->downloadable()
                                ->openable()
                                ->previewable()
                                ->reorderable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $date = now()->format('Y-m-d');
                                    $random = Str::random(8);
                                    $extension = $file->getClientOriginalExtension();
                                    return "attachment-{$date}-{$random}.{$extension}";
                                }),
                        ])
                        ->action(function (AdminRepair $record, array $data) {
                            // Get the handover record
                            $handover = $record;

                            // Check if new_attachment_file already exists
                            $existingFiles = $handover->new_attachment_file ?
                                (is_string($handover->new_attachment_file) ? json_decode($handover->new_attachment_file, true) : $handover->new_attachment_file) :
                                [];

                            // Add new files to existing files
                            $allFiles = array_merge($existingFiles, $data['files']);

                            // Update the handover record with new files
                            $handover->update([
                                'new_attachment_file' => json_encode($allFiles),
                            ]);

                            // Show success notification
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Attachment Uploaded')
                                ->body('New attachment files have been added successfully.')
                                ->send();
                        }),

                    // Tables\Actions\ViewAction::make()
                    //     ->visible(fn (AdminRepair $record): bool => $record->status !== 'Draft'),

                    // Tables\Actions\Action::make('print')
                    //     ->label('Print')
                    //     ->icon('heroicon-o-printer')
                    //     ->url(fn (AdminRepair $record) => route('admin.repairs.print', $record))
                    //     ->openUrlInNewTab(),
                ])
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminRepairs::route('/'),
            // 'create' => Pages\CreateAdminRepair::route('/create'),
            // 'edit' => Pages\EditAdminRepair::route('/{record}/edit'),
        ];
    }
}
