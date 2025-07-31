<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoftwareHandoverResource\Pages;
use App\Filament\Resources\SoftwareHandoverResource\RelationManagers;
use App\Models\CompanyDetail;
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
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Collection;
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
                        Grid::make(4)
                            ->schema([
                                TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->readonly()
                                    ->maxLength(255),
                                TextInput::make('salesperson')
                                    ->label('Salesperson')
                                    ->placeholder('Select salesperson')
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
                                    ->readonly(),

                                TextInput::make('headcount')
                                    ->numeric()
                                    ->readonly(),
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
                                            ->native(false)
                                            ->displayFormat('d M Y'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('webinar_training')
                                            ->label('Online Webinar Training')
                                            ->native(false)
                                            ->displayFormat('d M Y'),

                                        DatePicker::make('go_live_date')
                                            ->label('System Go Live')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->live() // Make it react to changes
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
                                        return \App\Models\User::whereIn('role_id', [4, 5])
                                        ->orderBy('name')
                                            ->pluck('name', 'name')
                                            ->toArray();
                                    })
                                    ->required()
                                    ->disabled(fn() => auth()->user()->role_id !== 3)
                                    ->default(function (SoftwareHandover $record) {
                                        // First try to use existing implementer_id if record exists
                                        if ($record && $record->implementer) {
                                            return $record->implementer;
                                        }

                                        // Otherwise try to find the first available implementer
                                        $firstImplementer = \App\Models\User::whereIn('role_id', [4, 5])->first();
                                        return $firstImplementer ? $firstImplementer->id : null;
                                    })
                                    ->searchable()
                                    ->placeholder('Select an implementer')
                                    ->afterStateUpdated(function ($state, $old, $record, $component) {
                                        // Only send email if this is an existing record and the implementer actually changed
                                        if ($record && $record->exists && $old !== $state && $old !== null) {
                                            // Add debug logging to see what's happening
                                            \Illuminate\Support\Facades\Log::info("Implementer change detected", [
                                                'record_id' => $record->id,
                                                'old_implementer' => $old,
                                                'new_implementer' => $state
                                            ]);

                                            // Initialize variables with safe defaults
                                            $emailContent = [];
                                            $recipients = [];

                                            try {
                                                // Find the implementers - use firstOrFail() to catch missing users
                                                $newImplementer = \App\Models\User::where('name', $state)->first();

                                                // For old implementer, don't fail if not found
                                                $oldImplementer = \App\Models\User::where('name', $old)->first();
                                                $oldImplementerName = $oldImplementer ? $oldImplementer->name : $old;

                                                // Only proceed if new implementer exists
                                                if ($newImplementer) {
                                                    $viewName = 'emails.handover_changeimplementer';

                                                    // Get company name with fallbacks
                                                    $companyName = $record->company_name;
                                                    if (empty($companyName) && isset($record->lead) && isset($record->lead->companyDetail)) {
                                                        $companyName = $record->lead->companyDetail->company_name;
                                                    }
                                                    if (empty($companyName)) {
                                                        $companyName = 'Unknown Company';
                                                    }

                                                    // Get salesperson with safety checks
                                                    $salesperson = null;
                                                    $salespersonName = 'Unknown';
                                                    if (isset($record->lead) && isset($record->lead->salesperson)) {
                                                        $salesperson = \App\Models\User::find($record->lead->salesperson);
                                                        if ($salesperson) {
                                                            $salespersonName = $salesperson->name;
                                                        }
                                                    }

                                                    // Format the handover ID properly
                                                    $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                                                    // Get the handover PDF URL
                                                    $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                                                    // Process invoice files safely
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

                                                    // Create email content structure with safe data
                                                    $emailContent = [
                                                        'implementer' => [
                                                            'name' => $newImplementer->name,
                                                        ],
                                                        'oldImplementer' => [
                                                            'name' => $oldImplementerName,
                                                        ],
                                                        'company' => [
                                                            'name' => $companyName,
                                                        ],
                                                        'salesperson' => [
                                                            'name' => $salespersonName,
                                                        ],
                                                        'handover_id' => $handoverId,
                                                        'createdAt' => $record->completed_at
                                                            ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y')
                                                            : now()->format('d M Y'),
                                                        'handoverFormUrl' => $handoverFormUrl,
                                                        'invoiceFiles' => $invoiceFiles,
                                                    ];

                                                    // Initialize recipients array with admin email
                                                    // $recipients = ['faiz@timeteccloud.com']; // UNCOMMENTED - Always include admin

                                                    // Add new implementer email if valid
                                                    if ($newImplementer->email && filter_var($newImplementer->email, FILTER_VALIDATE_EMAIL)) {
                                                        $recipients[] = $newImplementer->email;
                                                    }

                                                    // Add old implementer email if valid and user exists
                                                    if ($oldImplementer && $oldImplementer->email && filter_var($oldImplementer->email, FILTER_VALIDATE_EMAIL)) {
                                                        $recipients[] = $oldImplementer->email;
                                                    }

                                                    // Add salesperson email if valid and user exists
                                                    if ($salesperson && $salesperson->email && filter_var($salesperson->email, FILTER_VALIDATE_EMAIL)) {
                                                        $recipients[] = $salesperson->email;
                                                    }

                                                    // Get authenticated user's email for sender with fallbacks
                                                    $authUser = auth()->user();
                                                    $senderEmail = $authUser->email ?? 'no-reply@timeteccloud.com';
                                                    $senderName = $authUser->name ?? 'TimeTec System';

                                                    // Log what we're about to do
                                                    \Illuminate\Support\Facades\Log::info("About to send email", [
                                                        'recipients' => $recipients,
                                                        'sender' => $senderEmail,
                                                        'subject' => "SOFTWARE HANDOVER ID {$handoverId} | {$companyName}"
                                                    ]);

                                                    // Send email with template and custom subject format if we have recipients
                                                    if (count($recipients) > 0) {
                                                        \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                                                            $message->from($senderEmail, $senderName)
                                                                ->to($recipients)
                                                                ->subject("SOFTWARE HANDOVER ID {$handoverId} | {$companyName}");
                                                        });

                                                        \Illuminate\Support\Facades\Log::info("Project assignment email - Change Implementer sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                                    } else {
                                                        \Illuminate\Support\Facades\Log::warning("No valid recipients for email notification");
                                                    }
                                                } else {
                                                    \Illuminate\Support\Facades\Log::warning("New implementer not found: {$state}");
                                                }
                                            } catch (\Exception $e) {
                                                // Better error logging with context
                                                \Illuminate\Support\Facades\Log::error("Email sending failed for handover #{$record->id}: " . $e->getMessage(), [
                                                    'exception' => $e,
                                                    'trace' => $e->getTraceAsString(),
                                                    'old_implementer' => $old,
                                                    'new_implementer' => $state
                                                ]);

                                                // Only try to log email content if it was created
                                                if (!empty($emailContent)) {
                                                    \Illuminate\Support\Facades\Log::debug("Email content for handover #{$record->id}:", $emailContent);
                                                }
                                            }
                                        }
                                    }),
                                TextInput::make('payroll_code')
                                    ->label('Payroll Code')
                                    ->maxLength(50)
                                    ->disabled(fn() => auth()->user()->role_id !== 3)
                                    ->dehydrated(true),
                            ]),
                        Section::make('Handover Status')
                        ->columnSpan(1)
                        ->schema([
                            Select::make('status_handover')
                                ->label('Project Status')
                                ->options(function (callable $get) {
                                    // First check if go_live_date has been selected
                                    if (!empty($get('go_live_date'))) {
                                        // If go_live_date exists, include Closed option
                                        return [
                                            'Closed' => 'Closed',
                                        ];
                                    } else {
                                        // If no go_live_date, don't include Closed option
                                        return [
                                            'Open' => 'Open',
                                            'InActive' => 'InActive',
                                            'Delay' => 'Delay',
                                        ];
                                    }
                                })
                                ->default(function (callable $get, SoftwareHandover $record) {
                                    // If go_live_date is set, default to Closed
                                    if (!empty($get('go_live_date')) || ($record && $record->go_live_date)) {
                                        return 'Closed';
                                    }

                                    // Otherwise use existing status or fall back to Open
                                    // If the current status is 'Closed' but go_live_date is removed, reset to 'Open'
                                    $currentStatus = $record->status_handover ?? 'Open';
                                    return ($currentStatus === 'Closed' && empty($get('go_live_date')) && empty($record->go_live_date))
                                        ? 'Open'
                                        : $currentStatus;
                                })
                                ->live() // Make it react to changes
                                ->required(),

                            Textarea::make('inactive_reason')
                                ->label('Inactive Reason')
                                ->placeholder('Please explain why this project is inactive')
                                ->rows(3)
                                ->maxLength(500)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->hidden(fn (callable $get): bool => $get('status_handover') !== 'InActive')
                                ->requiredIf('status_handover', 'InActive')
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
                    ->orderBy('id', 'desc');

                // if (auth()->user()->role_id === 2) {
                //     $userId = auth()->id();
                //     $query->whereHas('lead', function ($leadQuery) use ($userId) {
                //         $leadQuery->where('salesperson', $userId);
                //     });
                // }
            })
            ->defaultPaginationPageOption(50) // Set default pagination to 50 records per page
            ->paginationPageOptions([25, 50]) // Customize pagination options
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
                    ->searchable()
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        // This will control what's displayed
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            return strtoupper(Str::limit($state, 30, '...'));
                        }

                        return $state;
                    })
                    ->url(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);
                            return url('admin/leads/' . $encryptedId);
                        }

                        return null; // No URL if no company found
                    })
                    ->openUrlInNewTab()
                    ->color(function ($record) {
                        $company = CompanyDetail::where('company_name', $record->company_name)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if (filled($company)) {
                            return Color::hex('#338cf0');
                        }

                        return Color::hex("#000000");
                    }),

                TextColumn::make('salesperson')
                    ->label('SalesPerson'),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->toggleable(),

                TextColumn::make('status_handover')
                    ->label('Status')
                    ->toggleable()
                    ->formatStateUsing(fn($state) => strtoupper($state ?? '')),

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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('thire')
                    ->label('THIRE')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tacc')
                    ->label('TACC')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tpbi')
                    ->label('TPBI')
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill " style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payroll_code')
                    ->label('Payroll Code')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        if (!$record->go_live_date) {
                            try {
                                $completedDate = Carbon::parse($record->completed_at);
                                $today = Carbon::now();
                                // Calculate the difference in days
                                $daysDifference = $completedDate->diffInDays($today);

                                return $daysDifference . ' ' . Str::plural('day', $daysDifference);
                            } catch (\Exception $e) {
                                return 'Error: ' . $e->getMessage();
                            }
                        }

                        try {
                           $goLiveDate = Carbon::parse($record->go_live_date);
                           $completedDate = Carbon::parse($record->completed_at);

                           $daysDifference = $completedDate->diffInDays($goLiveDate);

                           return $daysDifference . ' ' . Str::plural('day', $daysDifference);
                        } catch (\Exception $e) {
                            // Return exception message for debugging
                            return 'Error: ' . $e->getMessage();
                        }
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('go_live_date')
                    ->label('Go Live Date')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kick_off_meeting')
                    ->label('Kick Off Date')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('webinar_training')
                    ->label('Webinar Date')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module_configuration')
                    ->label('Module Configuration')
                    ->options([
                        'full_module' => 'Full Module (TA+TL+TC+TP)',
                        'non_full_module' => 'Non-Full Module',
                        'ta_only' => 'TA Only',
                        'tl_only' => 'TL Only',
                        'tc_only' => 'TC Only',
                        'tp_only' => 'TP Only',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            // Full module - has all core modules
                            'full_module' => $query->where('ta', true)
                                                ->where('tl', true)
                                                ->where('tc', true)
                                                ->where('tp', true),

                            // Non-full module - missing at least one core module
                            'non_full_module' => $query->where(function (Builder $subQuery) {
                                $subQuery->where('ta', false)
                                        ->where('tl', false)
                                        ->where('tc', false)
                                        ->where('tp', false);
                            }),

                            // TA Only - only has TA module enabled
                            'ta_only' => $query->where('ta', true)
                                            ->where('tl', false)
                                            ->where('tc', false)
                                            ->where('tp', false),

                            // TL Only - only has TL module enabled
                            'tl_only' => $query->where('ta', false)
                                            ->where('tl', true)
                                            ->where('tc', false)
                                            ->where('tp', false),

                            // TC Only - only has TC module enabled
                            'tc_only' => $query->where('ta', false)
                                            ->where('tl', false)
                                            ->where('tc', true)
                                            ->where('tp', false),

                            // TP Only - only has TP module enabled
                            'tp_only' => $query->where('ta', false)
                                            ->where('tl', false)
                                            ->where('tc', false)
                                            ->where('tp', true),

                            default => $query,
                        };
                    })
                    ->indicator('Module Configuration'),
                // Existing date range filter
                Tables\Filters\SelectFilter::make('lead_association')
                    ->label('Lead Association')
                    ->options([
                        'with_lead' => 'Has Lead ID',
                        'without_lead' => 'Missing Lead ID',
                    ])
                    ->query(function (Builder $query, array $data) {
                        // Return early if no value is selected
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'with_lead' => $query->whereNotNull('lead_id'),
                            'without_lead' => $query->whereNull('lead_id'),
                            default => $query,
                        };
                    })
                    ->indicator('Lead Association'),
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
                    // Get users with role_id 4 or 5 first (standard implementers)
                    $implementers = \App\Models\User::whereIn('role_id', [4, 5])
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->toArray();

                    // Add specific implementers who might not be in roles 4 or 5
                    $specificImplementers = [
                        'ADZZIM' => 'Adzzim Bin Kassim',
                        'AZRUL' => 'Azrul Nizam',
                        'HANIF' => 'Muhammad Hanif',
                        'Muhammad Alif Faisal' => 'Muhammad Alif Faisal',
                        'BARI' => 'Bari',
                    ];

                    // Merge arrays, ensuring there are no duplicates
                    foreach ($specificImplementers as $key => $value) {
                        if (!array_key_exists($key, $implementers)) {
                            $implementers[$key] = $value;
                        }
                    }

                    // Sort alphabetically for better user experience
                    ksort($implementers);

                    return $implementers;
                })
                ->searchable(),

                // Status handover filter
                Tables\Filters\SelectFilter::make('status_handover')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'Open' => 'Open',
                        'Delay' => 'Delay',
                        'InActive' => 'InActive',
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
            ->filtersFormColumns(1)
            ->bulkActions([
                Tables\Actions\BulkAction::make('updateStatusHandover')
                    ->label('Batch Update Status Handover')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(function () {
                        $user = auth()->user();

                        // Managers
                        if ($user->role_id === 3) {
                            return true;
                        }

                        // Or lead owner with admin privilege
                        if ($user->role_id === 1 && $user->additional_role === 1) {
                            return true;
                        }

                        return false;
                    })
                    ->form([
                        \Filament\Forms\Components\Select::make('status_handover')
                            ->label('New Status Handover')
                            ->options([
                                'Open' => 'Open',
                                'Delay' => 'Delay',
                                'Inactive' => 'Inactive',
                                'Closed' => 'Closed',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, \Illuminate\Support\Collection $records) {
                        foreach ($records as $record) {
                            $record->update([
                                'status_handover' => $data['status_handover'],
                            ]);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Status Handover Updated')
                            ->body('All selected records have been updated successfully.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('updateImplementer')
                    ->label('Batch Update Implementer')
                    ->icon('heroicon-o-user-group')
                    ->color('warning')
                    ->visible(function() {
                        $user = auth()->user();

                        // Allow access if user is a manager (role_id 3)
                        if ($user->role_id === 3) {
                            return true;
                        }

                        // OR if user is lead owner (role_id 1) with admin privileges (additional_role 1)
                        if ($user->role_id === 1 && $user->additional_role === 1) {
                            return true;
                        }

                        // Otherwise hide the action
                        return false;
                    })
                    ->form([
                        Forms\Components\Select::make('implementer')
                            ->label('New Implementer')
                            ->options(function () {
                                return \App\Models\User::whereIn('role_id', [4, 5])
                                    ->orderBy('name')
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Select new implementer'),
                    ])
                    ->action(function (array $data, Collection $records) {
                        $implementer = $data['implementer'];
                        $count = 0;

                        // Find the new implementer user
                        $newImplementerUser = \App\Models\User::where('name', $implementer)->first();
                        if (!$newImplementerUser) {
                            Notification::make()
                                ->title('Error')
                                ->body("Could not find implementer with name '{$implementer}'.")
                                ->danger()
                                ->send();
                            return;
                        }

                        // Prepare data for batch email
                        $updatedHandovers = [];

                        foreach ($records as $record) {
                            // Skip if implementer is already the same
                            if ($record->implementer === $implementer) {
                                continue;
                            }

                            // Store old implementer for tracking
                            $oldImplementer = $record->implementer ?? 'Unknown';

                            // Update the record
                            $record->update([
                                'implementer' => $implementer,
                            ]);

                            $count++;

                            // Format the handover ID properly
                            $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                            // Get company name with fallbacks
                            $companyName = $record->company_name;
                            if (empty($companyName) && isset($record->lead) && isset($record->lead->companyDetail)) {
                                $companyName = $record->lead->companyDetail->company_name;
                            }
                            if (empty($companyName)) {
                                $companyName = 'Unknown Company';
                            }

                            // Get the handover PDF URL
                            $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                            // Process invoice files safely
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

                            // Add to the collection of updated handovers
                            $updatedHandovers[] = [
                                'id' => $record->id,
                                'handover_id' => $handoverId,
                                'company_name' => $companyName,
                                'salesperson' => $record->salesperson ?? 'Unknown',
                                'old_implementer' => $oldImplementer,
                                'date_created' => $record->completed_at
                                    ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y')
                                    : now()->format('d M Y'),
                                'handover_url' => $handoverFormUrl,
                                'invoice_files' => $invoiceFiles,
                                // Add any additional data you want in the email
                                'modules' => [
                                    'ta' => $record->ta ? true : false,
                                    'tl' => $record->tl ? true : false,
                                    'tc' => $record->tc ? true : false,
                                    'tp' => $record->tp ? true : false,
                                    'tapp' => $record->tapp ? true : false,
                                    'thire' => $record->thire ? true : false,
                                    'tacc' => $record->tacc ? true : false,
                                    'tpbi' => $record->tpbi ? true : false,
                                ]
                            ];
                        }

                        // If any records were updated, send a consolidated email
                        if ($count > 0) {
                            try {
                                // Initialize recipients for the batch email
                                // $recipients = ['faiz@timeteccloud.com']; // Always include admin

                                // Add new implementer email if valid
                                if ($newImplementerUser->email && filter_var($newImplementerUser->email, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $newImplementerUser->email;
                                }

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $authUser->email ?? 'no-reply@timeteccloud.com';
                                $senderName = $authUser->name ?? 'TimeTec System';

                                // Prepare the consolidated email content
                                $emailContent = [
                                    'implementer' => [
                                        'name' => $newImplementerUser->name,
                                    ],
                                    'handovers' => $updatedHandovers,
                                    'total_count' => $count,
                                    'updated_by' => $authUser->name ?? 'Admin User',
                                    'updated_at' => now()->format('d M Y, h:i A'),
                                ];

                                // Send the consolidated email
                                \Illuminate\Support\Facades\Mail::send('emails.handover_batch_update', ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $count, $implementer) {
                                    $message->from($senderEmail, $senderName)
                                        ->to($recipients)
                                        ->subject("BATCH UPDATE: {$count} Software Handovers Assigned to {$implementer}");
                                });

                                \Illuminate\Support\Facades\Log::info("Batch update email sent for {$count} handovers assigned to {$implementer}");
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to send batch update email: " . $e->getMessage());
                            }
                        }

                        // Show notification with results
                        Notification::make()
                            ->title('Implementer Updated')
                            ->body("Successfully updated implementer to '{$implementer}' for {$count} " . Str::plural('record', $count) . ".")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Update Implementer for Selected Records')
                    ->modalDescription('This will change the implementer for all selected records and send a single consolidated notification email.')
                    ->modalSubmitActionLabel('Update Implementer'),
                ]);
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
