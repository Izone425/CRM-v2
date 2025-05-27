<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoftwareHandoverResource\Pages;
use App\Filament\Resources\SoftwareHandoverResource\RelationManagers;
use App\Models\SoftwareHandover;
use App\Services\CategoryService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\View\View;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Illuminate\Support\Str;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SoftwareHandoverResource extends Resource
{
    protected static ?string $model = SoftwareHandover::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Section: Company Details
            Section::make('Company Information')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->readonly()
                                ->maxLength(255),

                            TextInput::make('pic_name')
                                ->label('Name')
                                ->readonly()
                                ->maxLength(255),

                            TextInput::make('pic_phone')
                                ->label('HP Number')
                                ->readonly()
                                ->maxLength(20),
                        ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('salesperson')
                                ->label('Salesperson')
                                ->placeholder('Select salesperson')
                                ->readonly(),

                            TextInput::make('headcount')
                                ->numeric()
                                ->readonly(),

                            TextInput::make('category')
                                ->label('Company Size')
                                ->formatStateUsing(function ($state, $record) {
                                    // If the record has headcount, derive category from it
                                    if ($record && isset($record->headcount)) {
                                        $categoryService = app(CategoryService::class);
                                        return $categoryService->retrieve($record->headcount);
                                    }

                                    // Otherwise, return the stored category value
                                    return $state;
                                })
                                ->dehydrated(false)
                                ->readonly()
                        ]),
                ]),

            Grid::make(6)
            ->schema([
                // Section: Modules
                Section::make('Module Selection')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)
                        ->schema([
                            Checkbox::make('ta')
                                ->label('Time Attendance (TA)')
                                ->inline(),

                            Checkbox::make('tapp')
                                ->label('TimeTec Access (T-APP)')
                                ->inline(),

                            Checkbox::make('tl')
                                ->label('TimeTec Leave (TL)')
                                ->inline(),

                            Checkbox::make('thire')
                                ->label('TimeTec Hire (T-HIRE)')
                                ->inline(),

                            Checkbox::make('tc')
                                ->label('TimeTec Claim (TC)')
                                ->inline(),

                            Checkbox::make('tacc')
                                ->label('TimeTec Access (T-ACC)')
                                ->inline(),

                            Checkbox::make('tp')
                                ->label('TimeTec Payroll (TP)')
                                ->inline(),

                            Checkbox::make('tpbi')
                                ->label('TimeTec PBI (TPBI)')
                                ->inline(),
                        ])
                    ]),

                // Section: Implementation Details
                Section::make('Implementation Timeline')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('db_creation')
                                    ->label('Database Creation')
                                    ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                    ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y

                                DatePicker::make('kick_off_meeting')
                                    ->label('Kick Off Meeting')
                                    ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                    ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('webinar_training')
                                    ->label('Online Webinar Training')
                                    ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                    ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y

                                DatePicker::make('go_live_date')
                                    ->label('System Go Live')
                                    ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                    ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y
                            ]),
                    ]),

                Section::make('Training Information')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('implementer')
                            ->label('Implementer')
                            ->maxLength(255),
                        TextInput::make('payroll_code')
                            ->label('Payroll Code')
                            ->maxLength(50),
                    ]),
                Section::make('Handover Status')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'New' => 'New',
                                'Approved' => 'Approved',
                                'Completed' => 'Completed',
                                'Rejected' => 'Rejected',
                                'Draft' => 'Draft',
                            ])
                            ->default('New')
                            ->required(),
                        TextInput::make('formatted_date')
                            ->label('Action Date')
                            ->formatStateUsing(function ($state, $record) {
                                return $record->updated_at ? \Carbon\Carbon::parse($record->updated_at)->format('d M Y') : '-';
                            })
                            ->disabled()
                            ->dehydrated(false)
                    ]),
            ]),
        ]);
}


    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query
                    ->where('status', '=', 'Completed')
                    ->orderBy('created_at', 'desc');
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name'),
                    // ->formatStateUsing(function ($state, $record) {
                    //     $fullName = $state ?? $record->company_name ?? 'N/A';

                    //     // Only create the link if lead and lead.id exist
                    //     if ($record->lead && $record->lead->id) {
                    //         $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);
                    //         return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                    //                 target="_blank"
                    //                 title="View lead details"
                    //                 class="inline-block"
                    //                 style="color:#338cf0;">
                    //                 ' . $fullName . '
                    //             </a>';
                    //     }

                    //     // Otherwise, just display the company name without a link
                    //     return $fullName;
                    // })
                    // ->html(),

                    TextColumn::make('ta')
                        ->label('TA')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tl')
                        ->label('TL')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tc')
                        ->label('TC')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tp')
                        ->label('TP')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tapp')
                        ->label('TAPP')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('thire')
                        ->label('THIRE')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tacc')
                        ->label('TACC')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                    TextColumn::make('tpbi')
                        ->label('TPBI')
                        ->formatStateUsing(function ($state) {
                            return $state
                                ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                                : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                        })
                        ->toggleable(),

                TextColumn::make('payroll_code')
                    ->label('Payroll Code')
                    ->toggleable(),
                    TextColumn::make('company_size_label')
                    ->label('Company Size')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && isset($record->headcount)) {
                            $categoryService = app(CategoryService::class);
                            return $categoryService->retrieve($record->headcount);
                        }
                        return $state ?? 'N/A';
                    })
                    ->toggleable(),
                TextColumn::make('headcount')
                    ->label('Headcount')
                    ->toggleable(),
                TextColumn::make('db_creation')
                    ->label('DB Creation')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('go_live_date')
                    ->label('Go Live Date')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('total_days')
                    ->label('Total Days')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->toggleable(),
                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->toggleable(),
                TextColumn::make('kick_off_meeting')
                    ->label('ON9 Kick Off Meeting')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('webinar_training')
                    ->label('ON9 Webinar Training')
                    ->date('d M Y')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('created_at')
                ->form([
                    DateRangePicker::make('date_range')
                        ->label('')
                        ->placeholder('Select date range'),
                ])
                ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range from the "start - end" format
                        [$start, $end] = explode(' - ', $data['date_range']);

                        // Ensure valid dates
                        $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                        $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                        // Apply the filter
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                })
                ->indicateUsing(function (array $data) {
                    if (!empty($data['date_range'])) {
                        // Parse the date range for display
                        [$start, $end] = explode(' - ', $data['date_range']);

                        return 'From: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                            ' To: ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                    }
                    return null;
                }),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('create_attachment')
                ->label('Create Attachment')
                ->icon('heroicon-o-paper-clip')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->label('Attachment Title')
                        ->default(function (SoftwareHandover $record) {
                            return "Files for {$record->company_name}";
                        })
                        ->required(),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->default(function (SoftwareHandover $record) {
                            return "Combined files for {$record->company_name} (Handover #{$record->id})";
                        }),
                ])
                ->action(function (array $data, SoftwareHandover $record) {
                    // Collect all available files from the handover
                    $allFiles = [];

                    // Add invoice files if available
                    if (!empty($record->invoice_file)) {
                        $allFiles = array_merge($allFiles, is_array($record->invoice_file) ? $record->invoice_file : [$record->invoice_file]);
                    }

                    // Add confirmation order files if available
                    if (!empty($record->confirmation_order_file)) {
                        $allFiles = array_merge($allFiles, is_array($record->confirmation_order_file) ? $record->confirmation_order_file : [$record->confirmation_order_file]);
                    }

                    // Add HRDF grant files if available
                    if (!empty($record->hrdf_grant_file)) {
                        $allFiles = array_merge($allFiles, is_array($record->hrdf_grant_file) ? $record->hrdf_grant_file : [$record->hrdf_grant_file]);
                    }

                    // Add payment slip files if available
                    if (!empty($record->payment_slip_file)) {
                        $allFiles = array_merge($allFiles, is_array($record->payment_slip_file) ? $record->payment_slip_file : [$record->payment_slip_file]);
                    }

                    // Check if any files are available
                    if (empty($allFiles)) {
                        Notification::make()
                            ->title('No files available')
                            ->body("This handover has no files to create an attachment from.")
                            ->danger()
                            ->send();
                        return;
                    }

                    // Create a new software attachment with all files
                    $attachment = \App\Models\SoftwareAttachment::create([
                        'software_handover_id' => $record->id,
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'files' => $allFiles, // Add all collected files
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id()
                    ]);

                    // Show success notification
                    if ($attachment) {
                        $fileCount = count($allFiles);
                        Notification::make()
                            ->title('Attachment Created')
                            ->body("Successfully created attachment with {$fileCount} file" . ($fileCount != 1 ? 's' : '') . ".")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error')
                            ->body('Failed to create attachment.')
                            ->danger()
                            ->send();
                    }
                })
                ->visible(function (SoftwareHandover $record): bool {
                    // Only show this action if the record has any files
                    return !empty($record->invoice_file) ||
                        !empty($record->confirmation_order_file) ||
                        !empty($record->hrdf_grant_file) ||
                        !empty($record->payment_slip_file);
                })
                ->requiresConfirmation()
                ->modalHeading('Create Attachment with All Files')
                ->modalDescription('This will create a single attachment containing all files from this handover.')
                ->modalSubmitActionLabel('Create Attachment'),
            ]);
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ]);
    }

    // public static function getRelations(): array
    // {
    //     return [
    //         //
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoftwareHandovers::route('/'),
            'view' => Pages\ViewSoftwareHandover::route('/{record}'),
            // 'create' => Pages\CreateSoftwareHandover::route('/create'),
            // 'edit' => Pages\EditSoftwareHandover::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
