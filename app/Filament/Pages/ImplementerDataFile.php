<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use App\Models\CompanyDetail;
use App\Models\DataFileUpload;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImplementerDataFile extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.implementer-data-file';
    protected static ?string $navigationLabel = 'Data Files';

    public function getTitle(): string
    {
        return 'Data File Management';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DataFileUpload::query())
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('module')
                    ->label('Module')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('file_type')
                    ->label('File Type')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'reference',
                        'success' => 'import',
                    ]),

                // BadgeColumn::make('status')
                //     ->colors([
                //         'warning' => 'Pending',
                //         'success' => 'Completed',
                //         'danger' => 'Failed',
                //         'primary' => 'Processing',
                //     ]),

                TextColumn::make('created_at')
                    ->label('Upload Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // SelectFilter::make('company_id')
                //     ->label('Company')
                //     ->relationship('companyDetail', 'company_name')
                //     ->searchable()
                //     ->preload(),

                SelectFilter::make('module')
                    ->options([
                        'EMPLOYEE PROFILE' => 'Employee Profile',
                        'ATTENDANCE MODULE' => 'Attendance Module',
                        'LEAVE MODULE' => 'Leave Module',
                        'CLAIM MODULE' => 'Claim Module',
                        'PAYROLL MODULE' => 'Payroll Module',
                    ]),

                SelectFilter::make('category')
                    ->options([
                        'import' => 'Import',
                        'reference' => 'Reference',
                    ]),

                // SelectFilter::make('status')
                //     ->options([
                //         'Pending' => 'Pending',
                //         'Processing' => 'Processing',
                //         'Completed' => 'Completed',
                //         'Failed' => 'Failed',
                //     ]),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (DataFileUpload $record) {
                        if (Storage::disk('public')->exists($record->file_path)) {
                            // Use the safe file name for download but correct path for retrieval
                            $downloadName = $record->file_name; // Display name
                            $filePath = $record->file_path; // Storage path

                            return response()->download(
                                storage_path('app/public/' . $filePath),
                                $downloadName
                            );
                        }

                        Notification::make()
                            ->title('File not found')
                            ->danger()
                            ->send();
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (DataFileUpload $record) {
                        if (Storage::disk('public')->exists($record->file_path)) {
                            Storage::disk('public')->delete($record->file_path);
                        }

                        $record->delete();

                        Notification::make()
                            ->title('File deleted successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if (Storage::disk('public')->exists($record->file_path)) {
                                Storage::disk('public')->delete($record->file_path);
                            }
                            $record->delete();
                        }

                        Notification::make()
                            ->title('Files deleted successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Upload New File')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('company_id')
                            ->label('Company')
                            ->options(function () {
                                // Get lead IDs from all three tables
                                $softwareHandoverLeadIds = \App\Models\SoftwareHandover::pluck('lead_id')->toArray();
                                $hardwareHandoverLeadIds = \App\Models\HardwareHandover::pluck('lead_id')->toArray();
                                $repairLeadIds = \App\Models\AdminRepair::pluck('lead_id')->toArray();

                                // Combine all lead IDs (removing duplicates)
                                $allLeadIds = array_unique(array_merge(
                                    $softwareHandoverLeadIds,
                                    $hardwareHandoverLeadIds,
                                    $repairLeadIds
                                ));

                                // Filter companies by these lead IDs
                                return CompanyDetail::whereIn('lead_id', $allLeadIds)
                                    ->orderBy('company_name')
                                    ->pluck('company_name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),

                        Select::make('module_and_type')
                            ->label('File Type')
                            ->options([
                                'EMPLOYEE PROFILE|IMPORT USER INFORMATION|import' => 'Employee Profile - Import User Information',
                                'ATTENDANCE MODULE|CLOCKING SCHEDULE TEMPLATE|reference' => 'Attendance Module - Clocking Schedule Template',
                                'LEAVE MODULE|USER LEAVE BALANCE|import' => 'Leave Module - User Leave Balance',
                                'LEAVE MODULE|USER LEAVE TAKEN|import' => 'Leave Module - User Leave Taken',
                                'LEAVE MODULE|LEAVE POLICY TEMPLATE|reference' => 'Leave Module - Leave Policy Template',
                                'CLAIM MODULE|CLAIM POLICY TEMPLATE|reference' => 'Claim Module - Claim Policy Template',
                                'PAYROLL MODULE|PAYROLL EMPLOYEE INFORMATION|import' => 'Payroll Module - Employee Information',
                                'PAYROLL MODULE|PAYROLL EMPLOYEE SALARY DATA|import' => 'Payroll Module - Employee Salary Data',
                                'PAYROLL MODULE|PAYROLL ACCUMULATED ITEM EA|import' => 'Payroll Module - Accumulated Item EA',
                                'PAYROLL MODULE|PAYROLL BASIC INFORMATION|reference' => 'Payroll Module - Basic Information',
                            ])
                            ->required(),

                        FileUpload::make('file_upload')
                            ->label('File')
                            ->disk('public')
                            ->directory('data-files')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                                'application/pdf'
                            ])
                            ->maxSize(10240) // 10MB
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        // Split the module_and_type into components
                        [$module, $fileType, $category] = explode('|', $data['module_and_type']);

                        // Get just the first uploaded file (if multiple were uploaded)
                        $uploadedFile = $data['file_upload'];
                        if (is_array($uploadedFile)) {
                            $uploadedFile = $uploadedFile[0];
                        }

                        // Get the original file name without any path information
                        $originalFileName = pathinfo(Storage::disk('public')->path($uploadedFile), PATHINFO_BASENAME);

                        // Remove any invalid characters from filename
                        $safeFileName = str_replace(['/', '\\'], '_', $originalFileName);

                        // Create the data file upload record
                        DataFileUpload::create([
                            'company_id' => $data['company_id'],
                            'module' => $module,
                            'file_type' => $fileType,
                            'category' => $category,
                            'file_name' => $safeFileName, // Safe file name with no slashes
                            'file_path' => $uploadedFile, // Just the storage path
                            'status' => 'Pending',
                            'uploaded_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('File uploaded successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('downloadTemplates')
                    ->label('Download Templates')
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Select::make('template_type')
                            ->label('Select Template')
                            ->options([
                                'EMPLOYEE PROFILE|IMPORT USER INFORMATION|import' => 'Employee Profile - Import User Information',
                                'ATTENDANCE MODULE|CLOCKING SCHEDULE TEMPLATE|reference' => 'Attendance Module - Clocking Schedule Template',
                                'LEAVE MODULE|USER LEAVE BALANCE|import' => 'Leave Module - User Leave Balance',
                                'LEAVE MODULE|USER LEAVE TAKEN|import' => 'Leave Module - User Leave Taken',
                                'LEAVE MODULE|LEAVE POLICY TEMPLATE|reference' => 'Leave Module - Leave Policy Template',
                                'CLAIM MODULE|CLAIM POLICY TEMPLATE|reference' => 'Claim Module - Claim Policy Template',
                                'PAYROLL MODULE|PAYROLL EMPLOYEE INFORMATION|import' => 'Payroll Module - Employee Information',
                                'PAYROLL MODULE|PAYROLL EMPLOYEE SALARY DATA|import' => 'Payroll Module - Employee Salary Data',
                                'PAYROLL MODULE|PAYROLL ACCUMULATED ITEM EA|import' => 'Payroll Module - Accumulated Item EA',
                                'PAYROLL MODULE|PAYROLL BASIC INFORMATION|reference' => 'Payroll Module - Basic Information',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        // In a real implementation, this would download the template
                        // Here we'll just display a notification
                        [$module, $fileType, $category] = explode('|', $data['template_type']);

                        Notification::make()
                            ->title("Template {$fileType} downloaded")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
