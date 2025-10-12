<?php
// filepath: /var/www/html/timeteccrm/app/Livewire/AdminHardwareV2Dashboard/HardwareV2NewTable.php

namespace App\Livewire\AdminHardwareV2Dashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\HardwareHandoverV2;
use App\Models\Lead;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;

class HardwareV2PendingStockTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;
    protected static ?int $indexRepeater3 = 0;
    protected static ?int $indexRepeater4 = 0;

    public $selectedUser;
    public $lastRefreshTime;
    public $currentDashboard;

    public function mount($currentDashboard = null)
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->currentDashboard = $currentDashboard ?? 'HardwareAdminV2';
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

    #[On('refresh-HardwareHandoverV2-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);
        $this->resetTable();
    }

    public function getNewHardwareHandovers()
    {
        return HardwareHandoverV2::query()
            ->whereIn('status', ['Pending Stock'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query();

        if ($this->selectedUser === 'all-salespersons') {
            $query->whereIn('status', ['New', 'Approved', 'Pending Migration', 'Pending Stock']);
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            $query->whereIn('status', ['New', 'Approved', 'Pending Migration', 'Pending Stock']);
            $selectedUser = $this->selectedUser;
            $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                $leadQuery->where('salesperson', $selectedUser);
            });
        } else {
            $query->whereIn('status', ['New', 'Approved', 'Pending Migration', 'Pending Stock']);
        }

        return $query->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Completed Migration' => 'Completed Migration',
                        'Pending Payment' => 'Pending Payment',
                        'Completed: Internal Installation' => 'Completed: Internal Installation',
                        'Completed: External Installation' => 'Completed: External Installation',
                        'Completed: Courier' => 'Completed: Courier',
                        'Completed: Self Pick Up' => 'Completed: Self Pick Up',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15) // Exclude Testing Account
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
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandoverV2 $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        if ($record->handover_pdf) {
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        return '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('6xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandoverV2 $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandoverV2 $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    }),

                TextColumn::make('implementer')
                    ->label('Implementer'),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 30, '...'));
                        $encryptedId = Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('invoice_type')
                    ->label('Invoice Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                        default => ucfirst($state ?? 'Unknown')
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Pending Stock' => new HtmlString('<span style="color: orange;">Pending Stock</span>'),
                        'Pending Migration' => new HtmlString('<span style="color: purple;">Pending Migration</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),

                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading('Hardware Handover Details')
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HardwareHandoverV2 $record): View {
                            return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),
                    Action::make('create_invoice')
                        ->label('Create Invoice')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->modalHeading('Create Invoice for Hardware Handover')
                        ->modalWidth('3xl')
                        ->form([
                            Repeater::make('invoices')
                                ->label('Invoice Details')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('invoice_no')
                                                ->label('Invoice Number')
                                                ->required()
                                                ->placeholder('Enter invoice number (e.g., EPIN2509-0286)')
                                                ->maxLength(255)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    if ($state) {
                                                        // Check if invoice exists in invoices table
                                                        $invoiceExists = \App\Models\Invoice::where('invoice_no', $state)->exists();

                                                        if (!$invoiceExists) {
                                                            $set('invoice_validation_error', 'Invoice number not found in system');
                                                        } else {
                                                            $set('invoice_validation_error', null);

                                                            // Get payment status
                                                            $paymentStatus = $this->getPaymentStatusForInvoice($state);
                                                            $set('payment_status_display', $paymentStatus);
                                                        }
                                                    }
                                                })
                                                ->helperText(function (Get $get) {
                                                    $error = $get('invoice_validation_error');
                                                    $status = $get('payment_status_display');

                                                    if ($error) {
                                                        return $error;
                                                    }

                                                    if ($status) {
                                                        return "Payment Status: {$status}";
                                                    }

                                                    return 'Invoice will be validated against system records';
                                                })
                                                ->extraAttributes(function (Get $get) {
                                                    $error = $get('invoice_validation_error');
                                                    return $error ? ['style' => 'border-color: #ef4444;'] : [];
                                                }),

                                            FileUpload::make('invoice_file')
                                                ->label('Invoice PDF')
                                                ->directory('hardware-handover-invoices')
                                                ->acceptedFileTypes(['application/pdf'])
                                                ->maxSize(10240)
                                                ->required()
                                        ]),

                                    // Hidden fields to store validation data
                                    TextInput::make('invoice_validation_error')
                                        ->hidden()
                                        ->dehydrated(false),

                                    TextInput::make('payment_status_display')
                                        ->hidden()
                                        ->dehydrated(false),
                                ])
                                ->addActionLabel('Add Another Invoice')
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(5)
                                ->collapsible(),
                        ])
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            // Validate all invoices exist
                            foreach ($data['invoices'] as $invoice) {
                                $invoiceExists = \App\Models\Invoice::where('invoice_no', $invoice['invoice_no'])->exists();
                                if (!$invoiceExists) {
                                    Notification::make()
                                        ->title('Validation Error')
                                        ->body("Invoice {$invoice['invoice_no']} not found in system")
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }

                            // Store invoice data with proper file paths
                            $invoiceData = [];
                            foreach ($data['invoices'] as $invoice) {
                                $invoiceData[] = [
                                    'invoice_no' => $invoice['invoice_no'],
                                    'invoice_file' => $invoice['invoice_file'], // This should be the relative path from storage
                                    'payment_status' => $this->getPaymentStatusForInvoice($invoice['invoice_no'])
                                ];
                            }

                            // Update hardware handover with invoice data
                            $record->update([
                                'invoice_data' => json_encode($invoiceData),
                            ]);

                            // Route based on invoice type (rest of your code remains the same)
                            if ($record->invoice_type === 'single') {
                                $record->update([
                                    'status' => 'Pending Migration',
                                    'migration_pending_at' => now(),
                                ]);

                                $statusMessage = 'Hardware Handover moved to Pending Migration';
                                $bodyMessage = 'Single invoice type automatically routed to migration.';
                            } elseif ($record->invoice_type === 'combined') {
                                $record->update([
                                    'status' => 'Pending Payment',
                                    'payment_pending_at' => now(),
                                ]);

                                $statusMessage = 'Hardware Handover moved to Pending Payment';
                                $bodyMessage = 'Combined invoice type routed to payment processing.';
                            } else {
                                $statusMessage = 'Invoices created successfully';
                                $bodyMessage = 'Hardware handover updated with invoice information.';
                            }

                            // Send email to salesperson
                            $this->sendHardwareHandoverEmail($record, $invoiceData);

                            Notification::make()
                                ->title($statusMessage)
                                ->body($bodyMessage)
                                ->success()
                                ->send();
                        })
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            $record->status === 'Pending Stock' && auth()->user()->role_id !== 2
                        )
                ])->button()
            ]);
    }

    protected function getPaymentStatusForInvoice(string $invoiceNo): string
    {
        // Get the total invoice amount for this invoice number
        $totalInvoiceAmount = \App\Models\Invoice::where('invoice_no', $invoiceNo)->sum('invoice_amount');

        // Look for this invoice in debtor_agings table
        $debtorAging = DB::table('debtor_agings')
            ->where('invoice_number', $invoiceNo)
            ->first();

        // If no matching record in debtor_agings or outstanding is 0
        if (!$debtorAging || (float)$debtorAging->outstanding === 0.0) {
            return 'Full Payment';
        }

        // If outstanding equals total invoice amount
        if ((float)$debtorAging->outstanding === (float)$totalInvoiceAmount) {
            return 'UnPaid';
        }

        // If outstanding is less than invoice amount but greater than 0
        if ((float)$debtorAging->outstanding < (float)$totalInvoiceAmount && (float)$debtorAging->outstanding > 0) {
            return 'Partial Payment';
        }

        // Fallback (shouldn't normally reach here)
        return 'UnPaid';
    }

    protected function sendHardwareHandoverEmail(HardwareHandoverV2 $record, array $invoiceData): void
    {
        try {
            // Get salesperson email from lead
            $salespersonEmail = $record->lead->getSalespersonEmail();
            $salespersonName = $record->lead->getSalespersonUser()?->name ?? 'Unknown';

            if (!$salespersonEmail) {
                Log::warning("No salesperson email found for hardware handover {$record->id}");
                return;
            }

            // Generate handover ID
            $handoverId = 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

            // Generate handover form URL (you may need to adjust this URL)
            $handoverFormUrl = url("admin/hardware-handover/{$record->id}");

            // Get company name
            $companyName = $record->lead->companyDetail->company_name ?? 'N/A';

            // Create email subject
            $subject = "HARDWARE HANDOVER | {$handoverId}";

            // Prepare data for the email template
            $emailData = [
                'record' => $record,
                'salespersonName' => $salespersonName,
                'handoverId' => $handoverId,
                'handoverFormUrl' => $handoverFormUrl,
                'companyName' => $companyName,
                'invoiceData' => $invoiceData, // This now includes the full file paths
            ];

            // Send email using the Blade template
            Mail::send('emails.hardware-handover-v2-notification', $emailData, function ($message) use ($salespersonEmail, $subject) {
                $message->to($salespersonEmail)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Hardware handover email sent to {$salespersonEmail} for handover {$record->id}");

        } catch (\Exception $e) {
            Log::error("Failed to send hardware handover email: " . $e->getMessage());

            Notification::make()
                ->title('Email Notification Failed')
                ->body('Invoice created successfully, but email notification failed to send.')
                ->warning()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-pending-stock-table');
    }
}
