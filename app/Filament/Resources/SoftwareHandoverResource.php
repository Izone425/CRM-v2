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
                                            ->disabled()
                                            ->inline(),

                                        Checkbox::make('tapp')
                                            ->label('TimeTec Access (T-APP)')
                                            ->disabled()
                                            ->inline(),

                                        Checkbox::make('tl')
                                            ->label('TimeTec Leave (TL)')
                                            ->disabled()
                                            ->inline(),

                                        Checkbox::make('thire')
                                            ->label('TimeTec Hire (T-HIRE)')
                                            ->disabled()
                                            ->inline(),

                                        Checkbox::make('tc')
                                            ->label('TimeTec Claim (TC)')
                                            ->disabled()
                                            ->inline(),

                                        Checkbox::make('tacc')
                                            ->label('TimeTec Access (T-ACC)')
                                            ->disabled()
                                            ->inline(),

                                        Checkbox::make('tp')
                                            ->label('TimeTec Payroll (TP)')
                                            ->disabled()
                                            ->inline(),

                                        Checkbox::make('tpbi')
                                            ->label('TimeTec PBI (TPBI)')
                                            ->disabled()
                                            ->inline(),
                                    ])
                            ]),

                        // Section: Implementation Details
                        Section::make('Implementation Timeline')
                            ->columnSpan(2)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('formatted_date')
                                            ->label('DB Creation Date')
                                            ->formatStateUsing(function ($state, $record) {
                                                return $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y') : '-';
                                            })
                                            ->disabled()
                                            ->dehydrated(false),
                                        DatePicker::make('kick_off_meeting')
                                            ->label('Online Kick Off Meeting')
                                            ->disabled()
                                            ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                            ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('webinar_training')
                                            ->label('Online Webinar Training')
                                            ->disabled()
                                            ->format('Y-m-d')  // Change from d/m/Y to Y-m-d
                                            ->displayFormat('d/m/Y'),  // Keep display format as d/m/Y

                                        DatePicker::make('go_live_date')
                                            ->label('System Go Live')
                                            ->format('Y-m-d')
                                            ->displayFormat('d/m/Y')
                                            ->live()  // Make it react to changes
                                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                                // If a go_live_date is set (not null or empty), set status to Closed
                                                if (!empty($state)) {
                                                    $set('status_handover', 'Closed');
                                                }
                                            })
                                            ->disabled(function () {
                                                // Disable this field if the user has role_id 2 (salesperson)
                                                return auth()->user()->role_id === 2;
                                            })
                                            ->dehydrated(function () {
                                                // Even if disabled, we still want to save any existing value
                                                // This ensures the field value is still submitted when the form is saved
                                                return true;
                                            }),
                                    ]),
                            ]),

                        Section::make('Training Information')
                            ->columnSpan(1)
                            ->schema([
                                Select::make('implementer')
                                    ->label('Implementer')
                                    ->options(function () {
                                        return \App\Models\User::where('role_id', 4)
                                            ->orderBy('name')
                                            ->pluck('name', 'name')
                                            ->toArray();
                                    })
                                    ->required()
                                    ->default(function (SoftwareHandover $record) {
                                        // First try to use existing implementer_id if record exists
                                        if ($record && $record->implementer) {
                                            return $record->implementer;
                                        }

                                        // Otherwise try to find the first available implementer
                                        $firstImplementer = \App\Models\User::where('role_id', 4)->first();
                                        return $firstImplementer ? $firstImplementer->id : null;
                                    })
                                    ->searchable()
                                    ->placeholder('Select an implementer')
                                    ->afterStateUpdated(function ($state, $old, $record, $component) {
                                        // Only send email if this is an existing record and the implementer actually changed
                                        if ($record && $record->exists && $old !== $state && $old !== null) {
                                            $newImplementer = \App\Models\User::where('name',$state)->first();
                                            $oldImplementer = \App\Models\User::where('name',$old)->first();

                                            if ($newImplementer) {
                                                // Send email notification
                                                try {
                                                    $viewName = 'emails.handover_changeimplementer';

                                                    // Get salesperson and company details
                                                    $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';
                                                    $salespersonId = $record->lead->salesperson ?? null;
                                                    $salesperson = \App\Models\User::find($salespersonId);

                                                    // Format the handover ID properly
                                                    $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                                                    // Get the handover PDF URL
                                                    $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                                                    $invoiceFiles = [];
                                                    if ($record->invoice_file) {
                                                        $invoiceFileArray = is_string($record->invoice_file)
                                                            ? json_decode($record->invoice_file, true)
                                                            : $record->invoice_file;

                                                        if (is_array($invoiceFileArray)) {
                                                            foreach ($invoiceFileArray as $file) {
                                                                $invoiceFiles[] = url('storage/' . $file);
                                                            }
                                                        }
                                                    }

                                                    // Create email content structure
                                                    $emailContent = [
                                                        'implementer' => [
                                                            'name' => $newImplementer->name,
                                                        ],
                                                        'oldImplementer' =>[
                                                            'name' => $oldImplementer->name,
                                                        ],
                                                        'company' => [
                                                            'name' => $companyName,
                                                        ],
                                                        'salesperson' => [
                                                            'name' => $salesperson->name,
                                                        ],
                                                        'handover_id' => $handoverId,
                                                        // CHANGE created_at to completed_at
                                                        'createdAt' => $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y') : now()->format('d M Y'),
                                                        'handoverFormUrl' => $handoverFormUrl,
                                                        'invoiceFiles' => $invoiceFiles, // Array of all invoice file URLs
                                                    ];

                                                    // Initialize recipients array with admin email
                                                    $recipients = ['admin.timetec.hr@timeteccloud.com']; // Always include admin

                                                    // Add implementer email if valid
                                                    if ($newImplementer->email && filter_var($newImplementer->email, FILTER_VALIDATE_EMAIL)) {
                                                        $recipients[] = $newImplementer->email;
                                                    }

                                                    // Add old implementer email if valid
                                                    if ($oldImplementer->email && filter_var($oldImplementer->email, FILTER_VALIDATE_EMAIL)) {
                                                        $recipients[] = $oldImplementer->email;
                                                    }

                                                    // Add salesperson email if valid
                                                    if ($salesperson->email && filter_var($salesperson->email, FILTER_VALIDATE_EMAIL)) {
                                                        $recipients[] = $salesperson->email;
                                                    }

                                                    // Get authenticated user's email for sender
                                                    $authUser = auth()->user();
                                                    $senderEmail = $authUser->email;
                                                    $senderName = $authUser->name;

                                                    // Send email with template and custom subject format
                                                    if (count($recipients) > 0) {
                                                        \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                                                            $message->from($senderEmail,$senderName)
                                                                ->to($recipients)
                                                                ->subject("SOFTWARE HANDOVER ID {$handoverId} | {$companyName}");
                                                            //   $message->from("itsupport@timeteccloud.com","IT Support")
                                                            //     ->to("adly.shahromazmi@timeteccloud.com")
                                                            //     ->subject("SOFTWARE HANDOVER ID {$handoverId} | {$companyName}");
                                                        });

                                                        \Illuminate\Support\Facades\Log::info("Project assignment email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                                    }
                                                } catch (\Exception $e) {
                                                    // Log error but don't stop the process
                                                    \Illuminate\Support\Facades\Log::error("Email sending failed for handover #{$record->id}: {$e->getMessage()}");
                                                }
                                            }
                                        }
                                    }),
                                TextInput::make('payroll_code')
                                    ->label('Payroll Code')
                                    ->maxLength(50),
                            ]),
                        Section::make('Handover Status')
                            ->columnSpan(1)
                            ->schema([
                                Select::make('status_handover')
                                    ->label('Status')
                                    ->options([
                                        'Open' => 'Open',
                                        'Delay' => 'Delay',
                                        'Inactive' => 'Inactive',
                                        'Closed' => 'Closed',
                                    ])
                                    ->default(function (SoftwareHandover $record) {
                                        // If go_live_date is set, default to Closed
                                        if ($record && $record->go_live_date) {
                                            return 'Closed';
                                        }

                                        // If total days > 60, default to Delay
                                        if ($record && $record->completed_at) {
                                            $completedDate = Carbon::parse($record->completed_at);
                                            $today = Carbon::now();
                                            $daysDifference = $completedDate->diffInDays($today);

                                            if ($daysDifference > 60 && $record->status_handover !== 'Closed') {
                                                return 'Delay';
                                            }
                                        }

                                        // Otherwise use existing status or fall back to Open
                                        return $record->status_handover ?? 'Open';
                                    })
                                    ->live() // Make it react to changes
                                    ->required(),
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
                    ->orderBy('id', 'asc');

                // if (auth()->user()->role_id === 2) {
                //     $userId = auth()->id();
                //     $query->whereHas('lead', function ($leadQuery) use ($userId) {
                //         $leadQuery->where('salesperson', $userId);
                //     });
                // }
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
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
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('company_name')
                    ->limit(20)
                    ->searchable()
                    ->label('Company Name'),

                TextColumn::make('salesperson')
                    ->label('SalesPerson'),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->toggleable(),

                TextColumn::make('status_handover')
                    ->label('Status')
                    ->toggleable(),

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
                TextColumn::make('completed_at')
                    ->label('DB Creation')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('total_days')
                    ->label('Total Days')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        // Check if completed_at exists
                        if (!$record->completed_at) {
                            return 'No completion date';
                        }

                        try {
                            // Parse the date safely
                            $completedDate = Carbon::parse($record->completed_at);
                            $today = Carbon::now();

                            // Calculate the difference in days
                            $daysDifference = $completedDate->diffInDays($today);

                            // Return formatted result
                            return $daysDifference . ' ' . Str::plural('day', $daysDifference);
                        } catch (\Exception $e) {
                            // Return exception message for debugging
                            return 'Error: ' . $e->getMessage();
                        }
                    })
                    ->toggleable(),
                TextColumn::make('go_live_date')
                    ->label('Go Live Date')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('kick_off_meeting')
                    ->label('Kick Off Date')
                    ->date('d M Y')
                    ->toggleable(),
                TextColumn::make('webinar_training')
                    ->label('Webinar Date')
                    ->date('d M Y')
                    ->toggleable(),
            ])
            ->filters([
                // Existing date range filter
                Filter::make('completed_at')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('DB Creation Date')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            // Parse the date range from the "start - end" format
                            [$start, $end] = explode(' - ', $data['date_range']);

                            // Ensure valid dates
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();

                            // Apply the filter to completed_at instead of created_at
                            $query->whereBetween('completed_at', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['date_range'])) {
                            // Parse the date range for display
                            [$start, $end] = explode(' - ', $data['date_range']);

                            return 'DB Creation Date: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                ' - ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }
                        return null;
                    }),

                // New salesperson filter
                Tables\Filters\SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        return \App\Models\User::where('role_id', 2) // Assuming role_id 2 is for salespeople
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->searchable(),

                // New implementer filter
                Tables\Filters\SelectFilter::make('implementer')
                    ->label('Implementer')
                    ->options(function () {
                        return \App\Models\User::where('role_id', 4) // Assuming role_id 4 is for implementers
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->searchable(),

                // Status handover filter
                Tables\Filters\SelectFilter::make('status_handover')
                    ->label('Status')
                    ->options([
                        'Open' => 'Open',
                        'Delay' => 'Delay',
                        'Inactive' => 'Inactive',
                        'Closed' => 'Closed',
                    ]),

                // Company size filter
                Filter::make('company_size')
                    ->form([
                        Forms\Components\Select::make('company_size')
                            ->label('Company Size')
                            ->options([
                                'Small' => 'Small (1-24)',
                                'Medium' => 'Medium (25-99)',
                                'Large' => 'Large (100-500)',
                                'Enterprise' => 'Enterprise (501+)',
                            ])
                            ->searchable()
                            ->placeholder('Select company size'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (empty($data['company_size'])) {
                            return;
                        }

                        // Apply different headcount filters based on selected company size
                        switch ($data['company_size']) {
                            case 'Small':
                                $query->whereBetween('headcount', [1, 24]);
                                break;
                            case 'Medium':
                                $query->whereBetween('headcount', [25, 99]);
                                break;
                            case 'Large':
                                $query->whereBetween('headcount', [100, 500]);
                                break;
                            case 'Enterprise':
                                $query->where('headcount', '>=', 501);
                                break;
                        }
                    }),
            ])
            ->filtersFormColumns(1);
        // ->actions([
        //     // Tables\Actions\EditAction::make(),
        //     Tables\Actions\Action::make('create_attachment')
        //     ->label('Create Attachment')
        //     ->icon('heroicon-o-paper-clip')
        //     ->color('success')
        //     ->form([
        //         Forms\Components\TextInput::make('title')
        //             ->label('Attachment Title')
        //             ->default(function (SoftwareHandover $record) {
        //                 return "Files for {$record->company_name}";
        //             })
        //             ->required(),

        //         Forms\Components\Textarea::make('description')
        //             ->label('Description')
        //             ->default(function (SoftwareHandover $record) {
        //                 return "Combined files for {$record->company_name} (Handover #{$record->id})";
        //             }),
        //     ])
        //     ->action(function (array $data, SoftwareHandover $record) {
        //         // Collect all available files from the handover
        //         $allFiles = [];

        //         // Add invoice files if available
        //         if (!empty($record->invoice_file)) {
        //             $allFiles = array_merge($allFiles, is_array($record->invoice_file) ? $record->invoice_file : [$record->invoice_file]);
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

        //         // Create a new software attachment with all files
        //         $attachment = \App\Models\SoftwareAttachment::create([
        //             'software_handover_id' => $record->id,
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
        //     ->visible(function (SoftwareHandover $record): bool {
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
            'index' => Pages\ListSoftwareHandovers::route('/'),
            // 'view' => Pages\ViewSoftwareHandover::route('/{record}'),
            // 'create' => Pages\CreateSoftwareHandover::route('/create'),
            'edit' => Pages\EditSoftwareHandover::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
