<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HardwarePendingStockResource\Pages;
use App\Filament\Resources\HardwarePendingStockResource\RelationManagers;
use App\Models\CompanyDetail;
use App\Models\HardwareAttachment;
use App\Models\HardwareHandover;
use App\Models\User;
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
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HardwarePendingStockResource extends Resource
{
    protected static ?string $model = HardwareHandover::class;

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
                    ->where('status', '=', 'Pending Stock')
                    ->orderBy('created_at', 'desc');

                if (auth()->user()->role_id === 2) {
                    $userId = auth()->id();
                    $query->whereHas('lead', function ($leadQuery) use ($userId) {
                        $leadQuery->where('salesperson', $userId);
                    });
                }
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting logic that uses the raw ID value
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandover $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.companyDetail.company_name')
                    ->searchable()
                    ->label('Company Name')
                    ->url(function ($state, $record) {
                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                            return url('admin/leads/' . $encryptedId);
                        }

                        return null;
                    })
                    ->openUrlInNewTab()
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return strtoupper(Str::limit($state, 30, '...'));
                        }

                        if ($record->lead && $record->lead->companyDetail) {
                            return strtoupper(Str::limit($record->lead->companyDetail->company_name, 30, '...'));
                        }

                        return $record->company_name ? strtoupper(Str::limit($record->company_name, 30, '...')) : '-';
                    })
                    ->color(function ($record) {
                        if ($record->lead && $record->lead->companyDetail) {
                            return Color::hex('#338cf0');
                        }

                        return Color::hex("#000000");
                    }),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    }),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->toggleable(),

                TextColumn::make('tc10_quantity')
                    ->label('TC10')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tc20_quantity')
                    ->label('TC20')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('face_id5_quantity')
                    ->label('FACE ID 5')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('face_id6_quantity')
                    ->label('FACE ID 6')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('time_beacon_quantity')
                    ->label('TIME BEACON')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nfc_tag_quantity')
                    ->label('NFC TAG')
                    ->numeric(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date Submit')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('pending_stock_at')
                    ->label('Date Pending Stock')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('pending_migration_at')
                    ->label('Date Pending Migration')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('Date Completed')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                // TextColumn::make('installation_type')
                //     ->label('Category 1')
                //     ->formatStateUsing(function ($state) {
                //         return match ($state) {
                //             'courier' => 'Courier',
                //             'internal_installation' => 'Internal Installation',
                //             'external_installation' => 'External Installation',
                //             default => ucfirst($state),
                //         };
                //     })
                //     ->toggleable(),

                // TextColumn::make('category2')
                //     ->label('Category 2')
                //     ->formatStateUsing(function ($state, HardwareHandover $record) {
                //         // If empty, return a placeholder
                //         if (empty($state)) {
                //             return '-';
                //         }

                //         // Decode JSON if it's a string
                //         $data = is_string($state) ? json_decode($state, true) : $state;

                //         // Format based on installation type
                //         if ($record->installation_type === 'courier') {
                //             $parts = [];

                //             if (!empty($data['email'])) {
                //                 $parts[] = "Email: {$data['email']}";
                //             }

                //             if (!empty($data['pic_name'])) {
                //                 $parts[] = "Name: {$data['pic_name']}";
                //             }

                //             if (!empty($data['pic_phone'])) {
                //                 $parts[] = "Phone: {$data['pic_phone']}";
                //             }

                //             if (!empty($data['courier_address'])) {
                //                 $parts[] = "Address: {$data['courier_address']}";
                //             }

                //             // Return the formatted parts with HTML line breaks instead of pipes
                //             return !empty($parts)
                //                 ? new HtmlString(implode('<br>', $parts))
                //                 : 'No courier details';
                //         }
                //         elseif ($record->installation_type === 'internal_installation') {
                //             if (!empty($data['installer'])) {
                //                 $installer = \App\Models\Installer::find($data['installer']);
                //                 return $installer ? $installer->company_name : 'Unknown Installer';
                //             }
                //             return 'No installer selected';
                //         }
                //         elseif ($record->installation_type === 'external_installation') {
                //             if (!empty($data['reseller'])) {
                //                 $reseller = \App\Models\Reseller::find($data['reseller']);
                //                 return $reseller ? $reseller->company_name : 'Unknown Reseller';
                //             }
                //             return 'No reseller selected';
                //         }

                //         // Fallback for any other case
                //         return json_encode($data);
                //     })
                //     ->wrap()
                //     ->html() // Important: Add this to render the HTML content
                //     ->toggleable(),
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
            ]);
            // ->actions([
            //     // Tables\Actions\EditAction::make(),
            //     Tables\Actions\Action::make('create_attachment')
            //     ->label('Create Attachment')
            //     ->icon('heroicon-o-paper-clip')
            //     ->color('success')
            //     ->form([
            //         Forms\Components\TextInput::make('title')
            //             ->label('Attachment Title')
            //             ->default(function (HardwareHandover $record) {
            //                 return "Files for {$record->company_name}";
            //             })
            //             ->required(),

            //         Forms\Components\Textarea::make('description')
            //             ->label('Description')
            //             ->default(function (HardwareHandover $record) {
            //                 return "Combined files for {$record->company_name} (Handover #{$record->id})";
            //             }),
            //     ])
            //     ->action(function (array $data, HardwareHandover $record) {
            //         // Collect all available files from the handover
            //         $allFiles = [];

            //         // Add invoice files if available
            //         if (!empty($record->invoice_file)) {
            //             $allFiles = array_merge($allFiles, is_array($record->invoice_file) ? $record->invoice_file : [$record->invoice_file]);
            //         }

            //         if (!empty($record->handover_pdf)){
            //             $allFiles = array_merge($allFiles, is_array($record->handover_pdf) ? $record->handover_pdf : [$record->handover_pdf]);
            //         }

            //         // Add confirmation order files if available
            //         if (!empty($record->confirmation_order_file)) {
            //             $allFiles = array_merge($allFiles, is_array($record->confirmation_order_file) ? $record->confirmation_order_file : [$record->confirmation_order_file]);
            //         }

            //         // Add HRDF grant files if available
            //         if (!empty($record->hrdf_grant_file)) {
            //             $allFiles = array_merge($allFiles, is_array($record->hrdf_grant_file) ? $record->hrdf_grant_file : [$record->hrdf_grant_file]);
            //         }

            //         // Add payment slip files if available
            //         if (!empty($record->payment_slip_file)) {
            //             $allFiles = array_merge($allFiles, is_array($record->payment_slip_file) ? $record->payment_slip_file : [$record->payment_slip_file]);
            //         }

            //         // Check if any files are available
            //         if (empty($allFiles)) {
            //             Notification::make()
            //                 ->title('No files available')
            //                 ->body("This handover has no files to create an attachment from.")
            //                 ->danger()
            //                 ->send();
            //             return;
            //         }

            //         // Create a new Hardware attachment with all files
            //         $attachment = HardwareAttachment::create([
            //             'hardware_handover_id' => $record->id,
            //             'title' => $data['title'],
            //             'description' => $data['description'],
            //             'files' => $allFiles, // Add all collected files
            //             'created_by' => auth()->id(),
            //             'updated_by' => auth()->id()
            //         ]);

            //         // Show success notification
            //         if ($attachment) {
            //             $fileCount = count($allFiles);
            //             Notification::make()
            //                 ->title('Attachment Created')
            //                 ->body("Successfully created attachment with {$fileCount} file" . ($fileCount != 1 ? 's' : '') . ".")
            //                 ->success()
            //                 ->send();
            //         } else {
            //             Notification::make()
            //                 ->title('Error')
            //                 ->body('Failed to create attachment.')
            //                 ->danger()
            //                 ->send();
            //         }
            //     })
            //     ->visible(function (HardwareHandover $record): bool {
            //         // Only show this action if the record has any files
            //         return !empty($record->invoice_file) ||
            //             !empty($record->confirmation_order_file) ||
            //             !empty($record->hrdf_grant_file) ||
            //             !empty($record->payment_slip_file);
            //     })
            //     ->requiresConfirmation()
            //     ->modalHeading('Create Attachment with All Files')
            //     ->modalDescription('This will create a single attachment containing all files from this handover.')
            //     ->modalSubmitActionLabel('Create Attachment'),
            // ]);
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
            'index' => Pages\ListHardwarePendingStocks::route('/'),
            // 'view' => Pages\ViewHardwareHandover::route('/{record}'),
            // 'create' => Pages\CreateHardwareHandover::route('/create'),
            // 'edit' => Pages\EditHardwareHandover::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
