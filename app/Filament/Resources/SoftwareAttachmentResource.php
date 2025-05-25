<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoftwareAttachmentResource\Pages;
use App\Models\SoftwareAttachment;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;

class SoftwareAttachmentResource extends Resource
{
    protected static ?string $model = SoftwareAttachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationLabel = 'Handover Attachments';

    protected static ?string $navigationGroup = 'Software Handovers';

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

                                // Log the raw database value for debugging
                                info('Raw files from database:', ['raw' => $rawFiles]);

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

                                // Log the results for debugging
                                info('Processed files:', ['files' => $processedFiles, 'count' => count($processedFiles)]);

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
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('softwareHandover.id')
                    ->label('Handover ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                // Display file count
                TextColumn::make('files')
                    ->label('File Count')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return '0 files';
                        $count = is_array($state) ? count($state) : 1;
                        return "{$count} " . ($count == 1 ? 'file' : 'files');
                    })
                    ->sortable(),

                ViewColumn::make('files')
                    ->label('Files')
                    ->view('filament.pages.file-list'),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('software_handover_id')
                    ->relationship('softwareHandover', 'id')
                    ->label('Handover ID')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('created_by')
                    ->relationship('creator', 'name')
                    ->label('Created By')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListSoftwareAttachments::route('/'),
            'create' => Pages\CreateSoftwareAttachment::route('/create'),
            'edit' => Pages\EditSoftwareAttachment::route('/{record}/edit'),
        ];
    }
}
