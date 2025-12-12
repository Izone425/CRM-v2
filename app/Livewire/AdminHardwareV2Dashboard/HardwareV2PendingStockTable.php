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
use Illuminate\Database\Eloquent\Builder;

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
            // ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Pending Stock'])
            ->where('sales_order_status', 'packing')
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            // ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
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
            ->defaultPaginationPageOption('all')
            ->paginated([5, 'all'])
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

                SelectFilter::make('sales_order_status')
                    ->label('Filter by SO Status')
                    ->options(function () {
                        // Get unique SO statuses from the database
                        return HardwareHandoverV2::whereNotNull('sales_order_status')
                            ->where('sales_order_status', '!=', '')
                            ->distinct()
                            ->pluck('sales_order_status')
                            ->mapWithKeys(function ($status) {
                                $formattedStatus = ucfirst(strtolower($status));
                                return [$status => $formattedStatus];
                            })
                            ->toArray();
                    })
                    ->placeholder('All SO Statuses')
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereIn('sales_order_status', $data['values']);
                    }),

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

                SelectFilter::make('installation_type')
                    ->label('Filter by Installation Type')
                    ->options([
                        'external_installation' => 'External Installation',
                        'internal_installation' => 'Internal Installation',
                        'self_pick_up' => 'Pick-Up',
                        'courier' => 'Courier',
                    ])
                    ->placeholder('All Installation Types')
                    ->multiple(),
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
                        'external_installation' => 'External Installation',
                        'internal_installation' => 'Internal Installation',
                        'self_pick_up' => 'Pick-Up',
                        'courier' => 'Courier',
                        default => ucfirst($state ?? 'Unknown')
                    }),

                // TextColumn::make('status')
                //     ->label('Status')
                //     ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                //         'Pending Stock' => new HtmlString('<span style="color: black;">Pending Stock</span>'),
                //         default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                //     }),

                TextColumn::make('sales_order_status')
                    ->label('SO Status')
                    ->formatStateUsing(function ($state, HardwareHandoverV2 $record) {
                        if (!$record->sales_order_number) {
                            return '-';
                        }

                        $status = $state ?? 'Unknown';

                        // Check if we have multiple statuses (comma-separated)
                        if (strpos($status, ',') !== false) {
                            // Handle multiple statuses
                            $statuses = array_map('trim', explode(',', $status));
                            $salesOrderNumbers = array_map('trim', explode(',', $record->sales_order_number));

                            $statusElements = [];

                            foreach ($statuses as $index => $individualStatus) {
                                $formattedStatus = ucfirst(strtolower($individualStatus));

                                // Check if this individual status is "packing" (case insensitive)
                                $isPackingStatus = strtolower(trim($individualStatus)) === 'packing';
                                $statusColor = $isPackingStatus ? 'color: red; font-weight: bold;' : 'color: black;';

                                // Get corresponding SO number if available
                                $soNumber = isset($salesOrderNumbers[$index]) ? $salesOrderNumbers[$index] : '';

                                $statusElements[] = "
                                    <div style='margin-bottom: 4px;'>
                                        <div style='{$statusColor}'>{$formattedStatus}</div>
                                        " . ($soNumber ? "<div class='text-xs text-gray-500'>SO: {$soNumber}</div>" : "") . "
                                    </div>
                                ";
                            }

                            return new HtmlString("
                                <div class='text-sm'>
                                    " . implode('', $statusElements) . "
                                </div>
                            ");

                        } else {
                            // Handle single status (existing logic)
                            $formattedStatus = ucfirst(strtolower($status));

                            // Check if status is "packing" (case insensitive)
                            $isPackingStatus = strtolower($status) === 'packing';
                            $statusColor = $isPackingStatus ? 'color: red; font-weight: bold;' : 'color: black;';

                            return new HtmlString("
                                <div class='text-sm'>
                                    <div style='{$statusColor}'>{$formattedStatus}</div>
                                    <div class='text-xs text-gray-500'>SO: {$record->sales_order_number}</div>
                                </div>
                            ");
                        }
                    })
                    ->searchable(['sales_order_number', 'sales_order_status'])
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->getStateUsing(function (HardwareHandoverV2 $record) {
                        return $record->updated_at;
                    }),
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
                        ->modalHeading(function (HardwareHandoverV2 $record) {
                            // Get company name from the lead relationship
                            $companyName = 'Unknown Company';

                            if ($record->lead && $record->lead->companyDetail && $record->lead->companyDetail->company_name) {
                                $companyName = $record->lead->companyDetail->company_name;
                            }

                            return 'Create Invoice - ' . $companyName;
                        })
                        ->modalWidth('3xl')
                        ->form([
                            // ✅ Add AutoCount invoice checkbox at the top
                            Section::make('Invoice Creation Options')
                                ->schema([
                                    Checkbox::make('create_autocount_invoice')
                                        ->label('Create AutoCount HRDF Invoice')
                                        ->helperText('Generate AutoCount invoice from associated quotations')
                                        ->default(false)
                                        ->live()
                                        ->columnSpanFull(),
                                ]),

                            // ✅ Show preview when AutoCount is selected
                            Section::make('AutoCount Invoice Preview')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('autocount_preview')
                                        ->label('')
                                        ->content(function (callable $get, HardwareHandoverV2 $record) {
                                            if (!$get('create_autocount_invoice')) {
                                                return '';
                                            }

                                            try {
                                                // ✅ Get quotation IDs from proforma_invoice_hrdf (not proforma_invoice_product)
                                                $quotationIds = [];
                                                if ($record->proforma_invoice_hrdf) {
                                                    $quotationIds = is_string($record->proforma_invoice_hrdf)
                                                        ? json_decode($record->proforma_invoice_hrdf, true)
                                                        : $record->proforma_invoice_hrdf;
                                                }

                                                if (empty($quotationIds)) {
                                                    return 'No HRDF quotations found for AutoCount invoice generation.';
                                                }

                                                // Check if any quotations already have AutoCount invoices
                                                $alreadyProcessed = \App\Models\Quotation::whereIn('id', $quotationIds)
                                                    ->where('autocount_generated_pi', true)
                                                    ->pluck('pi_reference_no')
                                                    ->toArray();

                                                if (!empty($alreadyProcessed)) {
                                                    // ✅ Return as HtmlString for proper rendering
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="text-red-600">Warning: The following quotations already have AutoCount invoices: ' .
                                                        implode(', ', $alreadyProcessed) . '</div>'
                                                    );
                                                }

                                                // Generate preview
                                                $preview = $this->generateHardwareInvoicePreview($record, $quotationIds);

                                                if (empty($preview['invoices'])) {
                                                    return $preview['message'] ?? 'No items to display';
                                                }

                                                $html = '<div class="space-y-4">';
                                                $html .= '<div><strong>Debtor:</strong> ARM-P0062 - PEMBANGUNAN SUMBER MANUSIA BERHAD</div>';
                                                $html .= '<div><strong>Company:</strong> ' . ($record->lead->companyDetail->company_name ?? 'N/A') . '</div>';
                                                $html .= '<div><strong>Total Invoices:</strong> ' . $preview['total_invoices'] . '</div>';

                                                // Show each invoice separately
                                                foreach ($preview['invoices'] as $index => $invoice) {
                                                    $html .= '<div class="p-3 mt-4 border rounded bg-gray-50">';
                                                    $html .= '<div class="font-semibold text-blue-600">Invoice ' . ($index + 1) . '</div>';
                                                    $html .= '<div><strong>Document No:</strong> ' . $invoice['invoice_no'] . '</div>';
                                                    $html .= '<div class="mt-2">';
                                                    $html .= '<div class="mb-2 text-sm font-semibold">Items:</div>';

                                                    foreach ($invoice['items'] as $item) {
                                                        $html .= '<div class="flex justify-between py-1 text-sm">';
                                                        $html .= '<div class="flex items-center gap-2">';
                                                        $html .= '<span class="px-2 py-1 font-mono text-xs bg-gray-100 rounded">' . $item['code'] . '</span>';
                                                        $html .= '<span class="text-gray-600">× ' . number_format($item['quantity']) . '</span>';
                                                        $html .= '</div>';
                                                        $html .= '<span class="font-semibold">RM ' . number_format($item['amount'], 2) . '</span>';
                                                        $html .= '</div>';
                                                    }

                                                    $html .= '</div>';
                                                    $html .= '<div class="flex justify-between pt-2 mt-2 font-semibold border-t">';
                                                    $html .= '<span>Invoice Total:</span><span>RM ' . number_format($invoice['total'], 2) . '</span>';
                                                    $html .= '</div></div>';
                                                }

                                                // Show grand total if multiple invoices
                                                if ($preview['total_invoices'] > 1) {
                                                    $html .= '<div class="flex justify-between pt-2 mt-4 text-lg font-bold border-t-2 border-blue-500">';
                                                    $html .= '<span>Grand Total:</span><span>RM ' . number_format($preview['grand_total'], 2) . '</span>';
                                                    $html .= '</div>';
                                                }

                                                $html .= '</div>';

                                                return new \Illuminate\Support\HtmlString($html);
                                            } catch (\Exception $e) {
                                                Log::error('Error generating Hardware AutoCount preview: ' . $e->getMessage());
                                                return 'Error generating preview: ' . $e->getMessage();
                                            }
                                        })
                                ])
                                ->visible(fn (callable $get) => $get('create_autocount_invoice')),

                            // Repeater::make('invoices')
                            //     ->label('Invoice Details')
                            //     ->schema([
                            //         Grid::make(2)
                            //             ->schema([
                            //                 TextInput::make('invoice_no')
                            //                     ->label('Invoice Number')
                            //                     ->required()
                            //                     ->placeholder('Enter invoice number (e.g., EPIN2509-0286)')
                            //                     ->maxLength(255)
                            //                     ->live(onBlur: true)
                            //                     ->extraAlpineAttributes([
                            //                         'x-on:input' => '
                            //                             const start = $el.selectionStart;
                            //                             const end = $el.selectionEnd;
                            //                             const value = $el.value;
                            //                             $el.value = value.toUpperCase();
                            //                             $el.setSelectionRange(start, end);
                            //                         '
                            //                     ])
                            //                     ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            //                     ->rules([
                            //                         'required',
                            //                         function () {
                            //                             return [
                            //                                 'invoice_exists' => function (string $attribute, $value, \Closure $fail) {
                            //                                     if (!$value) return;

                            //                                     $upperValue = strtoupper($value);
                            //                                     $invoiceRecord = \App\Models\Invoice::where('invoice_no', $upperValue)->first();
                            //                                     if (!$invoiceRecord) {
                            //                                         $fail('Invoice number not found in system.');
                            //                                     }
                            //                                 },
                            //                                 'no_duplicates_in_form' => function (string $attribute, $value, \Closure $fail) {
                            //                                     if (!$value) return;

                            //                                     $upperValue = strtoupper($value);
                            //                                     $allInvoices = request()->input('invoices', []);
                            //                                     $duplicateCount = 0;

                            //                                     foreach ($allInvoices as $invoice) {
                            //                                         if (isset($invoice['invoice_no']) &&
                            //                                             strtoupper($invoice['invoice_no']) === $upperValue) {
                            //                                             $duplicateCount++;
                            //                                         }
                            //                                     }

                            //                                     if ($duplicateCount > 1) {
                            //                                         $fail('This invoice number is already used in another entry above.');
                            //                                     }
                            //                                 },
                            //                                 'no_duplicates_in_system' => function (string $attribute, $value, \Closure $fail) {
                            //                                     if (!$value) return;

                            //                                     $upperValue = strtoupper($value);

                            //                                     // Get current record ID
                            //                                     $component = app('livewire')->current();
                            //                                     $currentRecord = null;

                            //                                     if (method_exists($component, 'getMountedTableActionRecord')) {
                            //                                         $currentRecord = $component->getMountedTableActionRecord();
                            //                                     }

                            //                                     if (!$currentRecord) return;

                            //                                     // Check if invoice exists in other hardware handovers
                            //                                     $existingHandover = \App\Models\HardwareHandoverV2::where('id', '!=', $currentRecord->id)
                            //                                         ->whereNotNull('invoice_data')
                            //                                         ->get()
                            //                                         ->filter(function ($handover) use ($upperValue) {
                            //                                             $invoiceData = is_string($handover->invoice_data)
                            //                                                 ? json_decode($handover->invoice_data, true)
                            //                                                 : $handover->invoice_data;

                            //                                             if (!is_array($invoiceData)) return false;

                            //                                             foreach ($invoiceData as $existingInvoice) {
                            //                                                 if (isset($existingInvoice['invoice_no']) &&
                            //                                                     strtoupper($existingInvoice['invoice_no']) === $upperValue) {
                            //                                                     return true;
                            //                                                 }
                            //                                             }
                            //                                             return false;
                            //                                         })
                            //                                         ->first();

                            //                                     if ($existingHandover) {
                            //                                         $existingHandoverId = 'HW_250' . str_pad($existingHandover->id, 3, '0', STR_PAD_LEFT);
                            //                                         $fail("Invoice number already used in Hardware Handover {$existingHandoverId}.");
                            //                                     }
                            //                                 },
                            //                                 'salesperson_match' => function (string $attribute, $value, \Closure $fail) {
                            //                                     if (!$value) return;

                            //                                     $upperValue = strtoupper($value);
                            //                                     $invoiceRecord = \App\Models\Invoice::where('invoice_no', $upperValue)->first();

                            //                                     if (!$invoiceRecord) return; // This will be caught by invoice_exists rule

                            //                                     $component = app('livewire')->current();
                            //                                     $currentRecord = null;

                            //                                     if (method_exists($component, 'getMountedTableActionRecord')) {
                            //                                         $currentRecord = $component->getMountedTableActionRecord();
                            //                                     }

                            //                                     if (!$currentRecord) return;

                            //                                     $invoiceSalesperson = $invoiceRecord->salesperson ?? null;

                            //                                     if ($invoiceSalesperson !== null) {
                            //                                         $handoverSalespersonId = $currentRecord->lead->salesperson ?? null;
                            //                                         $handoverSalesperson = \App\Models\User::find($handoverSalespersonId)?->name ?? null;

                            //                                         if ($handoverSalesperson &&
                            //                                             stripos($handoverSalesperson, $invoiceSalesperson) === false &&
                            //                                             stripos($invoiceSalesperson, $handoverSalesperson) === false) {
                            //                                             $fail("Salesperson mismatch: Handover belongs to {$handoverSalesperson}, but invoice belongs to {$invoiceSalesperson}.");
                            //                                         }
                            //                                     }
                            //                                 }
                            //                             ];
                            //                         }
                            //                     ])
                            //                     ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            //                         if ($state) {
                            //                             $paymentStatus = $this->getPaymentStatusForInvoice($state);
                            //                             $set('payment_status_display', $paymentStatus);
                            //                         } else {
                            //                             $set('payment_status_display', null);
                            //                         }
                            //                     })
                            //                     ->helperText(function (Get $get) {
                            //                         $status = $get('payment_status_display');
                            //                         if ($status) {
                            //                             return "Payment Status: {$status}";
                            //                         }
                            //                         return 'Invoice will be validated against system records';
                            //                     }),

                            //                 // Keep only the payment status display hidden field
                            //                 TextInput::make('payment_status_display')
                            //                     ->hidden()
                            //                     ->dehydrated(false),

                            //                 FileUpload::make('invoice_file')
                            //                     ->label('Invoice PDF')
                            //                     ->directory('hardware-handover-invoices')
                            //                     ->acceptedFileTypes(['application/pdf'])
                            //                     ->maxSize(10240)
                            //                     ->required()
                            //             ]),

                            //         // Hidden fields to store validation data
                            //         TextInput::make('invoice_validation_error')
                            //             ->hidden()
                            //             ->dehydrated(false),

                            //         TextInput::make('payment_status_display')
                            //             ->hidden()
                            //             ->dehydrated(false),
                            //     ])
                            //     ->addActionLabel('Add Another Invoice')
                            //     ->reorderable(false)
                            //     ->defaultItems(1)
                            //     ->minItems(1)
                            //     ->maxItems(5),
                        ])
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            // ✅ Handle AutoCount invoice creation first
                            if ($data['create_autocount_invoice'] ?? false) {
                                try {
                                    $result = $this->createHardwareAutoCountInvoices($record);

                                    if ($result['success']) {
                                        Notification::make()
                                            ->title('AutoCount Invoices Created Successfully')
                                            ->body("Created {$result['total_invoices']} AutoCount invoice(s). Invoice Numbers: " .
                                                implode(', ', $result['invoice_numbers']))
                                            ->success()
                                            ->send();

                                        Log::info('Hardware AutoCount invoices created', [
                                            'handover_id' => $record->id,
                                            'invoice_numbers' => $result['invoice_numbers'],
                                            'total_invoices' => $result['total_invoices']
                                        ]);
                                    } else {
                                        Notification::make()
                                            ->title('Failed to Create AutoCount Invoices')
                                            ->body($result['error'] ?? 'Unknown error occurred')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Hardware AutoCount invoice creation failed: ' . $e->getMessage());
                                    Notification::make()
                                        ->title('AutoCount Invoice Error')
                                        ->body('An error occurred while creating AutoCount invoices: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }

                            // // First check for duplicates within the form data
                            // $invoiceNumbers = array_map(fn($invoice) => strtoupper($invoice['invoice_no']), $data['invoices']);
                            // if (count($invoiceNumbers) !== count(array_unique($invoiceNumbers))) {
                            //     Notification::make()
                            //         ->title('Duplicate Invoice Numbers')
                            //         ->body('You cannot enter the same invoice number multiple times.')
                            //         ->danger()
                            //         ->send();
                            //     return;
                            // }

                            // // Check for duplicates in existing hardware handovers
                            // foreach ($data['invoices'] as $invoice) {
                            //     $invoiceNo = strtoupper($invoice['invoice_no']);

                            //     // Check if this invoice number exists in other hardware handovers
                            //     $existingHandover = HardwareHandoverV2::where('id', '!=', $record->id)
                            //         ->whereNotNull('invoice_data')
                            //         ->get()
                            //         ->filter(function ($handover) use ($invoiceNo) {
                            //             $invoiceData = is_string($handover->invoice_data)
                            //                 ? json_decode($handover->invoice_data, true)
                            //                 : $handover->invoice_data;

                            //             if (!is_array($invoiceData)) return false;

                            //             foreach ($invoiceData as $existingInvoice) {
                            //                 if (isset($existingInvoice['invoice_no']) &&
                            //                     strtoupper($existingInvoice['invoice_no']) === $invoiceNo) {
                            //                     return true;
                            //                 }
                            //             }
                            //             return false;
                            //         })
                            //         ->first();

                            //     if ($existingHandover) {
                            //         $existingHandoverId = 'HW_250' . str_pad($existingHandover->id, 3, '0', STR_PAD_LEFT);
                            //         Notification::make()
                            //             ->title('Duplicate Invoice Number')
                            //             ->body("Invoice {$invoiceNo} is already used in Hardware Handover {$existingHandoverId}")
                            //             ->danger()
                            //             ->send();
                            //         return;
                            //     }

                            //     // Check if invoice exists in system
                            //     $invoiceRecord = \App\Models\Invoice::where('invoice_no', $invoiceNo)->first();

                            //     if (!$invoiceRecord) {
                            //         Notification::make()
                            //             ->title('Validation Error')
                            //             ->body("Invoice {$invoiceNo} not found in system")
                            //             ->danger()
                            //             ->send();
                            //         return;
                            //     }

                            //     // Check salesperson match - compare names (skip if invoice salesperson is null)
                            //     $invoiceSalesperson = $invoiceRecord->salesperson ?? null;

                            //     if ($invoiceSalesperson !== null) {
                            //         $handoverSalespersonId = $record->lead->salesperson ?? null;
                            //         $handoverSalesperson = User::find($handoverSalespersonId)?->name ?? null;

                            //         if (stripos($handoverSalesperson, $invoiceSalesperson) === false &&
                            //             stripos($invoiceSalesperson, $handoverSalesperson) === false) {
                            //             Notification::make()
                            //                 ->title('Salesperson Mismatch')
                            //                 ->body("Invoice {$invoiceNo} belongs to {$invoiceSalesperson}, but this handover belongs to {$handoverSalesperson}")
                            //                 ->danger()
                            //                 ->send();
                            //             return;
                            //         }
                            //     }
                            // }

                            // // Rest of your existing code...
                            // $invoiceData = [];
                            // foreach ($data['invoices'] as $invoice) {
                            //     $invoiceData[] = [
                            //         'invoice_no' => strtoupper($invoice['invoice_no']),
                            //         'invoice_file' => $invoice['invoice_file'],
                            //         'payment_status' => $this->getPaymentStatusForInvoice($invoice['invoice_no'])
                            //     ];
                            // }

                            // // Update hardware handover with invoice data
                            // $record->update([
                            //     'invoice_data' => json_encode($invoiceData),
                            // ]);

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
                        })
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            $record->status === 'Pending Stock' && auth()->user()->role_id !== 2
                    ),
                    Action::make('add_admin_remarks')
                        ->label('Add Admin Remarks')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('warning')
                        ->modalHeading(function (HardwareHandoverV2 $record) {
                            $companyName = 'Unknown Company';
                            if ($record->lead && $record->lead->companyDetail && $record->lead->companyDetail->company_name) {
                                $companyName = $record->lead->companyDetail->company_name;
                            }
                            return 'Add Admin Remarks - ' . $companyName;
                        })
                        ->modalWidth('2xl')
                        ->form([
                            Textarea::make('new_remark')
                                ->label(false)
                                ->placeholder('Enter your admin remark here...')
                                ->rows(4)
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
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            // Get current admin remarks (handle both array and JSON string)
                            $currentRemarks = $record->admin_remarks;

                            if (is_string($currentRemarks)) {
                                $currentRemarks = json_decode($currentRemarks, true) ?: [];
                            } elseif (!is_array($currentRemarks)) {
                                $currentRemarks = [];
                            }

                            // Add new remark with timestamp and user info
                            $newRemark = [
                                'remark' => $data['new_remark'],
                                'created_by' => auth()->user()->name,
                                'created_at' => now()->format('Y-m-d H:i:s'),
                                'user_role' => auth()->user()->role->name ?? 'Unknown'
                            ];

                            // Prepend new remark to existing ones (newest first)
                            array_unshift($currentRemarks, $newRemark);

                            // Update the record
                            $record->update([
                                'admin_remarks' => $currentRemarks
                            ]);

                            Notification::make()
                                ->title('Admin Remark Added')
                                ->body('Your admin remark has been successfully added.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (HardwareHandoverV2 $record): bool => auth()->user()->role_id !== 2),
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

            // Get updated by user name from created_by field
            $updatedByUser = User::find($record->created_by);
            $updatedByName = $updatedByUser ? $updatedByUser->name : 'Unknown User';

            // Generate handover ID
            $handoverId = 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

            // Generate handover form URL (you may need to adjust this URL)
            $handoverFormUrl = url("admin/hardware-handover/{$record->id}");

            // Get company name
            $companyName = $record->lead->companyDetail->company_name ?? 'N/A';

            // Create email subject
            $subject = "HARDWARE HANDOVER | {$handoverId} | {$companyName}";

            // Prepare data for the email template
            $emailData = [
                'record' => $record,
                'salespersonName' => $salespersonName,
                'updatedByName' => $updatedByName,
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

    /**
     * Generate AutoCount invoice preview for hardware handover
     */
    protected function generateHardwareInvoicePreview(HardwareHandoverV2 $record, array $quotationIds): array
    {
        if (empty($quotationIds)) {
            return [
                'invoices' => [],
                'total_invoices' => 0,
                'grand_total' => 0,
                'message' => 'No quotation IDs provided'
            ];
        }

        try {
            // Generate invoice numbers for preview
            $invoiceNumbers = $this->generateMultipleHardwareInvoiceNumbers(count($quotationIds));
            $invoices = [];
            $grandTotal = 0;

            foreach ($quotationIds as $index => $quotationId) {
                $details = \App\Models\QuotationDetail::where('quotation_id', $quotationId)
                    ->with('product')
                    ->get();

                if ($details->isEmpty()) {
                    Log::warning("No quotation details found for quotation ID: {$quotationId}");
                    continue;
                }

                // Group items by product code and unit price
                $groupedItems = [];
                $invoiceTotal = 0;

                foreach ($details as $detail) {
                    $productCode = $detail->product->code ?? 'ITEM-' . $detail->product_id;
                    $unitPrice = (float) $detail->unit_price;
                    $amount = (float) $detail->total_before_tax;
                    $quantity = (float) $detail->quantity;

                    $key = $productCode . '|' . $unitPrice;

                    if (isset($groupedItems[$key])) {
                        $groupedItems[$key]['quantity'] += $quantity;
                        $groupedItems[$key]['amount'] += $amount;
                    } else {
                        $groupedItems[$key] = [
                            'code' => $productCode,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'amount' => $amount
                        ];
                    }

                    $invoiceTotal += $amount;
                }

                $items = array_values($groupedItems);

                if (!empty($items)) {
                    $invoices[] = [
                        'invoice_no' => $invoiceNumbers[$index],
                        'items' => $items,
                        'total' => $invoiceTotal,
                        'quotation_ids' => [$quotationId]
                    ];

                    $grandTotal += $invoiceTotal;
                }
            }

            return [
                'invoices' => $invoices,
                'total_invoices' => count($invoices),
                'grand_total' => $grandTotal
            ];

        } catch (\Exception $e) {
            Log::error('Error in generateHardwareInvoicePreview', [
                'handover_id' => $record->id,
                'quotation_ids' => $quotationIds,
                'error' => $e->getMessage()
            ]);

            return [
                'invoices' => [],
                'total_invoices' => 0,
                'grand_total' => 0,
                'message' => 'Error generating preview: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate multiple invoice numbers for hardware handover
     */
    protected function generateMultipleHardwareInvoiceNumbers(int $count): array
    {
        $year = date('y');
        $month = date('m');
        $yearMonth = $year . $month;

        // Get latest sequence from CRM HRDF invoices table
        $latestInvoice = \App\Models\CrmHrdfInvoice::where('invoice_no', 'LIKE', "EHIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $startSequence = 1;
        if ($latestInvoice) {
            preg_match("/EHIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $startSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1;
        }

        // Generate all invoice numbers sequentially
        $invoiceNumbers = [];
        for ($i = 0; $i < $count; $i++) {
            $sequence = str_pad($startSequence + $i, 4, '0', STR_PAD_LEFT);
            $invoiceNumbers[] = "EHIN{$yearMonth}-{$sequence}";
        }

        return $invoiceNumbers;
    }

    /**
     * Create AutoCount invoices for hardware handover
     */
    protected function createHardwareAutoCountInvoices(HardwareHandoverV2 $record): array
    {
        try {
            $result = [
                'success' => false,
                'invoice_numbers' => [],
                'total_invoices' => 0,
                'error' => null,
            ];

            // ✅ Get quotation IDs from proforma_invoice_hrdf (not proforma_invoice_product)
            $quotationIds = [];
            if ($record->proforma_invoice_hrdf) {
                $quotationIds = is_string($record->proforma_invoice_hrdf)
                    ? json_decode($record->proforma_invoice_hrdf, true)
                    : $record->proforma_invoice_hrdf;
            }

            if (empty($quotationIds)) {
                $result['error'] = 'No HRDF quotations found for invoice creation';
                return $result;
            }

            // ✅ Check if any quotations already have AutoCount invoices
            $alreadyProcessed = \App\Models\Quotation::whereIn('id', $quotationIds)
                ->where('autocount_generated_pi', true)
                ->pluck('pi_reference_no')
                ->toArray();

            if (!empty($alreadyProcessed)) {
                $result['error'] = 'The following quotations already have AutoCount invoices: ' . implode(', ', $alreadyProcessed);
                return $result;
            }

            // ✅ Pre-generate all invoice numbers
            $invoiceNumbers = $this->generateMultipleHardwareInvoiceNumbers(count($quotationIds));
            $createdInvoices = [];

            foreach ($quotationIds as $index => $quotationId) {
                $invoiceNo = $invoiceNumbers[$index];

                // Get quotation details
                $details = \App\Models\QuotationDetail::where('quotation_id', $quotationId)
                    ->with('product')
                    ->get();

                if ($details->isEmpty()) {
                    Log::warning("No quotation details found for quotation ID: {$quotationId}");
                    continue;
                }

                // Prepare invoice data for AutoCount API
                $invoiceData = [
                    'company' => 'TIMETEC Sandbox',
                    'customer_code' => 'ARM-P0062',
                    'document_no' => $invoiceNo,
                    'document_date' => now()->format('Y-m-d'),
                    'description' => 'Hardware HRDF Invoice - ' . ($record->lead->companyDetail->company_name ?? 'N/A'),
                    'salesperson' => $this->getHardwareAutoCountSalesperson($record),
                    'round_method' => 0,
                    'inclusive' => true,
                    'details' => $this->getHardwareInvoiceDetailsFromQuotation($quotationId),
                ];

                // Create invoice via AutoCount API
                $autoCountService = app(\App\Services\AutoCountInvoiceService::class);
                $invoiceResult = $autoCountService->createInvoice($invoiceData);

                if (!$invoiceResult['success']) {
                    $result['error'] = "Failed to create invoice {$invoiceNo}: " . $invoiceResult['error'];
                    return $result;
                }

                $createdInvoices[] = $invoiceNo;

                // ✅ Create CrmHrdfInvoice record
                $total = $details->sum('total_before_tax');
                $salesperson = $this->getHardwareSalespersonName($record);

                \App\Models\CrmHrdfInvoice::create([
                    'invoice_no' => $invoiceNo,
                    'invoice_date' => now()->toDateString(),
                    'company_name' => $record->lead->companyDetail->company_name ?? 'N/A',
                    'handover_type' => 'HW', // Hardware Handover
                    'salesperson' => $salesperson,
                    'handover_id' => $record->id,
                    'debtor_code' => 'ARM-P0062',
                    'total_amount' => $total,
                ]);

                // ✅ Mark quotation as processed
                \App\Models\Quotation::where('id', $quotationId)->update([
                    'autocount_generated_pi' => true
                ]);

                Log::info('Hardware HRDF Invoice record created', [
                    'invoice_no' => $invoiceNo,
                    'hardware_handover_id' => $record->id,
                    'quotation_id' => $quotationId,
                    'company_name' => $record->lead->companyDetail->company_name ?? 'N/A',
                    'total_amount' => $total,
                    'handover_type' => 'HW',
                    'quotation_pi_reference' => \App\Models\Quotation::find($quotationId)?->pi_reference_no ?? 'N/A'
                ]);
            }

            $result['success'] = true;
            $result['invoice_numbers'] = $createdInvoices;
            $result['total_invoices'] = count($createdInvoices);

            Log::info('Hardware AutoCount invoices creation completed', [
                'handover_id' => $record->id,
                'total_invoices_created' => count($createdInvoices),
                'invoice_numbers' => $createdInvoices,
                'processed_quotation_ids' => $quotationIds
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Hardware AutoCount invoice creation failed', [
                'handover_id' => $record->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get AutoCount salesperson for hardware handover
     */
    protected function getHardwareAutoCountSalesperson(HardwareHandoverV2 $record): string
    {
        $salespersonId = $record->lead->salesperson ?? null;
        $salesperson = \App\Models\User::find($salespersonId);

        if (!$salesperson) {
            Log::warning('No salesperson found for hardware handover', [
                'handover_id' => $record->id,
                'fallback_used' => 'ADMIN'
            ]);
            return 'ADMIN';
        }

        // ✅ Check for autocount_name field first
        if (!empty($salesperson->autocount_name)) {
            Log::info('Hardware AutoCount salesperson using autocount_name', [
                'salesperson_name' => $salesperson->name,
                'salesperson_id' => $salespersonId,
                'handover_id' => $record->id,
                'autocount_code' => $salesperson->autocount_name
            ]);
            return $salesperson->autocount_name;
        }

        // ✅ Fallback to ADMIN if no autocount_name
        Log::warning('No autocount_name found for salesperson, using ADMIN fallback', [
            'salesperson_name' => $salesperson->name,
            'salesperson_id' => $salespersonId,
            'handover_id' => $record->id,
            'fallback_used' => 'ADMIN'
        ]);

        return 'ADMIN';
    }

    /**
     * Get salesperson name for hardware handover
     */
    protected function getHardwareSalespersonName(HardwareHandoverV2 $record): string
    {
        $salespersonId = $record->lead->salesperson ?? null;
        $salesperson = \App\Models\User::find($salespersonId);
        return $salesperson?->name ?? 'Unknown Salesperson';
    }

    /**
     * Get invoice details from quotation for hardware handover
     */
    protected function getHardwareInvoiceDetailsFromQuotation(int $quotationId): array
    {
        $details = \App\Models\QuotationDetail::where('quotation_id', $quotationId)
            ->with('product')
            ->get();

        $invoiceDetails = [];

        foreach ($details as $detail) {
            $productCode = $detail->product->code ?? 'ITEM-' . $detail->product_id;

            $invoiceDetails[] = [
                'item_code' => $productCode,
                'description' => $detail->product->product_name ?? 'Product',
                'quantity' => (float) $detail->quantity,
                'unit_price' => (float) $detail->unit_price,
                'discount_percent' => 0,
                'tax_code' => 'TX-01',
            ];
        }

        return $invoiceDetails;
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-pending-stock-table');
    }
}
