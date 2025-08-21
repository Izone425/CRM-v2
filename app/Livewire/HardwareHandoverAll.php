<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\Lead;
use App\Models\HardwareHandover;
use App\Models\RepairAppointment;
use App\Models\User;
use App\Services\CategoryService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class HardwareHandoverAll extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;
    protected static ?int $indexRepeater3 = 0;
    protected static ?int $indexRepeater4 = 0;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-hardwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getNewHardwareHandovers()
    {
        return HardwareHandover::query()
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Rejected' => 'Rejected',
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Completed: Installation' => 'Completed: Installation',
                        'Completed: Courier' => 'Completed: Courier',
                        'Completed Migration' => 'Completed Migration',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('missing_serials')
                    ->label('Missing Device Serials')
                    ->options([
                        'missing_serials' => 'Missing serial numbers',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return $query
                            ->whereNull('device_serials')
                            ->whereNotIn('status', ['New', 'Pending Stock', 'Draft']);
                    })
                    ->indicator('Missing Device Serials'),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id',15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::where('role_id', '4')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            // ->filters([
            //     // Filter for Creator
            //     SelectFilter::make('created_by')
            //         ->label('Created By')
            //         ->multiple()
            //         ->options(User::pluck('name', 'id')->toArray())
            //         ->placeholder('Select User'),

            //     // Filter by Company Name
            //     SelectFilter::make('company_name')
            //         ->label('Company')
            //         ->searchable()
            //         ->options(HardwareHandover::distinct()->pluck('company_name', 'company_name')->toArray())
            //         ->placeholder('Select Company'),
            // ])
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
                        return 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
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

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $fullName . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('invoice_type')
                    ->label('Invoice Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                        default => ucfirst($state ?? 'Unknown')
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        'No Stock' => new HtmlString('<span style="color: red;">No Stock</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
                ])
                ->actions([
                    ActionGroup::make([
                        Action::make('view')
                            ->label('View')
                            ->icon('heroicon-o-eye')
                            ->color('secondary')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            // Use a callback function instead of arrow function for more control
                            ->modalContent(function (HardwareHandover $record): View {

                                // Return the view with the record using $this->record pattern
                                return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                            }),
                        Action::make('pending_stock')
                            ->label('Pending Stock')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->color('warning')
                            ->modalHeading('Pending Stock Confirmation')
                            ->modalWidth('lg')
                            ->form([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('tc10_quantity')
                                            ->label('TC10')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),

                                        TextInput::make('face_id5_quantity')
                                            ->label('FACE ID 5')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),

                                        TextInput::make('time_beacon_quantity')
                                            ->label('TIME BEACON')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),

                                        TextInput::make('tc20_quantity')
                                            ->label('TC20')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),

                                        TextInput::make('face_id6_quantity')
                                            ->label('FACE ID 6')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),

                                        TextInput::make('nfc_tag_quantity')
                                            ->label('NFC TAG')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                    ]),

                                Select::make('implementer')
                                    ->label('Assign Implementer')
                                    ->options(function () {
                                        return User::whereIn('role_id', [4,5]) // Assuming 4 is the implementer role
                                            ->orWhere(function ($query) {
                                                $query->where('additional_role', 4);
                                            })
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->disabled(function (HardwareHandover $record) {
                                        // Only disable if there's an implementer from software handover
                                        if ($record && $record->lead_id) {
                                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $record->lead_id)
                                                ->latest()
                                                ->first();

                                            return $softwareHandover && $softwareHandover->implementer;
                                        }

                                        return false; // Enable field if no software handover exists
                                    })
                                    ->default(function (HardwareHandover $record) {
                                        // First, check if we already have a set implementer for this record
                                        if ($record && $record->implementer) {
                                            return $record->implementer;
                                        }

                                        // If not, try to get the implementer from the associated software handover
                                        if ($record && $record->lead_id) {
                                            // Find the software handover for the same lead
                                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $record->lead_id)
                                                ->latest()
                                                ->first();

                                            // Return the implementer ID if found
                                            if ($softwareHandover && $softwareHandover->implementer) {
                                                return $softwareHandover->implementer;
                                            }
                                        }

                                        return null; // No default implementer found
                                    }),

                                Grid::make(2)
                                ->schema([
                                    FileUpload::make('invoice_file')
                                        ->label('Upload Invoice')
                                        ->disk('public')
                                        ->directory('handovers/invoices')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->openable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                            $companyName = Str::slug($get('company_name') ?? 'invoice');
                                            $date = now()->format('Y-m-d');
                                            $random = Str::random(5);
                                            $extension = $file->getClientOriginalExtension();

                                            return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                        }),

                                    FileUpload::make('sales_order_file')
                                        ->label('Upload Sales Order')
                                        ->required()
                                        ->disk('public')
                                        ->directory('handovers/sales_orders')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->openable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                            $companyName = Str::slug($get('company_name') ?? 'invoice');
                                            $date = now()->format('Y-m-d');
                                            $random = Str::random(5);
                                            $extension = $file->getClientOriginalExtension();

                                            return "{$companyName}-salesorder-{$date}-{$random}.{$extension}";
                                        }),
                                ]),
                            ])
                            ->action(function (HardwareHandover $record, array $data): void {
                                // Process file uploads
                                if (isset($data['invoice_file']) && is_array($data['invoice_file'])) {
                                    // Get existing invoice files
                                    $existingFiles = [];
                                    if ($record->invoice_file) {
                                        $existingFiles = is_string($record->invoice_file)
                                            ? json_decode($record->invoice_file, true)
                                            : $record->invoice_file;

                                        if (!is_array($existingFiles)) {
                                            $existingFiles = [];
                                        }
                                    }

                                    // Merge existing files with newly uploaded ones
                                    $allFiles = array_merge($existingFiles, $data['invoice_file']);

                                    // Update data with combined files
                                    $data['invoice_file'] = json_encode($allFiles);
                                }

                                if (isset($data['sales_order_file']) && is_array($data['sales_order_file'])) {
                                    // Get existing sales order files
                                    $existingFiles = [];
                                    if ($record->sales_order_file) {
                                        $existingFiles = is_string($record->sales_order_file)
                                            ? json_decode($record->sales_order_file, true)
                                            : $record->sales_order_file;

                                        if (!is_array($existingFiles)) {
                                            $existingFiles = [];
                                        }
                                    }

                                    // Merge existing files with newly uploaded ones
                                    $allFiles = array_merge($existingFiles, $data['sales_order_file']);

                                    // Update data with combined files
                                    $data['sales_order_file'] = json_encode($allFiles);
                                }

                                $implementerId = null;
                                $implementerName = $record->implementer ?? null;
                                $implementerEmail = null;

                                // Check if implementer is selected from the form (when field is enabled)
                                if (isset($data['implementer']) && !empty($data['implementer'])) {
                                    $implementerId = $data['implementer'];
                                    $implementer = \App\Models\User::find($implementerId);
                                    if ($implementer) {
                                        $implementerName = $implementer->name;
                                        $implementerEmail = $implementer->email;
                                    }
                                } else {
                                    // Fallback to getting implementer from software handover
                                    $softwareHandover = $record->lead ? \App\Models\SoftwareHandover::where('lead_id', $record->lead->id)
                                        ->latest()
                                        ->first() : null;

                                    if ($softwareHandover && $softwareHandover->implementer) {
                                        $implementerName = $softwareHandover->implementer;
                                        // Try to find the user by name to get their email
                                        $implementer = \App\Models\User::where('name', $implementerName)->first();
                                        $implementerEmail = $implementer?->email ?? null;
                                    }
                                }

                                // Get the salesperson info
                                $salespersonId = $record->lead->salesperson ?? null;
                                $salesperson = \App\Models\User::find($salespersonId);
                                $salespersonEmail = $salesperson?->email ?? null;
                                $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                                $updateData = [
                                    'tc10_quantity' => $data['tc10_quantity'],
                                    'tc20_quantity' => $data['tc20_quantity'],
                                    'face_id5_quantity' => $data['face_id5_quantity'],
                                    'face_id6_quantity' => $data['face_id6_quantity'],
                                    'time_beacon_quantity' => $data['time_beacon_quantity'],
                                    'nfc_tag_quantity' => $data['nfc_tag_quantity'],
                                    'implementer' => $implementerName,
                                    'pending_stock_at' => now(),
                                    'status' => 'Pending Stock',
                                ];

                                if (isset($data['invoice_file'])) {
                                    $updateData['invoice_file'] = $data['invoice_file'];
                                }

                                if (isset($data['sales_order_file'])) {
                                    $updateData['sales_order_file'] = $data['sales_order_file'];
                                }

                                $record->update($updateData);

                                try {
                                    // Get the controller for PDF generation
                                    $pdfController = new \App\Http\Controllers\GenerateHardwareHandoverPdfController();

                                    // Generate the new PDF
                                    $pdfPath = $pdfController->generateInBackground($record);

                                    if ($pdfPath) {
                                        // Update the record with the new PDF path if needed
                                        if ($pdfPath !== $record->handover_pdf) {
                                            $record->update(['handover_pdf' => $pdfPath]);
                                        }

                                        \Illuminate\Support\Facades\Log::info("Hardware handover PDF regenerated successfully", [
                                            'handover_id' => $record->id,
                                            'pdf_path' => $pdfPath
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error("Failed to regenerate hardware handover PDF", [
                                        'handover_id' => $record->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                // Send email notification
                                try {
                                    $viewName = 'emails.pending_stock_notification';

                                    $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';
                                    $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                                    // Format the handover ID properly
                                    $handoverId = 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

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

                                    $salesOrderFiles = [];
                                    if ($record->sales_order_file) {
                                        $salesOrderFileArray = is_string($record->sales_order_file)
                                            ? json_decode($record->sales_order_file, true)
                                            : $record->sales_order_file;

                                        if (is_array($salesOrderFileArray)) {
                                            foreach ($salesOrderFileArray as $file) {
                                                $salesOrderFiles[] = url('storage/' . $file);
                                            }
                                        }
                                    }

                                    // Create email content structure
                                    $emailContent = [
                                        'implementer' => [
                                            'name' => $implementerName ?? null,
                                        ],
                                        'company' => [
                                            'name' => $companyName,
                                        ],
                                        'salesperson' => [
                                            'name' => $salespersonName,
                                        ],
                                        'handover_id' => $handoverId,
                                        // CHANGE created_at to completed_at
                                        'createdAt' => $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y') : now()->format('d M Y'),
                                        'handoverFormUrl' => $handoverFormUrl,
                                        'invoiceFiles' => $invoiceFiles,
                                        'salesOrderFiles' => $salesOrderFiles,
                                        'devices' => [
                                            'tc10' => [
                                                'quantity' => (int)$data['tc10_quantity'],
                                                'status' => (int)$data['tc10_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'tc20' => [
                                                'quantity' => (int)$data['tc20_quantity'],
                                                'status' => (int)$data['tc20_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'face_id5' => [
                                                'quantity' => (int)$data['face_id5_quantity'],
                                                'status' => (int)$data['face_id5_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'face_id6' => [
                                                'quantity' => (int)$data['face_id6_quantity'],
                                                'status' => (int)$data['face_id6_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'time_beacon' => [
                                                'quantity' => (int)$data['time_beacon_quantity'],
                                                'status' => (int)$data['time_beacon_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'nfc_tag' => [
                                                'quantity' => (int)$data['nfc_tag_quantity'],
                                                'status' => (int)$data['nfc_tag_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ]
                                        ]
                                    ];

                                    // Initialize recipients array with admin email
                                    $recipients = []; // Always include admin

                                    // Add implementer email if valid
                                    if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                        $recipients[] = $implementerEmail;
                                    }

                                    // Add salesperson email if valid
                                    if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                        $recipients[] = $salespersonEmail;
                                    }

                                    // Get authenticated user's email for sender
                                    $authUser = auth()->user();
                                    $senderEmail = $authUser->email;
                                    $senderName = $authUser->name;

                                    // Send email with template and custom subject format
                                    if (count($recipients) > 0) {
                                        \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                                            $message->from($senderEmail, $senderName)
                                                ->to($recipients)
                                                ->subject("HARDWARE HANDOVER ID {$handoverId} | {$companyName}");
                                        });

                                        \Illuminate\Support\Facades\Log::info("Project assignment email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                    }
                                } catch (\Exception $e) {
                                    // Log error but don't stop the process
                                    \Illuminate\Support\Facades\Log::error("Email sending failed for handover #{$record->id}: {$e->getMessage()}");
                                }

                                Notification::make()
                                    ->title('Hardware Handover processed')
                                    ->success()
                                    ->body('Status updated to: ' . $record->status)
                                    ->send();
                            })
                            ->requiresConfirmation(false)
                            ->hidden(fn (HardwareHandover $record): bool =>
                                $record->status !== 'New' || auth()->user()->role_id === 2
                            ),

                        Action::make('pending_migration')
                            ->label('Pending Migration')
                            ->icon('heroicon-o-truck')
                            ->color('success')
                            ->modalHeading('Pending Migration Confirmation')
                            ->modalWidth('lg')
                            ->form([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('tc10_quantity')
                                            ->label('TC10')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));

                                                $set('tc10_serials', $serials);
                                            }),

                                        TextInput::make('face_id5_quantity')
                                            ->label('FACE ID 5')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));

                                                $set('face_id5_serials', $serials);
                                            }),

                                        TextInput::make('time_beacon_quantity')
                                            ->label('TIME BEACON')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('time_beacon_serials', $serials);
                                            }),

                                        TextInput::make('tc20_quantity')
                                            ->label('TC20')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('tc20_serials', $serials);
                                            }),

                                        TextInput::make('face_id6_quantity')
                                            ->label('FACE ID 6')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('face_id6_serials', $serials);
                                            }),

                                        TextInput::make('nfc_tag_quantity')
                                            ->label('NFC TAG')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('nfc_tag_serials', $serials);
                                            }),
                                    ]),

                                Section::make('TC10 Serial Numbers')
                                    ->schema([
                                        Repeater::make('tc10_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('TC10 Serial Number #') . ' ' . ++self::$indexRepeater)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('tc10_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('tc10_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('tc10_quantity') <= 0)
                                    ->collapsible(),

                                Section::make('TC20 Serial Numbers')
                                    ->schema([
                                        Repeater::make('tc20_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('TC20 Serial Number #') . ' ' . ++self::$indexRepeater2)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('tc20_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('tc20_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('tc20_quantity') <= 0)
                                    ->collapsible(),

                                // For FACE ID 5 Serial Numbers
                                Section::make('FACE ID 5 Serial Numbers')
                                    ->schema([
                                        Repeater::make('face_id5_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('FACE ID 5 Serial Number #') . ' ' . ++self::$indexRepeater3)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('face_id5_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('face_id5_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('face_id5_quantity') <= 0)
                                    ->collapsible(),

                                // For FACE ID 6 Serial Numbers
                                Section::make('FACE ID 6 Serial Numbers')
                                    ->schema([
                                        Repeater::make('face_id6_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('FACE ID 6 Serial Number #') . ' ' . ++self::$indexRepeater4)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('face_id6_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('face_id6_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('face_id6_quantity') <= 0)
                                    ->collapsible(),

                                Select::make('implementer')
                                    ->label('Assign Implementer')
                                    ->options(function () {
                                        return User::whereIn('role_id', [4, 5])
                                            ->orWhere(function ($query) {
                                                $query->where('additional_role', 4);
                                            })
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->disabled(function (HardwareHandover $record) {
                                        // Only disable if there's an implementer from software handover
                                        if ($record && $record->lead_id) {
                                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $record->lead_id)
                                                ->latest()
                                                ->first();

                                            return $softwareHandover && $softwareHandover->implementer;
                                        }

                                        return false; // Enable field if no software handover exists
                                    })
                                    ->default(function (HardwareHandover $record) {
                                        // First, check if we already have a set implementer for this record
                                        if ($record && $record->implementer) {
                                            return $record->implementer;
                                        }

                                        // If not, try to get the implementer from the associated software handover
                                        if ($record && $record->lead_id) {
                                            // Find the software handover for the same lead
                                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $record->lead_id)
                                                ->latest()
                                                ->first();

                                            // Return the implementer ID if found
                                            if ($softwareHandover && $softwareHandover->implementer) {
                                                return $softwareHandover->implementer;
                                            }
                                        }

                                        return null; // No default implementer found
                                    }),

                                Grid::make(2)
                                ->schema([
                                    FileUpload::make('invoice_file')
                                        ->label('Upload Invoice')
                                        ->required()
                                        ->disk('public')
                                        ->directory('handovers/invoices')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->openable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                            $companyName = Str::slug($get('company_name') ?? 'invoice');
                                            $date = now()->format('Y-m-d');
                                            $random = Str::random(5);
                                            $extension = $file->getClientOriginalExtension();

                                            return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                        }),

                                    FileUpload::make('sales_order_file')
                                        ->label('Upload Sales Order')
                                        ->disk('public')
                                        ->directory('handovers/sales_orders')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->openable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                            $companyName = Str::slug($get('company_name') ?? 'invoice');
                                            $date = now()->format('Y-m-d');
                                            $random = Str::random(5);
                                            $extension = $file->getClientOriginalExtension();

                                            return "{$companyName}-salesorder-{$date}-{$random}.{$extension}";
                                        }),
                                ]),
                            ])
                            ->action(function (HardwareHandover $record, array $data): void {
                                // Process serial data
                                $serialData = [
                                    'tc10_serials' => $data['tc10_serials'] ?? [],
                                    'tc20_serials' => $data['tc20_serials'] ?? [],
                                    'face_id5_serials' => $data['face_id5_serials'] ?? [],
                                    'face_id6_serials' => $data['face_id6_serials'] ?? [],
                                ];

                                // Process file uploads
                                if (isset($data['invoice_file']) && is_array($data['invoice_file'])) {
                                    // Get existing invoice files
                                    $existingFiles = [];
                                    if ($record->invoice_file) {
                                        $existingFiles = is_string($record->invoice_file)
                                            ? json_decode($record->invoice_file, true)
                                            : $record->invoice_file;

                                        if (!is_array($existingFiles)) {
                                            $existingFiles = [];
                                        }
                                    }

                                    // Merge existing files with newly uploaded ones
                                    $allFiles = array_merge($existingFiles, $data['invoice_file']);

                                    // Update data with combined files
                                    $data['invoice_file'] = json_encode($allFiles);
                                }

                                if (isset($data['sales_order_file']) && is_array($data['sales_order_file'])) {
                                    // Get existing sales order files
                                    $existingFiles = [];
                                    if ($record->sales_order_file) {
                                        $existingFiles = is_string($record->sales_order_file)
                                            ? json_decode($record->sales_order_file, true)
                                            : $record->sales_order_file;

                                        if (!is_array($existingFiles)) {
                                            $existingFiles = [];
                                        }
                                    }

                                    // Merge existing files with newly uploaded ones
                                    $allFiles = array_merge($existingFiles, $data['sales_order_file']);

                                    // Update data with combined files
                                    $data['sales_order_file'] = json_encode($allFiles);
                                }

                                if ($record->implementer) {
                                    $implementerName = $record->implementer;
                                    // Try to find the user by name to get their ID and email
                                    $implementerUser = \App\Models\User::where('name', $implementerName)->first();
                                    if ($implementerUser) {
                                        $implementerId = $implementerUser->id;
                                        $implementerEmail = $implementerUser->email;
                                    }
                                } else {
                                    $implementerName = 'Unknown';
                                }

                                // Check if implementer is selected from the form (when field is enabled)
                                if (isset($data['implementer']) && !empty($data['implementer'])) {
                                    $implementerId = $data['implementer'];
                                    $implementer = \App\Models\User::find($implementerId);
                                    if ($implementer) {
                                        $implementerName = $implementer->name;
                                        $implementerEmail = $implementer->email;
                                    }
                                } else {
                                    // Fallback to getting implementer from software handover
                                    $softwareHandover = $record->lead ? \App\Models\SoftwareHandover::where('lead_id', $record->lead->id)
                                        ->latest()
                                        ->first() : null;

                                    if ($softwareHandover && $softwareHandover->implementer) {
                                        $implementerName = $softwareHandover->implementer;
                                        // Try to find the user by name to get their email
                                        $implementer = \App\Models\User::where('name', $implementerName)->first();
                                        $implementerEmail = $implementer?->email ?? null;
                                    }
                                }

                                // Get the salesperson info
                                $salespersonId = $record->lead->salesperson ?? null;
                                $salesperson = \App\Models\User::find($salespersonId);
                                $salespersonEmail = $salesperson?->email ?? null;
                                $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                                $updateData = [
                                    'tc10_quantity' => $data['tc10_quantity'],
                                    'tc20_quantity' => $data['tc20_quantity'],
                                    'face_id5_quantity' => $data['face_id5_quantity'],
                                    'face_id6_quantity' => $data['face_id6_quantity'],
                                    'time_beacon_quantity' => $data['time_beacon_quantity'],
                                    'nfc_tag_quantity' => $data['nfc_tag_quantity'],
                                    'implementer' => $implementerName ?? null,
                                    'device_serials' => json_encode($serialData),
                                    'pending_migration_at' => now(),
                                    'status' => 'Pending Migration',
                                ];

                                if (isset($data['invoice_file'])) {
                                    $updateData['invoice_file'] = $data['invoice_file'];
                                }

                                if (isset($data['sales_order_file'])) {
                                    $updateData['sales_order_file'] = $data['sales_order_file'];
                                }

                                $record->update($updateData);

                                try {
                                    // Get the controller for PDF generation
                                    $pdfController = new \App\Http\Controllers\GenerateHardwareHandoverPdfController();

                                    // Generate the new PDF
                                    $pdfPath = $pdfController->generateInBackground($record);

                                    if ($pdfPath) {
                                        // Update the record with the new PDF path if needed
                                        if ($pdfPath !== $record->handover_pdf) {
                                            $record->update(['handover_pdf' => $pdfPath]);
                                        }

                                        \Illuminate\Support\Facades\Log::info("Hardware handover PDF regenerated successfully", [
                                            'handover_id' => $record->id,
                                            'pdf_path' => $pdfPath
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error("Failed to regenerate hardware handover PDF", [
                                        'handover_id' => $record->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                // Send email notification
                                try {
                                    $viewName = 'emails.pending_migration_notification';

                                    $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';
                                    $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                                    // Format the handover ID properly
                                    $handoverId = 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

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

                                    $salesOrderFiles = [];
                                    if ($record->sales_order_file) {
                                        $salesOrderFileArray = is_string($record->sales_order_file)
                                            ? json_decode($record->sales_order_file, true)
                                            : $record->sales_order_file;

                                        if (is_array($salesOrderFileArray)) {
                                            foreach ($salesOrderFileArray as $file) {
                                                $salesOrderFiles[] = url('storage/' . $file);
                                            }
                                        }
                                    }

                                    $deviceSerials = [];
                                    if ($record->device_serials) {
                                        $deviceSerials = is_string($record->device_serials)
                                            ? json_decode($record->device_serials, true)
                                            : $record->device_serials;
                                    }

                                    // If we're just creating the serials now, use the form data
                                    if (empty($deviceSerials) && isset($serialData)) {
                                        $deviceSerials = $serialData;
                                    }

                                    // Create email content structure
                                    $emailContent = [
                                        'implementer' => [
                                            'name' => $implementerName ?? null,
                                        ],
                                        'company' => [
                                            'name' => $companyName,
                                        ],
                                        'salesperson' => [
                                            'name' => $salespersonName,
                                        ],
                                        'device_serials' => $deviceSerials,
                                        'handover_id' => $handoverId,
                                        // CHANGE created_at to completed_at
                                        'createdAt' => $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y') : now()->format('d M Y'),
                                        'handoverFormUrl' => $handoverFormUrl,
                                        'invoiceFiles' => $invoiceFiles,
                                        'salesOrderFiles' => $salesOrderFiles,
                                        'devices' => [
                                            'tc10' => [
                                                'quantity' => (int)$data['tc10_quantity'],
                                                'status' => (int)$data['tc10_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'tc20' => [
                                                'quantity' => (int)$data['tc20_quantity'],
                                                'status' => (int)$data['tc20_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'face_id5' => [
                                                'quantity' => (int)$data['face_id5_quantity'],
                                                'status' => (int)$data['face_id5_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'face_id6' => [
                                                'quantity' => (int)$data['face_id6_quantity'],
                                                'status' => (int)$data['face_id6_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'time_beacon' => [
                                                'quantity' => (int)$data['time_beacon_quantity'],
                                                'status' => (int)$data['time_beacon_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'nfc_tag' => [
                                                'quantity' => (int)$data['nfc_tag_quantity'],
                                                'status' => (int)$data['nfc_tag_quantity'] > 0 ? 'Available' : 'Pending Stock'
                                            ]
                                        ]
                                    ];

                                    // Add implementer email if valid
                                    if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                        $recipients[] = $implementerEmail;
                                    }

                                    // Add salesperson email if valid
                                    if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                        $recipients[] = $salespersonEmail;
                                    }

                                    // Get authenticated user's email for sender
                                    $authUser = auth()->user();
                                    $senderEmail = $authUser->email;
                                    $senderName = $authUser->name;

                                    // Send email with template and custom subject format
                                    if (count($recipients) > 0) {
                                        \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                                            $message->from($senderEmail, $senderName)
                                                ->to($recipients)
                                                ->subject("HARDWARE HANDOVER ID {$handoverId} | {$companyName}");
                                        });

                                        \Illuminate\Support\Facades\Log::info("Project assignment email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                    }
                                } catch (\Exception $e) {
                                    // Log error but don't stop the process
                                    \Illuminate\Support\Facades\Log::error("Email sending failed for handover #{$record->id}: {$e->getMessage()}");
                                }

                                Notification::make()
                                    ->title('Hardware Handover processed')
                                    ->success()
                                    ->body('Status updated to: ' . $record->status)
                                    ->send();
                            })
                            ->requiresConfirmation(false)
                            ->hidden(fn (HardwareHandover $record): bool =>
                                !in_array($record->status, ['New', 'Pending Stock']) || auth()->user()->role_id === 2
                            ),

                        Action::make('mark_rejected')
                            ->label('Reject')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->hidden(fn (HardwareHandover $record): bool =>
                                $record->status !== 'New' || auth()->user()->role_id === 2
                            )
                            ->form([
                                \Filament\Forms\Components\Textarea::make('reject_reason')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->afterStateHydrated(fn($state) => Str::upper($state))
                                    ->afterStateUpdated(fn($state) => Str::upper($state))
                                    ->label('Reason for Rejection')
                                    ->required()
                                    ->placeholder('Please provide a reason for rejecting this handover')
                                    ->maxLength(500)
                            ])
                            ->action(function (HardwareHandover $record, array $data): void {
                                // Update both status and add the rejection remarks
                                $record->update([
                                    'status' => 'Rejected',
                                    'reject_reason' => $data['reject_reason']
                                ]);

                                Notification::make()
                                    ->title('Hardware Handover marked as rejected')
                                    ->body('Rejection reason: ' . $data['reject_reason'])
                                    ->danger()
                                    ->send();
                            })
                            ->requiresConfirmation(false),
                        Action::make('mark_as_completed_migration')
                            ->label(fn(): HtmlString => new HtmlString('Mark as Completed<br> Migration'))
                            ->icon('heroicon-o-check-circle')
                            ->color('primary')
                            ->requiresConfirmation()
                            ->modalHeading("Mark as Completed: Migration")
                            ->modalDescription('Are you sure you want to mark this handover as migration completed?')
                            ->modalSubmitActionLabel('Yes, Mark as Completed')
                            ->modalCancelActionLabel('No, Cancel')
                            ->form([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('tc10_quantity')
                                            ->label('TC10')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));

                                                $set('tc10_serials', $serials);
                                            }),

                                        TextInput::make('face_id5_quantity')
                                            ->label('FACE ID 5')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));

                                                $set('face_id5_serials', $serials);
                                            }),

                                        TextInput::make('time_beacon_quantity')
                                            ->label('TIME BEACON')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('time_beacon_serials', $serials);
                                            }),

                                        TextInput::make('tc20_quantity')
                                            ->label('TC20')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('tc20_serials', $serials);
                                            }),

                                        TextInput::make('face_id6_quantity')
                                            ->label('FACE ID 6')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('face_id6_serials', $serials);
                                            }),

                                        TextInput::make('nfc_tag_quantity')
                                            ->label('NFC TAG')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('nfc_tag_serials', $serials);
                                            }),
                                    ]),

                                Section::make('TC10 Serial Numbers')
                                    ->schema([
                                        Repeater::make('tc10_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('TC10 Serial Number #') . ' ' . ++self::$indexRepeater)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('tc10_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('tc10_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('tc10_quantity') <= 0)
                                    ->collapsible(),

                                Section::make('TC20 Serial Numbers')
                                    ->schema([
                                        Repeater::make('tc20_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('TC20 Serial Number #') . ' ' . ++self::$indexRepeater2)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('tc20_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('tc20_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('tc20_quantity') <= 0)
                                    ->collapsible(),

                                // For FACE ID 5 Serial Numbers
                                Section::make('FACE ID 5 Serial Numbers')
                                    ->schema([
                                        Repeater::make('face_id5_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('FACE ID 5 Serial Number #') . ' ' . ++self::$indexRepeater3)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('face_id5_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('face_id5_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('face_id5_quantity') <= 0)
                                    ->collapsible(),

                                // For FACE ID 6 Serial Numbers
                                Section::make('FACE ID 6 Serial Numbers')
                                    ->schema([
                                        Repeater::make('face_id6_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('FACE ID 6 Serial Number #') . ' ' . ++self::$indexRepeater4)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('face_id6_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('face_id6_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('face_id6_quantity') <= 0)
                                    ->collapsible(),

                                Select::make('implementer')
                                    ->label('Assign Implementer')
                                    ->options(function () {
                                        return User::whereIn('role_id', [4, 5])
                                            ->orWhere(function ($query) {
                                                $query->where('additional_role', 4);
                                            })
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->disabled(function (HardwareHandover $record) {
                                        // Only disable if there's an implementer from software handover
                                        if ($record && $record->lead_id) {
                                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $record->lead_id)
                                                ->latest()
                                                ->first();

                                            return $softwareHandover && $softwareHandover->implementer;
                                        }

                                        return false; // Enable field if no software handover exists
                                    })
                                    ->default(function (HardwareHandover $record) {
                                        // First, check if we already have a set implementer for this record
                                        if ($record && $record->implementer) {
                                            return $record->implementer;
                                        }

                                        // If not, try to get the implementer from the associated software handover
                                        if ($record && $record->lead_id) {
                                            // Find the software handover for the same lead
                                            $softwareHandover = \App\Models\SoftwareHandover::where('lead_id', $record->lead_id)
                                                ->latest()
                                                ->first();

                                            // Return the implementer ID if found
                                            if ($softwareHandover && $softwareHandover->implementer) {
                                                return $softwareHandover->implementer;
                                            }
                                        }

                                        return null; // No default implementer found
                                    }),

                                Grid::make(2)
                                ->schema([
                                    FileUpload::make('invoice_file')
                                        ->label('Upload Invoice')
                                        ->required()
                                        ->disk('public')
                                        ->directory('handovers/invoices')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->openable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                            $companyName = Str::slug($get('company_name') ?? 'invoice');
                                            $date = now()->format('Y-m-d');
                                            $random = Str::random(5);
                                            $extension = $file->getClientOriginalExtension();

                                            return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                        }),

                                    FileUpload::make('sales_order_file')
                                        ->label('Upload Sales Order')
                                        ->required()
                                        ->disk('public')
                                        ->directory('handovers/sales_orders')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->openable()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                            $companyName = Str::slug($get('company_name') ?? 'invoice');
                                            $date = now()->format('Y-m-d');
                                            $random = Str::random(5);
                                            $extension = $file->getClientOriginalExtension();

                                            return "{$companyName}-salesorder-{$date}-{$random}.{$extension}";
                                        }),
                                ]),
                            ])
                            ->action(function (HardwareHandover $record, array $data): void {
                                $serialData = [
                                    'tc10_serials' => $data['tc10_serials'] ?? [],
                                    'tc20_serials' => $data['tc20_serials'] ?? [],
                                    'face_id5_serials' => $data['face_id5_serials'] ?? [],
                                    'face_id6_serials' => $data['face_id6_serials'] ?? [],
                                ];
                                // Process file uploads
                                if (isset($data['invoice_file']) && is_array($data['invoice_file'])) {
                                    // Get existing invoice files
                                    $existingFiles = [];
                                    if ($record->invoice_file) {
                                        $existingFiles = is_string($record->invoice_file)
                                            ? json_decode($record->invoice_file, true)
                                            : $record->invoice_file;

                                        if (!is_array($existingFiles)) {
                                            $existingFiles = [];
                                        }
                                    }

                                    // Merge existing files with newly uploaded ones
                                    $allFiles = array_merge($existingFiles, $data['invoice_file']);

                                    // Update data with combined files
                                    $data['invoice_file'] = json_encode($allFiles);
                                }

                                if (isset($data['sales_order_file']) && is_array($data['sales_order_file'])) {
                                    // Get existing sales order files
                                    $existingFiles = [];
                                    if ($record->sales_order_file) {
                                        $existingFiles = is_string($record->sales_order_file)
                                            ? json_decode($record->sales_order_file, true)
                                            : $record->sales_order_file;

                                        if (!is_array($existingFiles)) {
                                            $existingFiles = [];
                                        }
                                    }

                                    // Merge existing files with newly uploaded ones
                                    $allFiles = array_merge($existingFiles, $data['sales_order_file']);

                                    // Update data with combined files
                                    $data['sales_order_file'] = json_encode($allFiles);
                                }

                                if ($record->implementer) {
                                    $implementerName = $record->implementer;
                                    // Try to find the user by name to get their ID and email
                                    $implementerUser = \App\Models\User::where('name', $implementerName)->first();
                                    if ($implementerUser) {
                                        $implementerId = $implementerUser->id;
                                        $implementerEmail = $implementerUser->email;
                                    }
                                } else {
                                    $implementerName = 'Unknown';
                                }

                                // Check if implementer is selected from the form (when field is enabled)
                                if (isset($data['implementer']) && !empty($data['implementer'])) {
                                    $implementerId = $data['implementer'];
                                    $implementer = \App\Models\User::find($implementerId);
                                    if ($implementer) {
                                        $implementerName = $implementer->name;
                                        $implementerEmail = $implementer->email;
                                    }
                                } else {
                                    // Fallback to getting implementer from software handover
                                    $softwareHandover = $record->lead ? \App\Models\SoftwareHandover::where('lead_id', $record->lead->id)
                                        ->latest()
                                        ->first() : null;

                                    if ($softwareHandover && $softwareHandover->implementer) {
                                        $implementerName = $softwareHandover->implementer;
                                        // Try to find the user by name to get their email
                                        $implementer = \App\Models\User::where('name', $implementerName)->first();
                                        $implementerEmail = $implementer?->email ?? null;
                                    }
                                }

                                // Get the salesperson info
                                $salespersonId = $record->lead->salesperson ?? null;
                                $salesperson = \App\Models\User::find($salespersonId);
                                $salespersonEmail = $salesperson?->email ?? null;
                                $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                                $updateData = [
                                    'tc10_quantity' => $data['tc10_quantity'],
                                    'tc20_quantity' => $data['tc20_quantity'],
                                    'face_id5_quantity' => $data['face_id5_quantity'],
                                    'face_id6_quantity' => $data['face_id6_quantity'],
                                    'time_beacon_quantity' => $data['time_beacon_quantity'],
                                    'nfc_tag_quantity' => $data['nfc_tag_quantity'],
                                    'device_serials' => json_encode($serialData),
                                    'implementer' => $implementerName ?? null,
                                    'pending_migration_at' => now(),
                                    'status' => 'Completed Migration',
                                ];

                                if (isset($data['invoice_file'])) {
                                    $updateData['invoice_file'] = $data['invoice_file'];
                                }

                                if (isset($data['sales_order_file'])) {
                                    $updateData['sales_order_file'] = $data['sales_order_file'];
                                }

                                $record->update($updateData);

                                try {
                                    // Get the controller for PDF generation
                                    $pdfController = new \App\Http\Controllers\GenerateHardwareHandoverPdfController();

                                    // Generate the new PDF
                                    $pdfPath = $pdfController->generateInBackground($record);

                                    if ($pdfPath) {
                                        // Update the record with the new PDF path if needed
                                        if ($pdfPath !== $record->handover_pdf) {
                                            $record->update(['handover_pdf' => $pdfPath]);
                                        }

                                        \Illuminate\Support\Facades\Log::info("Hardware handover PDF regenerated successfully", [
                                            'handover_id' => $record->id,
                                            'pdf_path' => $pdfPath
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error("Failed to regenerate hardware handover PDF", [
                                        'handover_id' => $record->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                Notification::make()
                                    ->title('Hardware Handover processed')
                                    ->success()
                                    ->body('Status updated to: ' . $record->status)
                                    ->send();
                            })
                            ->requiresConfirmation(false)
                            ->hidden(fn (HardwareHandover $record): bool =>
                                !in_array($record->status, ['New', 'Pending Stock']) || auth()->user()->role_id === 2
                            ),
                        Action::make('mark_as_completed_installation')
                            ->label(fn(): HtmlString => new HtmlString('Mark as Completed:<br>Installation'))
                            ->icon('heroicon-o-wrench')
                            ->color('primary')
                            ->hidden(fn (HardwareHandover $record): bool =>
                                $record->status !== 'Completed Migration' || auth()->user()->role_id === 2
                            )
                            ->modalWidth('3xl')
                            ->modalHeading("Add Repair Appointment")
                            ->form([
                                ToggleButtons::make('mode')
                                    ->label('')
                                    ->options([
                                        'auto' => 'Auto',
                                        'custom' => 'Custom',
                                    ])
                                    ->reactive()
                                    ->inline()
                                    ->grouped()
                                    ->default('auto')
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state === 'custom') {
                                            $set('date', null);
                                            $set('start_time', null);
                                            $set('end_time', null);
                                        }else{
                                            $set('date', Carbon::today()->toDateString());
                                            $set('start_time', Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30))->format('H:i'));
                                            $set('end_time', Carbon::parse($get('start_time'))->addHour()->format('H:i'));
                                        }
                                    }),

                                Grid::make(3)
                                    ->schema([
                                        DatePicker::make('date')
                                            ->required()
                                            ->label('DATE')
                                            ->default(function ($record = null) {
                                                return Carbon::today()->toDateString();
                                            })
                                            ->reactive(),

                                        TimePicker::make('start_time')
                                            ->label('START TIME')
                                            ->required()
                                            ->seconds(false)
                                            ->reactive()
                                            ->default(function ($record = null) {
                                                $now = Carbon::now();
                                                return $now->addMinutes(30 - ($now->minute % 30))->format('H:i');
                                            })
                                            ->datalist(function (callable $get) {
                                                $user = Auth::user();
                                                $date = $get('date');

                                                if ($get('mode') === 'custom') {
                                                    return [];
                                                }

                                                $times = [];
                                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30))->setSeconds(0);

                                                if ($user && in_array($user->role_id, [9]) && $date) {
                                                    // Fetch all booked appointments as full models
                                                    $appointments = RepairAppointment::where('technician', $user->id)
                                                        ->whereDate('date', $date)
                                                        ->whereIn('status', ['New', 'Completed'])
                                                        ->get(['start_time', 'end_time']);

                                                    for ($i = 0; $i < 48; $i++) {
                                                        $slotStart = $startTime->copy();
                                                        $slotEnd = $startTime->copy()->addMinutes(30);
                                                        $formattedTime = $slotStart->format('H:i');

                                                        $isBooked = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                                                            $apptStart = Carbon::createFromFormat('H:i:s', $appointment->start_time);
                                                            $apptEnd = Carbon::createFromFormat('H:i:s', $appointment->end_time);

                                                            // Check if the slot overlaps with the appointment
                                                            return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
                                                        });

                                                        if (!$isBooked) {
                                                            $times[] = $formattedTime;
                                                        }

                                                        $startTime->addMinutes(30);
                                                    }
                                                } else {
                                                    for ($i = 0; $i < 48; $i++) {
                                                        $times[] = $startTime->format('H:i');
                                                        $startTime->addMinutes(30);
                                                    }
                                                }

                                                return $times;
                                            })
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($get('mode') === 'auto' && $state) {
                                                    $set('end_time', Carbon::parse($state)->addHour()->format('H:i'));
                                                }
                                            }),

                                        TimePicker::make('end_time')
                                            ->label('END TIME')
                                            ->required()
                                            ->seconds(false)
                                            ->reactive()
                                            ->default(function ($record = null, callable $get) {
                                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));
                                                return $startTime->addHour()->format('H:i');
                                            })
                                            ->datalist(function (callable $get) {
                                                $user = Auth::user();
                                                $date = $get('date');

                                                if ($get('mode') === 'custom') {
                                                    return [];
                                                }

                                                $times = [];
                                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));

                                                if ($user && in_array($user->role_id, [9]) && $date) {
                                                    // Fetch booked time slots for this technician on the selected date
                                                    $bookedAppointments = RepairAppointment::where('technician', $user->id)
                                                        ->whereDate('date', $date)
                                                        ->pluck('end_time', 'start_time')
                                                        ->toArray();

                                                    for ($i = 0; $i < 48; $i++) {
                                                        $formattedTime = $startTime->format('H:i');

                                                        // Check if time is booked
                                                        $isBooked = collect($bookedAppointments)->contains(function ($end, $start) use ($formattedTime) {
                                                            return $formattedTime >= $start && $formattedTime <= $end;
                                                        });

                                                        if (!$isBooked) {
                                                            $times[] = $formattedTime;
                                                        }

                                                        $startTime->addMinutes(30);
                                                    }
                                                } else {
                                                    // Default available slots
                                                    for ($i = 0; $i < 48; $i++) {
                                                        $times[] = $startTime->format('H:i');
                                                        $startTime->addMinutes(30);
                                                    }
                                                }

                                                return $times;
                                            }),
                                        ]),
                                        Grid::make(3)
                                        ->schema([
                                            Select::make('type')
                                                ->options([
                                                    'NEW INSTALLATION' => 'NEW INSTALLATION',
                                                    'REPAIR' => 'REPAIR',
                                                    'MAINTENANCE SERVICE' => 'MAINTENANCE SERVICE',
                                                    'SITE SURVEY' => 'SITE SURVEY',
                                                ])
                                                ->default(function ($record = null) {
                                                    // For new appointments, default to NEW INSTALLATION
                                                    return 'NEW INSTALLATION';
                                                })
                                                ->required()
                                                ->label('DEMO TYPE')
                                                ->reactive(),

                                            Select::make('appointment_type')
                                                ->options([
                                                    'ONSITE' => 'ONSITE',
                                                ])
                                                ->required()
                                                ->default('ONSITE')
                                                ->label('APPOINTMENT TYPE'),

                                            Select::make('technician')
                                                ->options(function () {
                                                    // Get technicians (role_id 9) with their names as both keys and values
                                                    $technicians = \App\Models\User::where('role_id', 9)
                                                        ->orderBy('name')
                                                        ->get()
                                                        ->mapWithKeys(function ($tech) {
                                                            return [$tech->name => $tech->name];
                                                        })
                                                        ->toArray();

                                                    // Get resellers from reseller table with their names as both keys and values
                                                    $resellers = \App\Models\Reseller::orderBy('company_name')
                                                        ->get()
                                                        ->mapWithKeys(function ($reseller) {
                                                            return [$reseller->company_name => $reseller->company_name];
                                                        })
                                                        ->toArray();

                                                    // Return as option groups
                                                    return [
                                                        'Internal Technicians' => $technicians,
                                                        'Reseller Partners' => $resellers,
                                                    ];
                                                })
                                                ->disableOptionWhen(function ($value, $get) {
                                                    $date = $get('date');
                                                    $startTime = $get('start_time');
                                                    $endTime = $get('end_time');

                                                    // If any of the required fields is not filled, don't disable options
                                                    if (!$date || !$startTime || !$endTime) {
                                                        return false;
                                                    }

                                                    $parsedDate = Carbon::parse($date)->format('Y-m-d');
                                                    $parsedStartTime = Carbon::parse($startTime)->format('H:i:s');
                                                    $parsedEndTime = Carbon::parse($endTime)->format('H:i:s');

                                                    // Check if the technician has any overlapping appointments
                                                    $hasOverlap = RepairAppointment::where('technician', $value)
                                                        ->whereIn('status', ['New', 'Done']) // Only check active appointments
                                                        ->whereDate('date', $parsedDate)
                                                        ->where(function ($query) use ($parsedStartTime, $parsedEndTime) {
                                                            $query->whereBetween('start_time', [$parsedStartTime, $parsedEndTime])
                                                                ->orWhereBetween('end_time', [$parsedStartTime, $parsedEndTime])
                                                                ->orWhere(function ($query) use ($parsedStartTime, $parsedEndTime) {
                                                                    $query->where('start_time', '<', $parsedStartTime)
                                                                        ->where('end_time', '>', $parsedEndTime);
                                                                });
                                                        })
                                                        ->exists();

                                                    return $hasOverlap;
                                                })
                                                ->searchable()
                                                ->required()
                                                ->default(function ($record = null) {
                                                    return $record ? $record->technician : null;
                                                })
                                                ->placeholder('Select a technician'),
                                            ]),
                                Textarea::make('remarks')
                                    ->label('REMARKS')
                                    ->rows(3)
                                    ->autosize()
                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                                TextInput::make('required_attendees')
                                    ->label('Required Attendees')
                                    ->default(function ($record = null) {
                                        if ($record && !empty($record->required_attendees)) {
                                            // If it looks like JSON, decode it and format as semicolon-separated string
                                            if (is_string($record->required_attendees) && $this->isJson($record->required_attendees)) {
                                                $attendees = json_decode($record->required_attendees, true);
                                                return is_array($attendees) ? implode(';', $attendees) : '';
                                            }
                                            return $record->required_attendees;
                                        }
                                        return null; // Return null for new appointments
                                    })
                                    ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                                Hidden::make('installation_type')
                                    ->default(function ($record) {
                                        // This ensures the field gets its value from the record
                                        return $record->installation_type ?? null;
                                    }),

                                FileUpload::make('reseller_invoice')
                                    ->label('Reseller Invoice')
                                    ->helperText('Upload reseller invoice')
                                    ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                    ->visible(fn(callable $get) => $get('installation_type') === 'external_installation')
                                    ->disk('public')
                                    ->directory('handovers/reseller_invoices')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->openable()
                                    ->downloadable()
                                    ->default(function ($record) {
                                        return $record->reseller_invoice ?? [];
                                    }),
                            ])
                            ->action(function (HardwareHandover $record, array $data): void {
                                // Process remarks to merge with existing ones
                                if (isset($data['remarks']) && is_array($data['remarks'])) {
                                    // Get existing admin remarks
                                    $existingAdminRemarks = [];
                                    if ($record->admin_remarks) {
                                        $existingAdminRemarks = is_string($record->admin_remarks)
                                            ? json_decode($record->admin_remarks, true)
                                            : $record->admin_remarks;

                                        if (!is_array($existingAdminRemarks)) {
                                            $existingAdminRemarks = [];
                                        }
                                    }

                                    // Process new remarks and encode attachments
                                    foreach ($data['remarks'] as $key => $remark) {
                                        // Add user information and timestamp
                                        $data['remarks'][$key]['user_id'] = auth()->id();
                                        $data['remarks'][$key]['user_name'] = auth()->user()->name;
                                        $data['remarks'][$key]['created_at'] = now()->format('Y-m-d H:i:s');

                                        // Store attachments in a proper format
                                        if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                                            $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                                        }
                                    }

                                    // Merge existing admin remarks with new ones
                                    $allAdminRemarks = array_merge($existingAdminRemarks, $data['remarks']);

                                    // Update the record with admin remarks and status
                                    $updateData = [
                                        'completed_at' => now(),
                                        'status' => 'Completed: Installation',
                                        'admin_remarks' => json_encode($allAdminRemarks)
                                    ];

                                    $record->update($updateData);
                                } else {
                                    // If no remarks provided, just update status
                                    $record->update([
                                        'completed_at' => now(),
                                        'status' => 'Completed: Installation',
                                    ]);
                                }

                                if ($record->installation_type === 'external_installation' && isset($data['reseller_invoice'])) {
                                    $record->update([
                                        'reseller_invoice' => $data['reseller_invoice']
                                    ]);
                                }

                                // Process required attendees from form data
                                $requiredAttendeesInput = $data['required_attendees'] ?? '';
                                $attendeeEmails = [];
                                if (!empty($requiredAttendeesInput)) {
                                    $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendeesInput)));
                                }

                                // Create a repair appointment for installation
                                $lead = $record->lead;
                                if ($lead) {
                                    $appointment = new \App\Models\RepairAppointment();
                                    $appointment->fill([
                                        'lead_id' => $lead->id,
                                        'type' => 'NEW INSTALLATION',
                                        'appointment_type' => 'ONSITE',
                                        'date' => $data['date'],
                                        'start_time' => $data['start_time'],
                                        'end_time' => $data['end_time'],
                                        'technician' => $data['technician'],
                                        'causer_id' => auth()->id(),
                                        'technician_assigned_date' => now(),
                                        'remarks' => $data['remarks'] ?? '',
                                        'status' => 'New',
                                        'title' => 'NEW INSTALLATION | ONSITE | TIMETEC REPAIR | ' . ($lead->companyDetail->company_name ?? $record->company_name),
                                        'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                                    ]);
                                    $appointment->save();

                                    // Regenerate PDF with updated information
                                    try {
                                        $pdfController = new \App\Http\Controllers\GenerateHardwareHandoverPdfController();
                                        $pdfPath = $pdfController->generateInBackground($record);

                                        if ($pdfPath && $pdfPath !== $record->handover_pdf) {
                                            $record->update(['handover_pdf' => $pdfPath]);
                                        }
                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error("Failed to regenerate hardware handover PDF", [
                                            'handover_id' => $record->id,
                                            'error' => $e->getMessage()
                                        ]);
                                    }

                                    // Set up email recipients for appointment notification
                                    $recipients = []; // Admin email

                                    // Add required attendees if they have valid emails
                                    foreach ($attendeeEmails as $email) {
                                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                                            $recipients[] = $email;
                                        }
                                    }

                                    // Get the salesperson info
                                    $salespersonId = $lead->salesperson ?? null;
                                    $salesperson = \App\Models\User::find($salespersonId);
                                    $salespersonEmail = $salesperson?->email ?? null;
                                    if ($salespersonEmail && !in_array($salespersonEmail, $recipients)) {
                                        $recipients[] = $salespersonEmail;
                                    }

                                    // Prepare email content for appointment notification
                                    $viewName = 'emails.repair_appointment_notification';
                                    $emailContent = [
                                        'leadOwnerName' => $lead->lead_owner ?? 'TimeTec Support',
                                        'lead' => [
                                            'lastName' => $lead->companyDetail->name ?? $lead->name,
                                            'company' => $lead->companyDetail->company_name ?? $record->company_name ?? 'N/A',
                                            'technicianName' => $data['technician'] ?? 'N/A',
                                            'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                                            'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                                            'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                                            'date' => Carbon::parse($data['date'])->format('d/m/Y') ?? 'N/A',
                                            'startTime' => Carbon::parse($data['start_time'])->format('h:i A') ?? 'N/A',
                                            'endTime' => Carbon::parse($data['end_time'])->format('h:i A') ?? 'N/A',
                                            'leadOwnerMobileNumber' => $salesperson?->mobile_number ?? 'N/A',
                                            'repair_type' => 'NEW INSTALLATION',
                                            'appointment_type' => 'ONSITE',
                                            'remarks' => $data['remarks'] ?? 'N/A',
                                        ],
                                    ];

                                    // Get authenticated user's email for sender
                                    $authUser = auth()->user();
                                    $senderEmail = $authUser->email;
                                    $senderName = $authUser->name;

                                    try {
                                        // Send email with template and custom subject format
                                        if (count($recipients) > 0) {
                                            \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead) {
                                                $companyName = $lead->companyDetail->company_name ?? 'Unknown Company';
                                                $message->from($senderEmail, $senderName)
                                                    ->to($recipients)
                                                    ->subject("TIMETEC REPAIR APPOINTMENT | NEW INSTALLATION | {$companyName}");
                                            });

                                            Notification::make()
                                                ->title('Installation appointment notification sent')
                                                ->success()
                                                ->body('Email notification sent to administrator and required attendees')
                                                ->send();
                                        }
                                    } catch (\Exception $e) {
                                        // Handle email sending failure
                                        \Illuminate\Support\Facades\Log::error("Email sending failed for installation appointment: Error: {$e->getMessage()}");

                                        Notification::make()
                                            ->title('Email Notification Failed')
                                            ->danger()
                                            ->body('Could not send email notification: ' . $e->getMessage())
                                            ->send();
                                    }
                                }

                                Notification::make()
                                    ->title('Hardware handover marked as completed with installation')
                                    ->success()
                                    ->body('An installation appointment has been created successfully')
                                    ->send();

                                // Emit event to refresh tables
                                $this->dispatch('refresh-hardwarehandover-tables');
                            }),
                        Action::make('mark_as_completed')
                            ->label(fn(): HtmlString => new HtmlString('Mark as Completed:<br>Courier'))
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->modalWidth('3xl')
                            // ->requiresConfirmation()
                            ->modalHeading("Mark as Completed")
                            ->modalDescription('Are you sure you want to mark this handover as completed? This will complete the hardware handover process.')
                            ->modalSubmitActionLabel('Yes, Mark as Completed')
                            ->modalCancelActionLabel('No, Cancel')
                            ->hidden(fn (HardwareHandover $record): bool =>
                                $record->status !== 'Completed Migration' || auth()->user()->role_id === 2
                            )
                            ->form([
                                \Filament\Forms\Components\Section::make('Category 1')
                                ->schema([
                                    // Hidden field to store the actual value
                                    \Filament\Forms\Components\Hidden::make('installation_type')
                                        ->default(function ($record) {
                                            return $record->installation_type ?? null;
                                        }),

                                    // Display the selected installation type
                                    \Filament\Forms\Components\Grid::make(1)
                                        ->schema([
                                            \Filament\Forms\Components\Placeholder::make('installation_type_display')
                                                ->label('Selected Installation Type')
                                                ->inlineLabel()
                                                ->content(function ($get) {
                                                    $type = $get('installation_type');
                                                    $label = match($type) {
                                                        'courier' => 'Courier',
                                                        'internal_installation' => 'Internal Installation',
                                                        'external_installation' => 'External Installation',
                                                        'self_pick_up' => 'Self Pick-up',
                                                        default => 'Not Selected'
                                                    };

                                                    // Different styles for different installation types
                                                    $styles = match($type) {
                                                        'courier' => 'background-color: #ecfdf5; color: #065f46; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 500; border: 1px solid #10b981;',
                                                        'internal_installation' => 'background-color: #eff6ff; color: #1e40af; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 500; border: 1px solid #3b82f6;',
                                                        'external_installation' => 'background-color: #fffbeb; color: #92400e; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 500; border: 1px solid #f59e0b;',
                                                        default => 'background-color: #f3f4f6; color: #1f2937; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 500; border: 1px solid #9ca3af;',
                                                    };

                                                    return new \Illuminate\Support\HtmlString(
                                                        "<span style=\"{$styles}\">{$label}</span>"
                                                    );
                                                })
                                        ])
                                ]),

                                \Filament\Forms\Components\Section::make('Category 2')
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('installation_type_helper')
                                            ->label('')
                                            ->content('Please select an installation type in Step 4 to see the relevant fields')
                                            ->visible(fn(callable $get) => empty($get('installation_type')))
                                            ->inlineLabel(),

                                        \Filament\Forms\Components\Grid::make(1)
                                            ->schema([
                                                \Filament\Forms\Components\Select::make('category2.installer')
                                                    ->label('Installer')
                                                    ->visible(fn(callable $get) => $get('installation_type') === 'internal_installation')
                                                    ->required(fn(callable $get) => $get('installation_type') === 'internal_installation')
                                                    ->options(function () {
                                                        // Retrieve options from the installer table
                                                        return \App\Models\Installer::pluck('company_name', 'id')->toArray();
                                                    })
                                                    ->disabled()
                                                    ->default(function ($record) {
                                                        // First check if record has category2 data already
                                                        if ($record && $record->category2) {
                                                            $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                            if (isset($category2['installer']) && !empty($category2['installer'])) {
                                                                return $category2['installer'];
                                                            }
                                                        }
                                                        return null;
                                                    })
                                                    ->searchable()
                                                    ->preload(),

                                                \Filament\Forms\Components\Select::make('category2.reseller')
                                                    ->label('Reseller')
                                                    ->visible(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                    ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                    ->options(function () {
                                                        // Retrieve options from the reseller table
                                                        return \App\Models\Reseller::pluck('company_name', 'id')->toArray();
                                                    })
                                                    ->disabled()
                                                    ->default(function ($record) {
                                                        // First check if record has category2 data already
                                                        if ($record && $record->category2) {
                                                            $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                            if (isset($category2['reseller']) && !empty($category2['reseller'])) {
                                                                return $category2['reseller'];
                                                            }
                                                        }
                                                        return null;
                                                    })
                                                    ->searchable()
                                                    ->preload(),

                                                \Filament\Forms\Components\Textarea::make('category2.courier_address')
                                                    ->label('Courier Address')
                                                    ->required(fn(callable $get) => $get('installation_type') === 'courier')
                                                    ->rows(2)
                                                    ->disabled()
                                                    ->default(function ($record) {
                                                        // First check if record has category2 data already
                                                        if ($record && $record->category2) {
                                                            $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                            if (isset($category2['courier_address']) && !empty($category2['courier_address'])) {
                                                                return $category2['courier_address'];
                                                            }
                                                        }

                                                        // If no record data, try to get lead address
                                                        $lead = \App\Models\Lead::find($record->lead_id);
                                                        if ($lead && $lead->companyDetail) {
                                                            $address = $lead->companyDetail->company_address1 ?? '';
                                                            if (!empty($lead->companyDetail->company_address2)) {
                                                                $address .= ", " . $lead->companyDetail->company_address2;
                                                            }
                                                            if (!empty($lead->companyDetail->postcode) || !empty($lead->companyDetail->state)) {
                                                                $address .= ", " . ($lead->companyDetail->postcode ?? '') . " " .
                                                                    ($lead->companyDetail->state ?? '');
                                                            }
                                                            return $address;
                                                        } else if ($lead) {
                                                            $address = $lead->address1 ?? '';
                                                            if (!empty($lead->address2)) {
                                                                $address .= ", " . $lead->address2;
                                                            }
                                                            if (!empty($lead->postcode) || !empty($lead->state)) {
                                                                $address .= ", " . ($lead->postcode ?? '') . " " . ($lead->state ?? '');
                                                            }
                                                            return $address;
                                                        }
                                                        return '';
                                                    })
                                                    ->visible(fn(callable $get) => $get('installation_type') === 'courier'),

                                                \Filament\Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        \Filament\Forms\Components\TextInput::make('category2.pic_name')
                                                            ->label('Name')
                                                            ->disabled()
                                                            ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->default(function ($record) {
                                                                if ($record && $record->category2) {
                                                                    $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                                    if (isset($category2['pic_name']) && !empty($category2['pic_name'])) {
                                                                        return $category2['pic_name'];
                                                                    }
                                                                }
                                                                $lead = \App\Models\Lead::find($record->lead_id);
                                                                return $lead->companyDetail->name ?? $lead->name ?? '';
                                                            })
                                                            ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),

                                                        \Filament\Forms\Components\TextInput::make('category2.pic_phone')
                                                            ->label('HP Number')
                                                            ->disabled()
                                                            ->tel()
                                                            ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                            ->default(function ($record) {
                                                                if ($record && $record->category2) {
                                                                    $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                                    if (isset($category2['pic_phone']) && !empty($category2['pic_phone'])) {
                                                                        return $category2['pic_phone'];
                                                                    }
                                                                }
                                                                $lead = \App\Models\Lead::find($record->lead_id);
                                                                return $lead->companyDetail->contact_no ?? $lead->contact_no ?? '';
                                                            })
                                                            ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),

                                                        \Filament\Forms\Components\TextInput::make('category2.email')
                                                            ->label('Email Address')
                                                            ->disabled()
                                                            ->email()
                                                            ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                            ->default(function ($record) {
                                                                if ($record && $record->category2) {
                                                                    $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                                    if (isset($category2['email']) && !empty($category2['email'])) {
                                                                        return $category2['email'];
                                                                    }
                                                                }
                                                                $lead = \App\Models\Lead::find($record->lead_id);
                                                                return $lead->companyDetail->email ?? $lead->email ?? '';
                                                            })
                                                            ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),
                                                    ]),
                                            ]),
                                    ]),
                                Section::make('Admin Remark')
                                    ->schema([
                                        Repeater::make('remarks')
                                            ->label('Admin Remarks')
                                            ->hiddenLabel(true)
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        Textarea::make('remark')
                                                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                            ->afterStateHydrated(fn($state) => Str::upper($state))
                                                            ->afterStateUpdated(fn($state) => Str::upper($state))
                                                            ->hiddenLabel(true)
                                                            ->label(function ($livewire) {
                                                                // Get the current array key from the state path
                                                                $statePath = $livewire->getFormStatePath();
                                                                $matches = [];
                                                                if (preg_match('/remarks\.(\d+)\./', $statePath, $matches)) {
                                                                    $index = (int) $matches[1];
                                                                    return 'Admin Remark ' . ($index + 1);
                                                                }

                                                                return 'Remark';
                                                            })
                                                            ->placeholder('Enter remark here')
                                                            ->autosize()
                                                            ->required()
                                                            ->rows(3),

                                                        FileUpload::make('attachments')
                                                            ->hiddenLabel(true)
                                                            ->disk('public')
                                                            ->directory('handovers/remark_attachments')
                                                            ->visibility('public')
                                                            ->multiple()
                                                            ->maxFiles(3)
                                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                                            ->openable()
                                                            ->downloadable()
                                                            ->required()
                                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                                                // In the context of a form within a table action, we can get the record from the mountedTableActionRecord property
                                                                $record = $this->mountedTableActionRecord;

                                                                if (!$record || !($record instanceof \App\Models\HardwareHandover)) {
                                                                    // Fallback if record not available
                                                                    $leadId = rand(1, 999); // Use a random number as fallback
                                                                    $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                                } else {
                                                                    // Use the lead ID from the record
                                                                    $leadId = $record->lead_id ?? $record->id;
                                                                    $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                                }

                                                                // Get extension
                                                                $extension = $file->getClientOriginalExtension();

                                                                // Generate a unique identifier (timestamp) to avoid overwriting files
                                                                $timestamp = now()->format('YmdHis');
                                                                $random = rand(1000, 9999);

                                                                return "{$formattedId}-HW-REMARK-{$timestamp}-{$random}.{$extension}";
                                                            }),
                                                    ])
                                            ])
                                            ->itemLabel(fn() => __('Admin Remark') . ' ' . ++self::$indexRepeater2)
                                            ->addActionLabel('Add Admin Remark')
                                            ->maxItems(5),
                                    ]),
                            ])
                            ->action(function (HardwareHandover $record, array $data): void {
                                // Process remarks to merge with existing ones
                                if (isset($data['remarks']) && is_array($data['remarks'])) {
                                    // Get existing admin remarks
                                    $existingAdminRemarks = [];
                                    if ($record->admin_remarks) {
                                        $existingAdminRemarks = is_string($record->admin_remarks)
                                            ? json_decode($record->admin_remarks, true)
                                            : $record->admin_remarks;

                                        if (!is_array($existingAdminRemarks)) {
                                            $existingAdminRemarks = [];
                                        }
                                    }

                                    // Process new remarks and encode attachments
                                    foreach ($data['remarks'] as $key => $remark) {
                                        // Store attachments in a proper format
                                        if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                                            $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                                        }
                                    }

                                    // Merge existing admin remarks with new ones
                                    $allAdminRemarks = array_merge($existingAdminRemarks, $data['remarks']);

                                    // Update the record with admin remarks
                                    $updateData = [
                                        'completed_at' => now(),
                                        'status' => 'Completed: Courier',
                                        'admin_remarks' => json_encode($allAdminRemarks)
                                    ];

                                    $record->update($updateData);
                                }
                                else {
                                    // If no remarks provided, just update status
                                    $record->update([
                                        'completed_at' => now(),
                                        'status' => 'Completed: Courier',
                                    ]);
                                }

                                // Get the implementer info
                                $implementerName = $record->implementer ?? 'Unknown';
                                $implementer = null;
                                $implementerEmail = null;

                                // Check if implementer is a name (string) or an ID
                                if ($implementerName && is_string($implementerName)) {
                                    // Try to find user by name
                                    $implementer = \App\Models\User::where('name', $implementerName)->first();
                                    if (!$implementer) {
                                        // As fallback, check if it might be an ID despite being stored as implementer
                                        $implementer = \App\Models\User::find($implementerName);
                                    }

                                    // Get email if we found a user
                                    $implementerEmail = $implementer?->email ?? null;
                                } else if (is_numeric($implementerName)) {
                                    // If implementer is stored as an ID
                                    $implementer = \App\Models\User::find($implementerName);
                                    $implementerEmail = $implementer?->email ?? null;
                                    $implementerName = $implementer?->name ?? 'Unknown';
                                }

                                // Get the salesperson info
                                $salespersonId = $record->lead->salesperson ?? null;
                                $salesperson = \App\Models\User::find($salespersonId);
                                $salespersonEmail = $salesperson?->email ?? null;
                                $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                                // Get the company name
                                $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                                $record->update($updateData);

                                // Regenerate PDF with updated information
                                try {
                                    $pdfController = new \App\Http\Controllers\GenerateHardwareHandoverPdfController();
                                    $pdfPath = $pdfController->generateInBackground($record);

                                    if ($pdfPath && $pdfPath !== $record->handover_pdf) {
                                        $record->update(['handover_pdf' => $pdfPath]);
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error("Failed to regenerate hardware handover PDF", [
                                        'handover_id' => $record->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                // Format the handover ID properly
                                $handoverId = 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

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

                                $salesOrderFiles = [];
                                if ($record->sales_order_file) {
                                    $salesOrderFileArray = is_string($record->sales_order_file)
                                        ? json_decode($record->sales_order_file, true)
                                        : $record->sales_order_file;

                                    if (is_array($salesOrderFileArray)) {
                                        foreach ($salesOrderFileArray as $file) {
                                            $salesOrderFiles[] = url('storage/' . $file);
                                        }
                                    }
                                }

                                // Send email notification
                                try {
                                    $viewName = 'emails.hardware_completed_notification';

                                    // Create email content structure
                                    $emailContent = [
                                        'implementer' => [
                                            'name' => $implementerName,
                                        ],
                                        'company' => [
                                            'name' => $companyName,
                                        ],
                                        'salesperson' => [
                                            'name' => $salespersonName,
                                        ],
                                        'handover_id' => $handoverId,
                                        'activatedAt' => now()->format('d M Y'),
                                        'handoverFormUrl' => $handoverFormUrl,
                                        'invoiceFiles' => $invoiceFiles,
                                        'salesOrderFiles' => $salesOrderFiles,
                                        'devices' => [
                                            'tc10' => [
                                                'quantity' => (int)$record->tc10_quantity,
                                                'status' => (int)$record->tc10_quantity > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'tc20' => [
                                                'quantity' => (int)$record->tc20_quantity,
                                                'status' => (int)$record->tc20_quantity > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'face_id5' => [
                                                'quantity' => (int)$record->face_id5_quantity,
                                                'status' => (int)$record->face_id5_quantity > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'face_id6' => [
                                                'quantity' => (int)$record->face_id6_quantity,
                                                'status' => (int)$record->face_id6_quantity > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'time_beacon' => [
                                                'quantity' => (int)$record->time_beacon_quantity,
                                                'status' => (int)$record->time_beacon_quantity > 0 ? 'Available' : 'Pending Stock'
                                            ],
                                            'nfc_tag' => [
                                                'quantity' => (int)$record->nfc_tag_quantity,
                                                'status' => (int)$record->nfc_tag_quantity > 0 ? 'Available' : 'Pending Stock'
                                            ]
                                            ],
                                        'admin_remarks' => []
                                    ];

                                    if ($record->admin_remarks) {
                                        $adminRemarks = is_string($record->admin_remarks)
                                            ? json_decode($record->admin_remarks, true)
                                            : $record->admin_remarks;

                                        if (is_array($adminRemarks)) {
                                            foreach ($adminRemarks as $remark) {
                                                $formattedRemark = [
                                                    'text' => $remark['remark'] ?? '',
                                                    'created_by' => $remark['user_name'] ?? 'Admin',
                                                    'created_at' => isset($remark['created_at'])
                                                        ? Carbon::parse($remark['created_at'])->format('d M Y h:i A')
                                                        : now()->format('d M Y h:i A'),
                                                    'attachments' => []
                                                ];

                                                // Process attachments for this remark
                                                if (isset($remark['attachments'])) {
                                                    $attachments = is_string($remark['attachments'])
                                                        ? json_decode($remark['attachments'], true)
                                                        : $remark['attachments'];

                                                    if (is_array($attachments)) {
                                                        foreach ($attachments as $attachment) {
                                                            $formattedRemark['attachments'][] = [
                                                                'url' => url('storage/' . $attachment),
                                                                'filename' => basename($attachment)
                                                            ];
                                                        }
                                                    }
                                                }

                                                $emailContent['admin_remarks'][] = $formattedRemark;
                                            }
                                        }
                                    }

                                    // Initialize recipients array
                                    $recipients = [];

                                    // Add implementer email if valid
                                    if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                        $recipients[] = $implementerEmail;
                                    }

                                    // Add salesperson email if valid
                                    if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                        $recipients[] = $salespersonEmail;
                                    }

                                    // Get authenticated user's email for sender
                                    $authUser = auth()->user();
                                    $senderEmail = $authUser->email;
                                    $senderName = $authUser->name;

                                    // Send email with template and custom subject format
                                    if (count($recipients) > 0) {
                                        \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                                            $message->from($senderEmail, $senderName)
                                                ->to($recipients)
                                                ->subject("HARDWARE HANDOVER ID {$handoverId} | {$companyName}");
                                        });

                                        \Illuminate\Support\Facades\Log::info("License activation email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                    }
                                } catch (\Exception $e) {
                                    // Log error but don't stop the process
                                    \Illuminate\Support\Facades\Log::error("Email sending failed for hardware handover #{$record->id}: {$e->getMessage()}");
                                }

                                Notification::make()
                                    ->title('Hardware handover has been completed successfully')
                                    ->success()
                                    ->body('Hardware handover has been marked as completed.')
                                    ->send();
                            }),
                            Action::make('update_device_serials')
                            ->label('Update Device Serials')
                            ->icon('heroicon-o-tag')
                            ->color('info')
                            ->modalHeading('Update Device Serial Numbers')
                            ->modalWidth('lg')
                            ->form([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('tc10_quantity')
                                            ->label('TC10')
                                            ->numeric()
                                            ->minValue(0)
                                            ->disabled()
                                            ->default(function(HardwareHandover $record) {
                                                return $record->tc10_quantity ?? 0;
                                            })
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));

                                                $set('tc10_serials', $serials);
                                            }),

                                        TextInput::make('face_id5_quantity')
                                            ->label('FACE ID 5')
                                            ->numeric()
                                            ->minValue(0)
                                            ->disabled()
                                            ->default(function(HardwareHandover $record) {
                                                return $record->face_id5_quantity ?? 0;
                                            })
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));

                                                $set('face_id5_serials', $serials);
                                            }),

                                        TextInput::make('time_beacon_quantity')
                                            ->label('TIME BEACON')
                                            ->numeric()
                                            ->minValue(0)
                                            ->disabled()
                                            ->default(function(HardwareHandover $record) {
                                                return $record->time_beacon_quantity ?? 0;
                                            })
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('time_beacon_serials', $serials);
                                            }),

                                        TextInput::make('tc20_quantity')
                                            ->label('TC20')
                                            ->numeric()
                                            ->minValue(0)
                                            ->disabled()
                                            ->default(function(HardwareHandover $record) {
                                                return $record->tc20_quantity ?? 0;
                                            })
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('tc20_serials', $serials);
                                            }),

                                        TextInput::make('face_id6_quantity')
                                            ->label('FACE ID 6')
                                            ->numeric()
                                            ->minValue(0)
                                            ->disabled()
                                            ->default(function(HardwareHandover $record) {
                                                return $record->face_id6_quantity ?? 0;
                                            })
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('face_id6_serials', $serials);
                                            }),

                                        TextInput::make('nfc_tag_quantity')
                                            ->label('NFC TAG')
                                            ->numeric()
                                            ->minValue(0)
                                            ->disabled()
                                            ->default(function(HardwareHandover $record) {
                                                return $record->nfc_tag_quantity ?? 0;
                                            })
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $quantity = (int)$state;
                                                $serials = array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                                $set('nfc_tag_serials', $serials);
                                            }),
                                    ]),

                                Section::make('TC10 Serial Numbers')
                                    ->schema([
                                        Repeater::make('tc10_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('TC10 Serial Number #') . ' ' . ++self::$indexRepeater)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->afterStateHydrated(function(Repeater $component, $state, HardwareHandover $record) {
                                                if (!$state && $record->device_serials) {
                                                    $deviceSerials = is_string($record->device_serials)
                                                        ? json_decode($record->device_serials, true)
                                                        : $record->device_serials;

                                                    if (isset($deviceSerials['tc10_serials']) && is_array($deviceSerials['tc10_serials'])) {
                                                        $component->state($deviceSerials['tc10_serials']);
                                                    }
                                                }
                                            })
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('tc10_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('tc10_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('tc10_quantity') <= 0)
                                    ->collapsible(),

                                Section::make('TC20 Serial Numbers')
                                    ->schema([
                                        Repeater::make('tc20_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('TC20 Serial Number #') . ' ' . ++self::$indexRepeater2)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->afterStateHydrated(function(Repeater $component, $state, HardwareHandover $record) {
                                                if (!$state && $record->device_serials) {
                                                    $deviceSerials = is_string($record->device_serials)
                                                        ? json_decode($record->device_serials, true)
                                                        : $record->device_serials;

                                                    if (isset($deviceSerials['tc20_serials']) && is_array($deviceSerials['tc20_serials'])) {
                                                        $component->state($deviceSerials['tc20_serials']);
                                                    }
                                                }
                                            })
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('tc20_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('tc20_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('tc20_quantity') <= 0)
                                    ->collapsible(),

                                // For FACE ID 5 Serial Numbers
                                Section::make('FACE ID 5 Serial Numbers')
                                    ->schema([
                                        Repeater::make('face_id5_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('FACE ID 5 Serial Number #') . ' ' . ++self::$indexRepeater3)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->afterStateHydrated(function(Repeater $component, $state, HardwareHandover $record) {
                                                if (!$state && $record->device_serials) {
                                                    $deviceSerials = is_string($record->device_serials)
                                                        ? json_decode($record->device_serials, true)
                                                        : $record->device_serials;

                                                    if (isset($deviceSerials['face_id5_serials']) && is_array($deviceSerials['face_id5_serials'])) {
                                                        $component->state($deviceSerials['face_id5_serials']);
                                                    }
                                                }
                                            })
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('face_id5_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('face_id5_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('face_id5_quantity') <= 0)
                                    ->collapsible(),

                                // For FACE ID 6 Serial Numbers
                                Section::make('FACE ID 6 Serial Numbers')
                                    ->schema([
                                        Repeater::make('face_id6_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('FACE ID 6 Serial Number #') . ' ' . ++self::$indexRepeater4)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->afterStateHydrated(function(Repeater $component, $state, HardwareHandover $record) {
                                                if (!$state && $record->device_serials) {
                                                    $deviceSerials = is_string($record->device_serials)
                                                        ? json_decode($record->device_serials, true)
                                                        : $record->device_serials;

                                                    if (isset($deviceSerials['face_id6_serials']) && is_array($deviceSerials['face_id6_serials'])) {
                                                        $component->state($deviceSerials['face_id6_serials']);
                                                    }
                                                }
                                            })
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('face_id6_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('face_id6_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('face_id6_quantity') <= 0)
                                    ->collapsible(),

                                // For TIME BEACON Serial Numbers
                                Section::make('TIME BEACON Serial Numbers')
                                    ->schema([
                                        Repeater::make('time_beacon_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('TIME BEACON Serial Number #') . ' ' . ++self::$indexRepeater4)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->afterStateHydrated(function(Repeater $component, $state, HardwareHandover $record) {
                                                if (!$state && $record->device_serials) {
                                                    $deviceSerials = is_string($record->device_serials)
                                                        ? json_decode($record->device_serials, true)
                                                        : $record->device_serials;

                                                    if (isset($deviceSerials['time_beacon_serials']) && is_array($deviceSerials['time_beacon_serials'])) {
                                                        $component->state($deviceSerials['time_beacon_serials']);
                                                    }
                                                }
                                            })
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('time_beacon_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('time_beacon_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('time_beacon_quantity') <= 0)
                                    ->collapsible(),

                                // For NFC TAG Serial Numbers
                                Section::make('NFC TAG Serial Numbers')
                                    ->schema([
                                        Repeater::make('nfc_tag_serials')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('serial')
                                                    ->label(fn() => __('NFC TAG Serial Number #') . ' ' . ++self::$indexRepeater4)
                                                    ->required()
                                                    ->maxLength(50)
                                            ])
                                            ->columns(2)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->afterStateHydrated(function(Repeater $component, $state, HardwareHandover $record) {
                                                if (!$state && $record->device_serials) {
                                                    $deviceSerials = is_string($record->device_serials)
                                                        ? json_decode($record->device_serials, true)
                                                        : $record->device_serials;

                                                    if (isset($deviceSerials['nfc_tag_serials']) && is_array($deviceSerials['nfc_tag_serials'])) {
                                                        $component->state($deviceSerials['nfc_tag_serials']);
                                                    }
                                                }
                                            })
                                            ->default(function (Get $get) {
                                                $quantity = (int)$get('nfc_tag_quantity');
                                                return array_map(function() {
                                                    return ['serial' => ''];
                                                }, array_fill(0, $quantity, null));
                                            })
                                            ->hidden(fn (Get $get): bool => (int)$get('nfc_tag_quantity') <= 0)
                                            ->columnSpanFull()
                                    ])
                                    ->hidden(fn (Get $get): bool => (int)$get('nfc_tag_quantity') <= 0)
                                    ->collapsible(),
                            ])
                            ->action(function (HardwareHandover $record, array $data): void {
                                // Process serial data
                                $serialData = [
                                    'tc10_serials' => $data['tc10_serials'] ?? [],
                                    'tc20_serials' => $data['tc20_serials'] ?? [],
                                    'face_id5_serials' => $data['face_id5_serials'] ?? [],
                                    'face_id6_serials' => $data['face_id6_serials'] ?? [],
                                    'time_beacon_serials' => $data['time_beacon_serials'] ?? [],
                                    'nfc_tag_serials' => $data['nfc_tag_serials'] ?? [],
                                ];

                                // Update the record with modified device serials and quantities
                                $updateData = [
                                    'device_serials' => json_encode($serialData),
                                ];

                                $record->update($updateData);

                                // Regenerate PDF with updated information
                                try {
                                    $pdfController = new \App\Http\Controllers\GenerateHardwareHandoverPdfController();
                                    $pdfPath = $pdfController->generateInBackground($record);

                                    if ($pdfPath && $pdfPath !== $record->handover_pdf) {
                                        $record->update(['handover_pdf' => $pdfPath]);
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error("Failed to regenerate hardware handover PDF", [
                                        'handover_id' => $record->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                Notification::make()
                                    ->title('Device Serial Numbers Updated')
                                    ->success()
                                    ->body('The device serial numbers have been successfully updated')
                                    ->send();
                            })
                            ->requiresConfirmation(false)
                            ->hidden(function (HardwareHandover $record): bool {
                                // Show button only when device_serials is null AND status is not in excluded list
                                $excludedStatuses = ['New', 'Pending Stock', 'Draft'];

                                // Return true to hide button when:
                                // 1. device_serials is not null, OR
                                // 2. status is in the excluded list
                                return !is_null($record->device_serials) || in_array($record->status, $excludedStatuses);
                            })
                        ])
                        ->button()
                        ->color('primary')
                        ->label('Actions')
                ]);
    }

    public function render()
    {
        return view('livewire.hardware-handover-all');
    }
}
