<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoftwareAttachmentResource\Pages;
use App\Models\SoftwareAttachment;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SoftwareAttachmentResource extends Resource
{
    protected static ?string $model = SoftwareHandover::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationLabel = 'Handover Attachments';

    protected static ?string $navigationGroup = 'Software Attachments';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Attachment Details')
                    ->schema([
                        Select::make('software_handover_id')
                            ->relationship('softwareHandover', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Software Handover ID'),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpan('full'),

                        FileUpload::make('files')
                            ->required()
                            ->multiple() // Allow multiple files
                            ->label('Files')
                            ->disk('public')
                            ->directory('software-handover-attachments')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->maxSize(10240) // 10MB
                            ->columnSpan('full')
                            ->downloadable() // Allow direct file downloads
                            ->openable() // Allow opening files in a new tab
                            ->previewable() // Enable file previews where possible
                            ->reorderable() // Allow reordering of multiple files
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                $title = Str::slug($get('title') ?? 'attachment');
                                $date = now()->format('Y-m-d');
                                $random = Str::random(5);
                                $extension = $file->getClientOriginalExtension();

                                return "{$title}-{$date}-{$random}.{$extension}";
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // Check if we have a record to work with
                                if (empty($state) && !$record) return;

                                // Get the raw DB value if available (this bypasses any accessor issues)
                                $rawFiles = $record ? $record->getRawOriginal('files') : $state;

                                // Parse the raw value (could be JSON string)
                                if (is_string($rawFiles) && (str_starts_with($rawFiles, '[') || str_starts_with($rawFiles, '{'))) {
                                    try {
                                        $parsedFiles = json_decode($rawFiles, true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            $rawFiles = $parsedFiles;
                                        }
                                    } catch (\Exception $e) {
                                        // If decoding fails, keep as is
                                    }
                                }

                                // Process the files to extract all paths
                                $processedFiles = [];

                                // Handle array of file paths
                                if (is_array($rawFiles)) {
                                    foreach ($rawFiles as $file) {
                                        // If it's a JSON string, decode it
                                        if (is_string($file) && (str_starts_with($file, '[') || str_starts_with($file, '{'))) {
                                            try {
                                                $decoded = json_decode($file, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    foreach ($decoded as $path) {
                                                        // Make sure it's a valid path
                                                        if (is_string($path) && !empty($path)) {
                                                            $processedFiles[] = $path;
                                                        }
                                                    }
                                                } else {
                                                    // If can't decode as array, use as is
                                                    $processedFiles[] = $file;
                                                }
                                            } catch (\Exception $e) {
                                                $processedFiles[] = $file;
                                            }
                                        } else if (is_string($file) && !empty($file)) {
                                            // Regular string path
                                            $processedFiles[] = $file;
                                        }
                                    }
                                } else if (is_string($rawFiles) && !empty($rawFiles)) {
                                    // Single string path
                                    $processedFiles[] = $rawFiles;
                                }

                                // Specially handle hrdf_grant files that might be missing
                                // This is a specific fix for the pattern you're seeing
                                $grantFilesInDb = [];
                                foreach ($processedFiles as $file) {
                                    if (str_contains($file, 'handovers/hrdf_grant/')) {
                                        $grantFilesInDb[] = $file;
                                    }
                                }

                                // Update the component state with the processed files
                                if (!empty($processedFiles)) {
                                    $component->state($processedFiles);
                                }
                            })
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function ($query) {
                $query->where('status', 'Completed');

                // Get user information
                $user = auth()->user();
                $userId = auth()->id();

                // Filter for salespeople (role_id = 2)
                if ($user->role_id === 2) {
                    // Since we're working directly with SoftwareHandover model now,
                    // we need to filter on the lead relationship directly
                    $query->whereHas('lead', function ($leadQuery) use ($userId) {
                        $leadQuery->where('salesperson', $userId);
                    });
                }
                // Filter for implementers (role_id = 4)
                elseif ($user->role_id === 4) {
                    // Get the implementer's name
                    $implementerName = $user->name;

                    // Filter where implementer field matches the current user's name
                    $query->where('implementer', $implementerName);
                }
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Format ID with prefix and padding
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                // // Display file count
                // TextColumn::make('files')
                //     ->label('File Count')
                //     ->formatStateUsing(function ($state) {
                //         if (empty($state)) return '0 files';
                //         $count = is_array($state) ? count($state) : 1;
                //         return "{$count} " . ($count == 1 ? 'file' : 'files');
                //     })
                //     ->sortable(),

                // ViewColumn::make('files')
                //     ->label('Files')
                //     ->view('filament.pages.file-list'),

                // TextColumn::make('lead.companyDetail.company_name')
                //     ->label('Company Name')
                //     ->formatStateUsing(function ($state, $record) {
                //         $fullName = $state ?? 'N/A';
                //         $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                //         $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                //         return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                //                     target="_blank"
                //                     title="' . e($fullName) . '"
                //                     class="inline-block"
                //                     style="color:#338cf0;">
                //                     ' . $shortened . '
                //                 </a>';
                //     })
                //     ->html(),

                TextColumn::make('company_name')
                    ->limit(20)
                    ->label('Company Name'),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('DB Creation Date')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('salesperson')
                ->label('Salesperson')
                ->options(function () {
                    return \App\Models\User::where('role_id', 2) // Assuming role_id 2 is for salespeople
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->query(function ($query, array $data) {
                    if (!isset($data['value']) || $data['value'] === '') {
                        return $query;
                    }

                    return $query->whereHas('softwareHandover.lead', function ($leadQuery) use ($data) {
                        $leadQuery->where('salesperson', $data['value']);
                    });
                })
                ->searchable(),

            // Implementer filter
            Tables\Filters\SelectFilter::make('implementer')
                ->label('Implementer')
                ->options(function () {
                    return \App\Models\User::where('role_id', 4) // Assuming role_id 4 is for implementers
                        ->orderBy('name')
                        ->pluck('name', 'name') // Change from id to name for both key and value
                        ->toArray();
                })
                ->query(function ($query, array $data) {
                    if (!isset($data['value']) || $data['value'] === '') {
                        return $query;
                    }

                    return $query->whereHas('softwareHandover', function ($handoverQuery) use ($data) {
                        $handoverQuery->where('implementer', $data['value']); // This now compares name to name
                    });
                })
                ->searchable(),

            // DB Creation Date Range filter
            Tables\Filters\Filter::make('completed_at')
                ->label('DB Creation Date')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label('From')
                        ->placeholder('Select start date'),
                    Forms\Components\DatePicker::make('until')
                        ->label('To')
                        ->placeholder('Select end date'),
                ])
                ->query(function ($query, array $data) {
                    if (!empty($data['from'])) {
                        $query->whereHas('softwareHandover', function ($handoverQuery) use ($data) {
                            $handoverQuery->whereDate('completed_at', '>=', $data['from']);
                        });
                    }

                    if (!empty($data['until'])) {
                        $query->whereHas('softwareHandover', function ($handoverQuery) use ($data) {
                            $handoverQuery->whereDate('completed_at', '<=', $data['until']);
                        });
                    }

                    return $query;
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if ($data['from'] ?? null) {
                        $indicators['from'] = 'From ' . Carbon::parse($data['from'])->format('d M Y');
                    }

                    if ($data['until'] ?? null) {
                        $indicators['until'] = 'To ' . Carbon::parse($data['until'])->format('d M Y');
                    }

                    return $indicators;
                }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn (SoftwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
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
                                ->directory('software-handover-attachments')
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
                        ->action(function (SoftwareHandover $record, array $data) {
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
                ])->button(),
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
            'index' => Pages\ListSoftwareAttachments::route('/'),
            // 'create' => Pages\CreateSoftwareAttachment::route('/create'),
            // 'edit' => Pages\EditSoftwareAttachment::route('/{record}/edit'),
        ];
    }
}
