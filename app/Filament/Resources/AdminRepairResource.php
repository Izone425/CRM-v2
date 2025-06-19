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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AdminRepairResource extends Resource
{
    protected static ?string $model = AdminRepair::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationLabel = 'Admin Repair';
    protected static ?int $indexRemarkCounter = 0;

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
                    Select::make('device_model')
                        ->label('Device Model')
                        ->columnSpan(1)
                        ->options([
                            'TimeTec TA100C' => 'TimeTec TA100C',
                            'TimeTec TA100CR' => 'TimeTec TA100CR',
                            'TimeTec TA500' => 'TimeTec TA500',
                            'TimeTec TA700W' => 'TimeTec TA700W',
                            'TimeTec Face ID 2' => 'TimeTec Face ID 2',
                            'TimeTec Face ID 3' => 'TimeTec Face ID 3',
                            'TimeTec Face ID 4' => 'TimeTec Face ID 4',
                            'TimeTec Face ID 4d' => 'TimeTec Face ID 4d',
                            'TimeTec i-Clock 680' => 'TimeTec i-Clock 680',
                            'Other' => 'Other (Please specify in remarks)',
                        ])
                        ->searchable()
                        ->required(),

                    // FIELD 6 – DEVICE SERIAL NUMBER
                    TextInput::make('device_serial')
                        ->label('Device Serial Number')
                        ->columnSpan(1)
                        ->required()
                        ->maxLength(100),

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

                        // FIELD 8 – VIDEO DETAILS
                        Section::make('Video Details')
                        ->columnSpan(1)
                        ->schema([
                            FileUpload::make('video_files')
                            ->label('Upload Videos (MP4, MOV, AVI)')
                            ->disk('public')
                            ->directory('repair/videos')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(3)
                            ->maxSize(10000)
                            ->acceptedFileTypes([
                                'video/mp4',
                                'video/quicktime',
                                'video/x-msvideo',
                                'video/x-ms-wmv',
                                'video/webm'
                            ])
                            // Add these settings
                            ->preserveFilenames()
                            ->enableOpen()
                            ->enableDownload()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                // Get extension
                                $extension = $file->getClientOriginalExtension();

                                // Generate a unique identifier
                                $timestamp = now()->format('YmdHis');
                                $random = rand(1000, 9999);

                                return "RP-VIDEO-{$timestamp}-{$random}.{$extension}";
                            })
                            ->openable()
                            ->previewable()
                            ->downloadable()
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Ticket #')
                    ->sortable()
                    ->searchable(),

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

                TextColumn::make('device_model')
                    ->label('Device Model')
                    ->searchable(),

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
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminRepairs::route('/'),
            'create' => Pages\CreateAdminRepair::route('/create'),
            'edit' => Pages\EditAdminRepair::route('/{record}/edit'),
        ];
    }
}
