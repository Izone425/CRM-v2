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
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\On;

class HardwareV2PendingPaymentTable extends Component implements HasForms, HasTable
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
            ->whereIn('status', ['Pending Payment'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Pending Payment'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);

        return $query->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5,'all'])
            // ->headerActions([
            //     Action::make('processFullPayment')
            //         ->label('Process Data')
            //         ->icon('heroicon-o-credit-card')
            //         ->color('success')
            //         ->visible(fn () => auth()->user()->role_id !== 2) // Hide for salesperson role
            //         ->action(function () {
            //             try {
            //                 // Run the artisan command
            //                 Artisan::call('handovers:process-full-payment-hardware-handover');
            //                 $output = Artisan::output();

            //                 // Refresh the table
            //                 $this->resetTable();
            //                 $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

            //                 // Show success notification
            //                 Notification::make()
            //                     ->title('Full Payment Processing Completed')
            //                     ->body('Hardware handovers with full payment have been processed successfully.')
            //                     ->success()
            //                     ->duration(5000)
            //                     ->send();

            //             } catch (\Exception $e) {
            //                 // Show error notification
            //                 Notification::make()
            //                     ->title('Processing Failed')
            //                     ->body('An error occurred while processing full payments: ' . $e->getMessage())
            //                     ->danger()
            //                     ->duration(10000)
            //                     ->send();
            //             }
            //         })
            //         ->requiresConfirmation()
            //         ->modalHeading('Process Full Payment Hardware Handovers')
            //         ->modalDescription('This will process all hardware handovers with full payment status and update their installation status. Are you sure you want to continue?')
            //         ->modalSubmitActionLabel('Process Now')
            // ])
            ->filters([
                SelectFilter::make('invoice_type')
                    ->label('Filter by Invoice Type')
                    ->options([
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                    ])
                    ->placeholder('All Invoice Types')
                    ->multiple(),

                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Rejected' => 'Rejected',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Pending Payment' => 'Pending Payment',
                        'Pending: Courier' => 'Pending: Courier',
                        'Completed: Courier' => 'Completed: Courier',
                        'Pending Admin: Self Pick-Up' => 'Pending Admin: Self Pick-Up',
                        'Pending Customer: Self Pick-Up' => 'Pending Customer: Self Pick-Up',
                        'Completed: Self Pick-Up' => 'Completed: Self Pick-Up',
                        'Pending: External Installation' => 'Pending: External Installation',
                        'Completed: External Installation' => 'Completed: External Installation',
                        'Pending: Internal Installation' => 'Pending: Internal Installation',
                        'Completed: Internal Installation' => 'Completed: Internal Installation',
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

                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
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
                        return User::find($salespersonId)?->name ?? $lead->lead_owner;
                    }),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 30, '...'));
                        $encryptedId = Encryptor::encrypt($record->lead->id);

                        // ✅ Check for subsidiary company names from proforma invoices
                        $subsidiaryNames = [];

                        if (!empty($record->proforma_invoice_product)) {
                            $piProducts = is_array($record->proforma_invoice_product)
                                ? $record->proforma_invoice_product
                                : json_decode($record->proforma_invoice_product, true);

                            if (is_array($piProducts)) {
                                foreach ($piProducts as $piId) {
                                    $quotation = \App\Models\Quotation::find($piId);
                                    if ($quotation && $quotation->subsidiary_id) {
                                        $subsidiary = $quotation->subsidiary;
                                        if ($subsidiary && $subsidiary->company_name) {
                                            $subsidiaryNames[] = strtoupper(Str::limit($subsidiary->company_name, 25, '...'));
                                        }
                                    }
                                }
                            }
                        }

                        // Build the main company link
                        $html = '<div>';

                        // ✅ Add subsidiary names at the top with different styling
                        if (!empty($subsidiaryNames)) {
                            $uniqueSubsidiaryNames = array_unique($subsidiaryNames);
                            foreach ($uniqueSubsidiaryNames as $subsidiaryName) {
                                $html .= '<div style="font-size: 10px; color: #e67e22; font-weight: bold; margin-bottom: 3px; background: #fef9e7; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-right: 4px;">
                                    ' . e($subsidiaryName) . '
                                </div><br>';
                            }
                        }

                        // Main company name
                        $html .= '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    style="color:#338cf0; text-decoration: none;">
                                    ' . $shortened . '
                                </a>';

                        $html .= '</div>';

                        return $html;
                    })
                    ->html(),

                TextColumn::make('installation_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'external_installation' => 'External',
                        'internal_installation' => 'Internal',
                        'self_pick_up' => 'Pick-Up',
                        'courier' => 'Courier',
                        default => ucfirst($state ?? 'Unknown')
                    }),

                TextColumn::make('invoice_count')
                    ->label('Invoices')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function (HardwareHandoverV2 $record) {
                        $invoiceData = $record->invoice_data
                            ? (is_string($record->invoice_data)
                                ? json_decode($record->invoice_data, true)
                                : $record->invoice_data)
                            : [];

                        if (!is_array($invoiceData) || count($invoiceData) === 0) {
                            return '-';
                        }

                        $epinCount = 0;
                        $ehinCount = 0;

                        foreach ($invoiceData as $invoice) {
                            $invoiceNo = $invoice['invoice_no'] ?? '';
                            if (stripos($invoiceNo, 'EPIN') !== false) {
                                $epinCount++;
                            } elseif (stripos($invoiceNo, 'EHIN') !== false) {
                                $ehinCount++;
                            }
                        }

                        $parts = [];
                        if ($epinCount > 0) {
                            $parts[] = "EPIN: {$epinCount}";
                        }
                        if ($ehinCount > 0) {
                            $parts[] = "EHIN: {$ehinCount}";
                        }

                        return !empty($parts) ? implode(' | ', $parts) : '-';
                    })
                    ->html()
                    ->action(
                        Action::make('viewInvoiceDetails')
                            ->modalHeading('Invoice Details')
                            ->modalWidth('2xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandoverV2 $record): View {
                                $invoiceData = $record->invoice_data
                                    ? (is_string($record->invoice_data)
                                        ? json_decode($record->invoice_data, true)
                                        : $record->invoice_data)
                                    : [];

                                return view('components.hardware-invoice-details', [
                                    'invoices' => $invoiceData,
                                    'record' => $record
                                ]);
                            })
                    ),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Pending Payment' => new HtmlString('<span style="color: red; font-weight: bold;">Pending Payment</span>'),
                        default => new HtmlString('<span style="font-weight: bold;">' . ucfirst($state) . '</span>'),
                    }),

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
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
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            $record->status === 'Pending Payment' &&
                            is_null($record->invoice_data)
                        )
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
                                                ->extraAlpineAttributes([
                                                    'x-on:input' => '
                                                        const start = $el.selectionStart;
                                                        const end = $el.selectionEnd;
                                                        const value = $el.value;
                                                        $el.value = value.toUpperCase();
                                                        $el.setSelectionRange(start, end);
                                                    '
                                                ])
                                                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                                ->rules([
                                                    'required',
                                                    function () {
                                                        return [
                                                            'invoice_exists' => function (string $attribute, $value, \Closure $fail) {
                                                                if (!$value) return;

                                                                $upperValue = strtoupper($value);
                                                                $invoiceRecord = \App\Models\Invoice::where('invoice_no', $upperValue)->first();
                                                                if (!$invoiceRecord) {
                                                                    $fail('Invoice number not found in system.');
                                                                }
                                                            },
                                                            'no_duplicates_in_form' => function (string $attribute, $value, \Closure $fail) {
                                                                if (!$value) return;

                                                                $upperValue = strtoupper($value);
                                                                $allInvoices = request()->input('invoices', []);
                                                                $duplicateCount = 0;

                                                                foreach ($allInvoices as $invoice) {
                                                                    if (isset($invoice['invoice_no']) &&
                                                                        strtoupper($invoice['invoice_no']) === $upperValue) {
                                                                        $duplicateCount++;
                                                                    }
                                                                }

                                                                if ($duplicateCount > 1) {
                                                                    $fail('This invoice number is already used in another entry above.');
                                                                }
                                                            },
                                                            'no_duplicates_in_system' => function (string $attribute, $value, \Closure $fail) {
                                                                if (!$value) return;

                                                                $upperValue = strtoupper($value);

                                                                // Get current record ID
                                                                $component = app('livewire')->current();
                                                                $currentRecord = null;

                                                                if (method_exists($component, 'getMountedTableActionRecord')) {
                                                                    $currentRecord = $component->getMountedTableActionRecord();
                                                                }

                                                                if (!$currentRecord) return;

                                                                // Check if invoice exists in other hardware handovers
                                                                $existingHandover = \App\Models\HardwareHandoverV2::where('id', '!=', $currentRecord->id)
                                                                    ->whereNotNull('invoice_data')
                                                                    ->get()
                                                                    ->filter(function ($handover) use ($upperValue) {
                                                                        $invoiceData = is_string($handover->invoice_data)
                                                                            ? json_decode($handover->invoice_data, true)
                                                                            : $handover->invoice_data;

                                                                        if (!is_array($invoiceData)) return false;

                                                                        foreach ($invoiceData as $existingInvoice) {
                                                                            if (isset($existingInvoice['invoice_no']) &&
                                                                                strtoupper($existingInvoice['invoice_no']) === $upperValue) {
                                                                                return true;
                                                                            }
                                                                        }
                                                                        return false;
                                                                    })
                                                                    ->first();

                                                                if ($existingHandover) {
                                                                    $existingHandoverId = $existingHandover->formatted_handover_id;
                                                                    $fail("Invoice number already used in Hardware Handover {$existingHandoverId}.");
                                                                }
                                                            },
                                                            'salesperson_match' => function (string $attribute, $value, \Closure $fail) {
                                                                if (!$value) return;

                                                                $upperValue = strtoupper($value);
                                                                $invoiceRecord = \App\Models\Invoice::where('invoice_no', $upperValue)->first();

                                                                if (!$invoiceRecord) return; // This will be caught by invoice_exists rule

                                                                $component = app('livewire')->current();
                                                                $currentRecord = null;

                                                                if (method_exists($component, 'getMountedTableActionRecord')) {
                                                                    $currentRecord = $component->getMountedTableActionRecord();
                                                                }

                                                                if (!$currentRecord) return;

                                                                $invoiceSalesperson = $invoiceRecord->salesperson ?? null;

                                                                if ($invoiceSalesperson !== null) {
                                                                    $handoverSalespersonId = $currentRecord->lead->salesperson ?? null;
                                                                    $handoverSalesperson = \App\Models\User::find($handoverSalespersonId)?->name ?? null;

                                                                    if ($handoverSalesperson &&
                                                                        stripos($handoverSalesperson, $invoiceSalesperson) === false &&
                                                                        stripos($invoiceSalesperson, $handoverSalesperson) === false) {
                                                                        $fail("Salesperson mismatch: Handover belongs to {$handoverSalesperson}, but invoice belongs to {$invoiceSalesperson}.");
                                                                    }
                                                                }
                                                            }
                                                        ];
                                                    }
                                                ])
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    if ($state) {
                                                        $paymentStatus = $this->getPaymentStatusForInvoice($state);
                                                        $set('payment_status_display', $paymentStatus);
                                                    } else {
                                                        $set('payment_status_display', null);
                                                    }
                                                })
                                                ->helperText(function (Get $get) {
                                                    $status = $get('payment_status_display');
                                                    if ($status) {
                                                        return "Payment Status: {$status}";
                                                    }
                                                    return 'Invoice will be validated against system records';
                                                }),

                                            // Keep only the payment status display hidden field
                                            TextInput::make('payment_status_display')
                                                ->hidden()
                                                ->dehydrated(false),

                                            FileUpload::make('invoice_file')
                                                ->label('Invoice PDF')
                                                ->directory('hardware-handover-invoices')
                                                ->acceptedFileTypes(['application/pdf'])
                                                ->maxSize(10240)
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
                                ->reorderable(false)
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(10),
                        ])
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            // First check for duplicates within the form data
                            $invoiceNumbers = array_map(fn($invoice) => strtoupper($invoice['invoice_no']), $data['invoices']);
                            if (count($invoiceNumbers) !== count(array_unique($invoiceNumbers))) {
                                Notification::make()
                                    ->title('Duplicate Invoice Numbers')
                                    ->body('You cannot enter the same invoice number multiple times.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Check for duplicates in existing hardware handovers
                            foreach ($data['invoices'] as $invoice) {
                                $invoiceNo = strtoupper($invoice['invoice_no']);

                                // Check if this invoice number exists in other hardware handovers
                                $existingHandover = HardwareHandoverV2::where('id', '!=', $record->id)
                                    ->whereNotNull('invoice_data')
                                    ->get()
                                    ->filter(function ($handover) use ($invoiceNo) {
                                        $invoiceData = is_string($handover->invoice_data)
                                            ? json_decode($handover->invoice_data, true)
                                            : $handover->invoice_data;

                                        if (!is_array($invoiceData)) return false;

                                        foreach ($invoiceData as $existingInvoice) {
                                            if (isset($existingInvoice['invoice_no']) &&
                                                strtoupper($existingInvoice['invoice_no']) === $invoiceNo) {
                                                return true;
                                            }
                                        }
                                        return false;
                                    })
                                    ->first();

                                if ($existingHandover) {
                                    $existingHandoverId = $existingHandover->formatted_handover_id;
                                    Notification::make()
                                        ->title('Duplicate Invoice Number')
                                        ->body("Invoice {$invoiceNo} is already used in Hardware Handover {$existingHandoverId}")
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Check if invoice exists in system
                                $invoiceRecord = \App\Models\Invoice::where('invoice_no', $invoiceNo)->first();

                                if (!$invoiceRecord) {
                                    Notification::make()
                                        ->title('Validation Error')
                                        ->body("Invoice {$invoiceNo} not found in system")
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Check salesperson match - compare names (skip if invoice salesperson is null)
                                $invoiceSalesperson = $invoiceRecord->salesperson ?? null;

                                if ($invoiceSalesperson !== null) {
                                    $handoverSalespersonId = $record->lead->salesperson ?? null;
                                    $handoverSalesperson = User::find($handoverSalespersonId)?->name ?? null;

                                    if (stripos($handoverSalesperson, $invoiceSalesperson) === false &&
                                        stripos($invoiceSalesperson, $handoverSalesperson) === false) {
                                        Notification::make()
                                            ->title('Salesperson Mismatch')
                                            ->body("Invoice {$invoiceNo} belongs to {$invoiceSalesperson}, but this handover belongs to {$handoverSalesperson}")
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                }
                            }

                            $invoiceData = [];
                            foreach ($data['invoices'] as $invoice) {
                                $invoiceData[] = [
                                    'invoice_no' => strtoupper($invoice['invoice_no']),
                                    'invoice_file' => $invoice['invoice_file'],
                                    'payment_status' => $this->getPaymentStatusForInvoice($invoice['invoice_no'])
                                ];
                            }

                            // Update hardware handover with invoice data
                            $record->update([
                                'invoice_data' => json_encode($invoiceData),
                            ]);

                            // Route based on invoice type
                            if ($record->invoice_type === 'single') {
                                $record->update([
                                    'status' => 'Pending Payment',
                                    'migration_pending_at' => now(),
                                ]);

                                $statusMessage = 'Hardware Handover moved to Pending Migration';
                                $bodyMessage = 'Single invoice type automatically routed to migration.';
                            } elseif ($record->invoice_type === 'combined') {
                                $record->update([
                                    'status' => 'Pending Migration',
                                    'payment_pending_at' => now(),
                                ]);

                                $statusMessage = 'Hardware Handover moved to Pending Payment';
                                $bodyMessage = 'Combined invoice type routed to payment processing.';
                            } else {
                                $statusMessage = 'Invoices created successfully';
                                $bodyMessage = 'Hardware handover updated with invoice information.';
                            }

                            // Send email to salesperson
                            // $this->sendHardwareHandoverEmail($record, $invoiceData);

                            Notification::make()
                                ->title($statusMessage)
                                ->body($bodyMessage)
                                ->success()
                                ->send();
                        }),

                    Action::make('bypass_payment')
                        ->label('Bypass Payment')
                        ->icon('heroicon-o-forward')
                        ->color('warning')
                        ->visible(fn(): bool => in_array(auth()->id(), [1, 4, 5, 14]))
                        ->requiresConfirmation()
                        ->modalHeading(function (HardwareHandoverV2 $record) {
                            // Get company name from the lead relationship
                            $companyName = 'Unknown Company';

                            if ($record->lead && $record->lead->companyDetail && $record->lead->companyDetail->company_name) {
                                $companyName = $record->lead->companyDetail->company_name;
                            }

                            return 'Bypass Payment - ' . $companyName;
                        })
                        ->modalDescription(fn (HardwareHandoverV2 $record): string =>
                            "Are you sure you want to bypass payment for this hardware handover? " .
                            "This will move it directly to the next status based on installation type: " .
                            match(strtolower($record->installation_type ?? '')) {
                                'courier' => 'Pending: Courier',
                                'self_pick_up' => 'Pending Admin: Self Pick-Up',
                                'external_installation' => 'Pending: External Installation',
                                'internal_installation' => 'Pending: Internal Installation',
                                default => 'Unknown Status'
                            }
                        )
                        ->modalSubmitActionLabel('Bypass Payment')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->action(function (HardwareHandoverV2 $record): void {
                            try {
                                // Get new status based on installation type
                                $newStatus = match(strtolower($record->installation_type ?? '')) {
                                    'courier' => 'Pending: Courier',
                                    'self_pick_up' => 'Pending Admin: Self Pick-Up',
                                    'external_installation' => 'Pending: External Installation',
                                    'internal_installation' => 'Pending: Internal Installation',
                                    default => null
                                };

                                if (!$newStatus) {
                                    Notification::make()
                                        ->title('Invalid Installation Type')
                                        ->body('Cannot determine next status due to invalid installation type: ' . ($record->installation_type ?? 'Unknown'))
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Update the record
                                $record->update([
                                    'status' => $newStatus,
                                    'updated_at' => now(),
                                ]);

                                // Log the bypass action
                                info(
                                    "Payment bypassed for Hardware Handover #{$record->id} by user " . auth()->user()->name .
                                    " (ID: " . auth()->id() . "). Status changed from 'Pending Payment' to '{$newStatus}'"
                                );

                                // Show success notification
                                Notification::make()
                                    ->title('Payment Bypassed Successfully')
                                    ->body("Hardware handover moved from 'Pending Payment' to '{$newStatus}'")
                                    ->success()
                                    ->duration(5000)
                                    ->send();

                                // Refresh the table
                                $this->resetTable();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Bypass Payment Failed')
                                    ->body('An error occurred while bypassing payment: ' . $e->getMessage())
                                    ->danger()
                                    ->duration(10000)
                                    ->send();

                                \Illuminate\Support\Facades\Log::error(
                                    "Failed to bypass payment for Hardware Handover #{$record->id}: " . $e->getMessage()
                                );
                            }
                        }),
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

        if ($debtorAging && (float)$debtorAging->outstanding === 0.0) {
            $status = 'Full Payment';
        } elseif ($debtorAging && (float)$debtorAging->outstanding === (float)$totalInvoiceAmount) {
            $status = 'UnPaid';
        } elseif ($debtorAging && (float)$debtorAging->outstanding < (float)$totalInvoiceAmount && (float)$debtorAging->outstanding > 0) {
            $status = 'Partial Payment';
        } else {
            $status = 'UnPaid';
        }

        return $status;
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-pending-payment-table');
    }
}
