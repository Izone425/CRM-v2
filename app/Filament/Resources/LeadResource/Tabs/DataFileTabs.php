<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\DataFile;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Card;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataFileTabs
{
    // Define categories based on the flowchart
    public static $categories = [
        'employee_profile' => [
            'name' => 'EMPLOYEE PROFILE',
            'subcategories' => [
                'user_information' => 'USER INFORMATION',
            ]
        ],
        'attendance_module' => [
            'name' => 'ATTENDANCE MODULE',
            'subcategories' => [
                'clocking_schedule' => 'CLOCKING SCHEDULE',
            ]
        ],
        'leave_module' => [
            'name' => 'LEAVE MODULE',
            'subcategories' => [
                'user_leave_balance' => 'USER LEAVE BALANCE',
                'user_leave_taken' => 'USER LEAVE TAKEN',
                'leave_policy_template' => 'LEAVE POLICY TEMPLATE',
            ]
        ],
        'claim_module' => [
            'name' => 'CLAIM MODULE',
            'subcategories' => [
                'claim_policy_template' => 'CLAIM POLICY TEMPLATE',
            ]
        ],
        'payroll_module' => [
            'name' => 'PAYROLL MODULE',
            'subcategories' => [
                'payroll_employee_info' => 'PAYROLL EMPLOYEE INFO',
                'payroll_employee_salary_data' => 'PAYROLL EMPLOYEE SALARY DATA',
                'payroll_accumulated_item_ea' => 'PAYROLL ACCUMULATED ITEM EA',
                'payroll_basic_information' => 'PAYROLL BASIC INFORMATION',
            ]
        ],
    ];

    public static function getSchema(): array
    {
        $sections = [];

        // EMPLOYEE PROFILE SECTION
        $sections[] = Section::make('Employee Profile Files')
            ->description('View and manage employee profile files')
            ->headerActions([
                Action::make('edit_employee_profile')
                    ->label('Upload')
                    ->modalHeading('Upload Employee Profile Files')
                    ->visible(fn (Lead $lead) => $lead->id)
                    ->modalSubmitActionLabel('Upload Files')
                    ->form([
                        // Create upload fields for each subcategory
                        ...array_map(function ($subKey, $subName) {
                            return FileUpload::make($subKey)
                                ->label($subName)
                                ->directory("data-files/employee_profile")
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/zip', 'application/x-zip-compressed'])
                                ->maxSize(10240) // 10MB
                                ->multiple()
                                ->helperText('Upload .pdf, .doc, .docx, .xls, .xlsx, .csv or .zip files (max 10MB)');
                        }, array_keys(self::$categories['employee_profile']['subcategories']), self::$categories['employee_profile']['subcategories'])
                    ])
                    ->action(function (Lead $lead, array $data) {
                        foreach ($data as $subKey => $files) {
                            if (!$files) continue;

                            foreach ($files as $file) {
                                DataFile::create([
                                    'lead_id' => $lead->id,
                                    'filename' => $file,
                                    'category' => 'employee_profile',
                                    'subcategory' => $subKey,
                                    'uploaded_by' => auth()->id(),
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Files uploaded successfully')
                            ->success()
                            ->send();

                        // Force page refresh to show updated files
                        return redirect(request()->header('Referer'));
                    }),
            ])
            ->schema([
                View::make('components.data-files.employee-profile')
                    ->visible(function ($record) {
                        return $record && $record->id &&
                            DataFile::where('lead_id', $record->id)
                                ->where('category', 'employee_profile')
                                ->exists();
                    }),
            ])
            ->collapsed(fn ($record) => !self::hasFilesInCategory($record, 'employee_profile'))
            ->collapsible();

        // ATTENDANCE MODULE SECTION
        $sections[] = Section::make('Attendance Module Files')
            ->description('View and manage attendance module files')
            ->headerActions([
                Action::make('edit_attendance_module')
                    ->label('Upload')
                    ->modalHeading('Upload Attendance Module Files')
                    ->visible(fn (Lead $lead) => $lead->id)
                    ->modalSubmitActionLabel('Upload Files')
                    ->form([
                        // Create upload fields for each subcategory
                        ...array_map(function ($subKey, $subName) {
                            return FileUpload::make($subKey)
                                ->label($subName)
                                ->directory("data-files/attendance_module")
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/zip', 'application/x-zip-compressed'])
                                ->maxSize(10240) // 10MB
                                ->multiple()
                                ->helperText('Upload .pdf, .doc, .docx, .xls, .xlsx, .csv or .zip files (max 10MB)');
                        }, array_keys(self::$categories['attendance_module']['subcategories']), self::$categories['attendance_module']['subcategories'])
                    ])
                    ->action(function (Lead $lead, array $data) {
                        foreach ($data as $subKey => $files) {
                            if (!$files) continue;

                            foreach ($files as $file) {
                                DataFile::create([
                                    'lead_id' => $lead->id,
                                    'filename' => $file,
                                    'category' => 'attendance_module',
                                    'subcategory' => $subKey,
                                    'uploaded_by' => auth()->id(),
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Files uploaded successfully')
                            ->success()
                            ->send();

                        // Force page refresh to show updated files
                        return redirect(request()->header('Referer'));
                    }),
            ])
            ->schema([
                View::make('components.data-files.attendance-module')
                    ->visible(function ($record) {
                        return $record && $record->id &&
                            DataFile::where('lead_id', $record->id)
                                ->where('category', 'attendance_module')
                                ->exists();
                    }),
            ])
            ->collapsed(fn ($record) => !self::hasFilesInCategory($record, 'attendance_module'))
            ->collapsible();

        // LEAVE MODULE SECTION
        $sections[] = Section::make('Leave Module Files')
            ->description('View and manage leave module files')
            ->headerActions([
                Action::make('edit_leave_module')
                    ->label('Upload')
                    ->modalHeading('Upload Leave Module Files')
                    ->visible(fn (Lead $lead) => $lead->id)
                    ->modalSubmitActionLabel('Upload Files')
                    ->form([
                        // Create upload fields for each subcategory
                        ...array_map(function ($subKey, $subName) {
                            return FileUpload::make($subKey)
                                ->label($subName)
                                ->directory("data-files/leave_module")
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/zip', 'application/x-zip-compressed'])
                                ->maxSize(10240) // 10MB
                                ->multiple()
                                ->helperText('Upload .pdf, .doc, .docx, .xls, .xlsx, .csv or .zip files (max 10MB)');
                        }, array_keys(self::$categories['leave_module']['subcategories']), self::$categories['leave_module']['subcategories'])
                    ])
                    ->action(function (Lead $lead, array $data) {
                        foreach ($data as $subKey => $files) {
                            if (!$files) continue;

                            foreach ($files as $file) {
                                DataFile::create([
                                    'lead_id' => $lead->id,
                                    'filename' => $file,
                                    'category' => 'leave_module',
                                    'subcategory' => $subKey,
                                    'uploaded_by' => auth()->id(),
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Files uploaded successfully')
                            ->success()
                            ->send();

                        // Force page refresh to show updated files
                        return redirect(request()->header('Referer'));
                    }),
            ])
            ->schema([
                View::make('components.data-files.leave-module')
                    ->visible(function ($record) {
                        return $record && $record->id &&
                            DataFile::where('lead_id', $record->id)
                                ->where('category', 'leave_module')
                                ->exists();
                    }),
            ])
            ->collapsed(fn ($record) => !self::hasFilesInCategory($record, 'leave_module'))
            ->collapsible();

        // CLAIM MODULE SECTION
        $sections[] = Section::make('Claim Module Files')
            ->description('View and manage claim module files')
            ->headerActions([
                Action::make('edit_claim_module')
                    ->label('Upload')
                    ->modalHeading('Upload Claim Module Files')
                    ->visible(fn (Lead $lead) => $lead->id)
                    ->modalSubmitActionLabel('Upload Files')
                    ->form([
                        // Create upload fields for each subcategory
                        ...array_map(function ($subKey, $subName) {
                            return FileUpload::make($subKey)
                                ->label($subName)
                                ->directory("data-files/claim_module")
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/zip', 'application/x-zip-compressed'])
                                ->maxSize(10240) // 10MB
                                ->multiple()
                                ->helperText('Upload .pdf, .doc, .docx, .xls, .xlsx, .csv or .zip files (max 10MB)');
                        }, array_keys(self::$categories['claim_module']['subcategories']), self::$categories['claim_module']['subcategories'])
                    ])
                    ->action(function (Lead $lead, array $data) {
                        foreach ($data as $subKey => $files) {
                            if (!$files) continue;

                            foreach ($files as $file) {
                                DataFile::create([
                                    'lead_id' => $lead->id,
                                    'filename' => $file,
                                    'category' => 'claim_module',
                                    'subcategory' => $subKey,
                                    'uploaded_by' => auth()->id(),
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Files uploaded successfully')
                            ->success()
                            ->send();

                        // Force page refresh to show updated files
                        return redirect(request()->header('Referer'));
                    }),
            ])
            ->schema([
                View::make('components.data-files.claim-module')
                    ->visible(function ($record) {
                        return $record && $record->id &&
                            DataFile::where('lead_id', $record->id)
                                ->where('category', 'claim_module')
                                ->exists();
                    }),
            ])
            ->collapsed(fn ($record) => !self::hasFilesInCategory($record, 'claim_module'))
            ->collapsible();

        // PAYROLL MODULE SECTION
        $sections[] = Section::make('Payroll Module Files')
            ->description('View and manage payroll module files')
            ->headerActions([
                Action::make('edit_payroll_module')
                    ->label('Upload')
                    ->modalHeading('Upload Payroll Module Files')
                    ->visible(fn (Lead $lead) => $lead->id)
                    ->modalSubmitActionLabel('Upload Files')
                    ->form([
                        // Create upload fields for each subcategory
                        ...array_map(function ($subKey, $subName) {
                            return FileUpload::make($subKey)
                                ->label($subName)
                                ->directory("data-files/payroll_module")
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/zip', 'application/x-zip-compressed'])
                                ->maxSize(10240) // 10MB
                                ->multiple()
                                ->helperText('Upload .pdf, .doc, .docx, .xls, .xlsx, .csv or .zip files (max 10MB)');
                        }, array_keys(self::$categories['payroll_module']['subcategories']), self::$categories['payroll_module']['subcategories'])
                    ])
                    ->action(function (Lead $lead, array $data) {
                        foreach ($data as $subKey => $files) {
                            if (!$files) continue;

                            foreach ($files as $file) {
                                DataFile::create([
                                    'lead_id' => $lead->id,
                                    'filename' => $file,
                                    'category' => 'payroll_module',
                                    'subcategory' => $subKey,
                                    'uploaded_by' => auth()->id(),
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Files uploaded successfully')
                            ->success()
                            ->send();

                        // Force page refresh to show updated files
                        return redirect(request()->header('Referer'));
                    }),
            ])
            ->schema([
                View::make('components.data-files.payroll-module')
                    ->visible(function ($record) {
                        return $record && $record->id &&
                            DataFile::where('lead_id', $record->id)
                                ->where('category', 'payroll_module')
                                ->exists();
                    }),
            ])
            ->collapsed(fn ($record) => !self::hasFilesInCategory($record, 'payroll_module'))
            ->collapsible();

        // Organize sections into 2-column layout
        $leftColumns = [];
        $rightColumns = [];

        foreach ($sections as $index => $section) {
            if ($index % 2 === 0) {
                $leftColumns[] = $section;
            } else {
                $rightColumns[] = $section;
            }
        }

        // Return a 2-column grid containing all sections
        return [
            Grid::make(2)
                ->schema([
                    Grid::make(1)
                        ->schema($leftColumns)
                        ->columnSpan(1),
                    Grid::make(1)
                        ->schema($rightColumns)
                        ->columnSpan(1),
                ]),
        ];
    }

    // Helper method to check if a record has files in a category
    protected static function hasFilesInCategory($record, string $category): bool
    {
        if (!$record || !$record->id) {
            return false;
        }

        return DataFile::where('lead_id', $record->id)
            ->where('category', $category)
            ->exists();
    }

    // Helper method to create file upload fields for a specific category
    protected static function getUploadFields(string $categoryKey): array
    {
        $fields = [];
        $category = self::$categories[$categoryKey] ?? null;

        if (!$category) {
            return [];
        }

        foreach ($category['subcategories'] as $subKey => $subName) {
            $fields[] = FileUpload::make($subKey)
                ->label($subName)
                ->directory("data-files/{$categoryKey}")
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/zip', 'application/x-zip-compressed'])
                ->maxSize(10240) // 10MB
                ->multiple()
                ->enableOpen()
                ->enableDownload()
                ->helperText('Upload .pdf, .doc, .docx, .xls, .xlsx, .csv or .zip files (max 10MB)')
                ->afterStateUpdated(function ($state, callable $set, Lead $record, string $statePath) use ($categoryKey, $subKey) {
                    if (!$state) return;

                    // Log activity when files are uploaded
                    $fileCount = count($state);
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($record)
                        ->log("Uploaded {$fileCount} file(s) to {$subKey}");

                    // Store reference in DataFile model
                    if ($record->id) {
                        // First, remove any old files that are no longer in the state
                        $existingFiles = DataFile::where('lead_id', $record->id)
                            ->where('category', $categoryKey)
                            ->where('subcategory', $subKey)
                            ->pluck('filename')
                            ->toArray();

                        $filesToDelete = array_diff($existingFiles, $state);

                        foreach ($filesToDelete as $fileToDelete) {
                            DataFile::where('lead_id', $record->id)
                                ->where('filename', $fileToDelete)
                                ->delete();
                        }

                        // Then add the new files
                        foreach ($state as $filename) {
                            DataFile::updateOrCreate(
                                [
                                    'lead_id' => $record->id,
                                    'filename' => $filename,
                                ],
                                [
                                    'category' => $categoryKey,
                                    'subcategory' => $subKey,
                                    'uploaded_by' => auth()->id(),
                                ]
                            );
                        }
                    }

                    Notification::make()
                        ->title('Files uploaded successfully')
                        ->success()
                        ->send();

                    // Force page refresh to show updated files
                    return redirect(request()->header('Referer'));
                })
                ->afterStateHydrated(function ($component, $state, $record) use ($categoryKey, $subKey) {
                    // Skip if no record or record ID
                    if (!$record || !$record->id) {
                        return;
                    }

                    // Retrieve files from database
                    $files = DataFile::where('lead_id', $record->id)
                        ->where('category', $categoryKey)
                        ->where('subcategory', $subKey)
                        ->pluck('filename')
                        ->toArray();

                    if (!empty($files)) {
                        $component->state($files);
                    }
                });
        }

        return $fields;
    }
}
