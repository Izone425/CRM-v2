<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HardwareAttachmentResource\Pages;
use App\Models\HardwareAttachment;
use App\Models\HardwareHandover;
use App\Models\User;
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

class HardwareAttachmentResource extends Resource
{
    protected static ?string $model = HardwareAttachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationLabel = 'Handover Attachments';

    protected static ?string $navigationGroup = 'Hardware Handovers';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Attachment Details')
                    ->schema([
                        Select::make('hardware_handover_id')
                            ->relationship('hardwareHandover', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Hardware Handover ID'),

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
                            ->directory('hardware-handover-attachments')
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
                if (auth()->user()->role_id === 2) {
                    $userId = auth()->id();
                    $query->whereHas('hardwareHandover.lead', function ($leadQuery) use ($userId) {
                        $leadQuery->where('salesperson', $userId);
                    });
                }
            })
            ->columns([
                TextColumn::make('index')
                    ->label('ID')
                    ->rowIndex(),

                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareAttachment $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Format ID with prefix and padding
                        return '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareAttachment $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record->hardwareHandover]);
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

                TextColumn::make('hardwareHandover.lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 20, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->hardwareHandover->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareAttachment $record) {
                        $lead = $record->hardwareHandover->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    }),

                TextColumn::make('hardwareHandover.implementer')
                    ->label('Implementer')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('hardwareHandover.completed_at')
                    ->label('Completed Date & Time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('hardware_handover_id')
                //     ->relationship('hardwareHandover', 'id')
                //     ->label('Handover ID')
                //     ->searchable()
                //     ->preload(),

                // Tables\Filters\SelectFilter::make('created_by')
                //     ->relationship('creator', 'name')
                //     ->label('Created By')
                //     ->searchable()
                //     ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    // Tables\Actions\EditAction::make(),
                    // Tables\Actions\DeleteAction::make(),
                ]),
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
            'index' => Pages\ListHardwareAttachments::route('/'),
            // 'create' => Pages\CreateHardwareAttachment::route('/create'),
            // 'edit' => Pages\EditHardwareAttachment::route('/{record}/edit'),
        ];
    }
}
