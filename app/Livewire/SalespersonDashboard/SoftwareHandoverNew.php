<?php

namespace App\Livewire\SalespersonDashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateSoftwareHandoverPdfController;
use App\Models\CompanyDetail;
use App\Models\CrmHrdfInvoice;
use App\Models\ImplementerLogs;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\User;
use App\Services\AutoCountIntegrationService;
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
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class SoftwareHandoverNew extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

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

    #[On('refresh-softwarehandover-tables')]
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

    public function getNewSoftwareHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = SoftwareHandover::query();
        $query->where('hr_version', 1);

        // Salesperson filter logic
        if ($this->selectedUser === 'all-salespersons') {
            $query->whereIn('status', ['Rejected', 'Draft', 'New', 'Approved']);

            // Keep as is - show all salespersons' handovers
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            // Validate that the selected user exists and is a salesperson
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();
            $query->whereIn('status', ['Rejected', 'Draft', 'New', 'Approved']);

            if ($userExists) {
                $selectedUser = $this->selectedUser; // Create a local variable
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                // Invalid user ID or not a salesperson, fall back to default
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            if (auth()->user()->role_id === 2) {
                // Salespersons (role_id 2) can see Draft, New, Approved, and Completed
                $query->whereIn('status', ['Rejected', 'Draft', 'New', 'Approved']);

                // But only THEIR OWN records
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            } else {
                // Other users (admin, managers) can only see New, Approved, and Completed
                $query->whereIn('status', ['New', 'Approved']);
                // But they can see ALL records
            }
        }

        $query->orderByRaw("CASE
            when status = 'Rejected' THEN 0
            WHEN status = 'Draft' THEN 1
            WHEN status = 'New' THEN 2
            WHEN status = 'Approved' THEN 3
            WHEN status = 'Completed' THEN 4
            ELSE 5
        END")
            ->orderBy('created_at', 'desc');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewSoftwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
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

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // ✅ Use model method for consistent formatting
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
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>');
                        }

                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('hr_version')
                    ->label('HR Version')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Version ' . $state : 'N/A';
                    }),

                TextColumn::make('license_type')
                    ->label('License Type')
                    ->formatStateUsing(fn (string $state): string => Str::title($state)),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('submit_for_approval')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn(SoftwareHandover $record): bool => $record->status === 'Draft')
                        ->action(function (SoftwareHandover $record): void {
                            $record->update([
                                'status' => 'New',
                                'submitted_at' => now(),
                            ]);

                            // Use the controller for PDF generation
                            app(GenerateSoftwareHandoverPdfController::class)->generateInBackground($record);

                            Notification::make()
                                ->title('Handover submitted for approval')
                                ->success()
                                ->send();
                        }),
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn(SoftwareHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    // Also add the view reason and convert to draft actions for completeness
                    Action::make('view_reason')
                        ->label('View Reason')
                        ->visible(fn(SoftwareHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Change Request Reason')
                        ->modalContent(fn($record) => view('components.view-reason', [
                            'reason' => $record->reject_reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('3xl')
                        ->color('warning'),
                    Action::make('mark_rejected')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->hidden(
                            fn(SoftwareHandover $record): bool =>
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
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Update both status and add the rejection remarks
                            $record->update([
                                'status' => 'Rejected',
                                'reject_reason' => $data['reject_reason']
                            ]);

                            $salespersonName = $record->salesperson;
                            $salesperson = null;

                            if ($salespersonName) {
                                $salesperson = \App\Models\User::where('name', $salespersonName)
                                    ->where('role_id', 2)
                                    ->first();
                            }

                            if (!$salesperson && $record->lead_id) {
                                $lead = \App\Models\Lead::find($record->lead_id);
                                if ($lead && $lead->salesperson) {
                                    $salesperson = \App\Models\User::find($lead->salesperson);
                                }
                            }

                            $salespersonEmail = $salesperson ? $salesperson->email : null;
                            $salespersonName = $salesperson ? $salesperson->name : ($record->salesperson ?? 'Unknown Salesperson');

                            $rejecter = auth()->user();
                            $rejecterName = $rejecter->name ?? 'System';
                            $rejecterEmail = $rejecter->email;

                            $handoverId = $record->formatted_handover_id;

                            if ($salespersonEmail) {
                                try {
                                    $rejectedDate = now()->format('d F Y');
                                    $rejectReason = $data['reject_reason'];

                                    \Illuminate\Support\Facades\Mail::send('emails.software_handover_rejection', [
                                        'rejecterName' => $rejecterName,
                                        'rejectedDate' => $rejectedDate,
                                        'handoverId' => $handoverId,
                                        'salespersonName' => $salespersonName,
                                        'rejectReason' => $rejectReason
                                    ], function ($message) use ($salespersonEmail, $handoverId, $rejecterEmail, $rejecterName) {
                                        $message->to($salespersonEmail)
                                            ->from($rejecterEmail, $rejecterName) // Set the rejecter as the sender
                                            ->subject("REJECTED | SOFTWARE HANDOVER ID {$handoverId}");
                                    });

                                    // Log successful email sending
                                    \Illuminate\Support\Facades\Log::info("Rejection email sent to {$salespersonEmail} for handover {$handoverId}");
                                } catch (\Exception $e) {
                                    // Log email sending failure
                                    \Illuminate\Support\Facades\Log::error("Failed to send rejection email: {$e->getMessage()}");
                                }
                            } else {
                                \Illuminate\Support\Facades\Log::warning("Cannot send rejection email - no email address found for salesperson: {$salespersonName}");
                            }

                            Notification::make()
                                ->title('Software Handover marked as rejected')
                                ->body('Rejection reason: ' . $data['reject_reason'])
                                ->danger()
                                ->send();
                        })
                        ->requiresConfirmation(false),
                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->modalWidth('3xl')
                        ->form([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('speaker_category')
                                        ->label('Speaker Category')
                                        ->readOnly()
                                        ->default(function (SoftwareHandover $record) {
                                            if ($record && $record->speaker_category) {
                                                return ucwords($record->speaker_category);
                                            }
                                            return ucwords($record->speaker_category) ?? 'Not specified';
                                        })
                                        ->dehydrated(false),

                                    Select::make('implementer_id')
                                        ->label('Implementer')
                                        ->options(function () {
                                            return \App\Models\User::whereIn('role_id', [4,5])
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->required()
                                        ->searchable()
                                        ->placeholder('Select an implementer')
                                        ->default(function (SoftwareHandover $record) {
                                            // ✅ If speaker category is Mandarin, auto-select John Low
                                            if ($record && strtolower($record->speaker_category) === 'mandarin') {
                                                $johnLow = \App\Models\User::whereIn('role_id', [4,5])
                                                    ->where('name', 'LIKE', '%John Low%')
                                                    ->first();

                                                if ($johnLow) {
                                                    \Illuminate\Support\Facades\Log::info("Auto-selecting John Low for Mandarin speaker", [
                                                        'handover_id' => $record->id,
                                                        'john_low_id' => $johnLow->id,
                                                    ]);
                                                    return $johnLow->id;
                                                }
                                            }
                                            return null;
                                        })
                                        ->disabled(function (SoftwareHandover $record) {
                                            // ✅ Make readonly if speaker category is Mandarin
                                            return $record && strtolower($record->speaker_category) === 'mandarin';
                                        })
                                        ->dehydrated(true),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('company_size')
                                        ->label(false)
                                        ->content(function (SoftwareHandover $record) {
                                            $companySizeLabel = $record->headcount_company_size_label ?? 'Unknown';
                                            $headcount = $record->headcount ?? 'N/A';

                                            return new HtmlString(
                                                '<span style="font-weight: 600; color: #475569; font-size: 14px;">' . 'Company Size: ' .
                                                '<span style="font-weight: 700; color: #DC2626;">' . $companySizeLabel . '</span>' .
                                                '</span>'
                                            );
                                        }),

                                    // ✅ PLACEHOLDER 2: Project Sequence Link
                                    \Filament\Forms\Components\Placeholder::make('project_sequence')
                                        ->label(false)
                                        ->content(function (SoftwareHandover $record) {
                                            return new HtmlString(
                                                '<span style="font-weight: 600; color: #475569; font-size: 14px;">Project Sequence: ' . '<a href="https://crm.timeteccloud.com/admin/implementer-audit-list"
                                                target="_blank"
                                                style="color: #3b82f6; text-decoration: none; font-weight: 500; font-size: 14px; display: inline-flex; align-items: center; gap: 4px;"
                                                onmouseover="this.style.textDecoration=\'underline\'; this.style.color=\'#2563eb\'"
                                                onmouseout="this.style.textDecoration=\'none\'; this.style.color=\'#3b82f6\'">
                                                Click Here
                                                </a></span>'
                                            );
                                        }),
                                ]),

                            // Section::make('AutoCount Invoice Creation')
                            //     ->schema([
                            //         TextInput::make('tt_invoice_number')
                            //             ->label('License Number')
                            //             ->placeholder('Enter alphanumeric license number (one-time entry for all invoices)')
                            //             ->maxLength(255),

                            //         Grid::make(2)->schema([
                            //             // ✅ Fixed debtor display (no selection needed)
                            //             Placeholder::make('debtor_info')
                            //                 ->label('Debtor Code')
                            //                 ->content('ARM-P0062 - PEMBANGUNAN SUMBER MANUSIA BERHAD')
                            //                 ->columnSpan(1),

                            //             Checkbox::make('create_autocount_invoice')
                            //                 ->label('Create AutoCount Invoice')
                            //                 ->default(true)
                            //                 ->live()
                            //                 ->columnSpan(1),
                            //         ]),

                            //         // ✅ Hidden field to store the fixed debtor selection
                            //         \Filament\Forms\Components\Hidden::make('debtor_selection')
                            //             ->default('ARM-P0062'),

                            //         // Invoice preview
                            //         Section::make('Invoice Preview')
                            //             ->schema([
                            //                 Placeholder::make('invoice_preview')
                            //                     ->label('')
                            //                     ->content(function (SoftwareHandover $record, callable $get) {
                            //                         if (!$get('create_autocount_invoice')) {
                            //                             return 'AutoCount invoice creation is disabled.';
                            //                         }

                            //                         $service = app(AutoCountIntegrationService::class);
                            //                         $preview = $service->generateInvoicePreview($record);

                            //                         if (empty($preview['invoices'])) {
                            //                             return $preview['message'] ?? 'No items to display';
                            //                         }

                            //                         $html = '<div class="space-y-4">';
                            //                         $html .= '<div><strong>Salesperson:</strong> ' . $preview['salesperson'] . '</div>';
                            //                         $html .= '<div><strong>Company:</strong> ' . $preview['company'] . '</div>';
                            //                         $html .= '<div><strong>Total Invoices:</strong> ' . $preview['total_invoices'] . '</div>';

                            //                         // ✅ Show each invoice separately
                            //                         foreach ($preview['invoices'] as $index => $invoice) {
                            //                             $html .= '<div class="p-3 mt-4 border rounded bg-gray-50">';
                            //                             $html .= '<div class="font-semibold text-blue-600">Invoice ' . ($index + 1) . '</div>';
                            //                             $html .= '<div><strong>Document No:</strong> ' . $invoice['invoice_no'] . '</div>';
                            //                             $html .= '<div class="mt-2">';
                            //                             $html .= '<div class="mb-2 text-sm font-semibold">Items:</div>';

                            //                             foreach ($invoice['items'] as $item) {
                            //                                 $html .= '<div class="flex justify-between py-1 text-sm">';
                            //                                 $html .= '<div class="flex items-center gap-2">';
                            //                                 $html .= '<span class="px-2 py-1 font-mono text-xs bg-gray-100 rounded">' . $item['code'] . '</span>';
                            //                                 $html .= '<span class="text-gray-600">× ' . number_format($item['quantity']) . '</span>';
                            //                                 $html .= '</div>';
                            //                                 $html .= '<span class="font-semibold">RM ' . number_format($item['amount'], 2) . '</span>';
                            //                                 $html .= '</div>';
                            //                             }

                            //                             $html .= '</div>';
                            //                             $html .= '<div class="flex justify-between pt-2 mt-2 font-semibold border-t">';
                            //                             $html .= '<span>Invoice Total:</span><span>RM ' . number_format($invoice['total'], 2) . '</span>';
                            //                             $html .= '</div></div>';
                            //                         }

                            //                         // ✅ Show grand total if multiple invoices
                            //                         if ($preview['total_invoices'] > 1) {
                            //                             $html .= '<div class="flex justify-between pt-2 mt-4 text-lg font-bold border-t-2 border-blue-500">';
                            //                             $html .= '<span>Grand Total:</span><span>RM ' . number_format($preview['grand_total'], 2) . '</span>';
                            //                             $html .= '</div>';
                            //                         }

                            //                         $html .= '</div>';

                            //                         return new \Illuminate\Support\HtmlString($html);
                            //                     })
                            //             ])
                            //             ->visible(fn (callable $get) => $get('create_autocount_invoice')),
                            //     ])
                            //     // ✅ Hide the entire AutoCount section if no proforma_invoice_hrdf
                            //     ->visible(function (SoftwareHandover $record) {
                            //         // Check if proforma_invoice_hrdf exists and is not empty
                            //         if (empty($record->proforma_invoice_hrdf)) {
                            //             return false;
                            //         }

                            //         // Parse the JSON and check if it contains any valid quotation IDs
                            //         $hrdfPis = is_string($record->proforma_invoice_hrdf)
                            //             ? json_decode($record->proforma_invoice_hrdf, true)
                            //             : $record->proforma_invoice_hrdf;

                            //         // Return true only if we have a valid array with content
                            //         return is_array($hrdfPis) && !empty($hrdfPis);
                            //     })
                            //     ->collapsible()
                            //     ->collapsed(false),
                            FileUpload::make('invoice_file')
                                ->label('Upload Invoice')
                                ->disk('public')
                                ->directory('handovers/invoices')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->multiple()
                                ->maxFiles(10)
                                ->minFiles(1)
                                ->openable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                    $companyName = Str::slug($get('company_name') ?? 'invoice');
                                    $date = now()->format('Y-m-d');
                                    $random = Str::random(5);
                                    $extension = $file->getClientOriginalExtension();

                                    return "{$companyName}-invoice-{$date}-{$random}.{$extension}";
                                }),

                            Placeholder::make('module_check_info')
                                ->label(false)
                                ->content(function (SoftwareHandover $record) {
                                    // Check all modules
                                    $ta = $this->shouldModuleBeChecked($record, [31, 118, 114, 108, 60]);
                                    $tl = $this->shouldModuleBeChecked($record, [38, 119, 115, 109, 60]);
                                    $tc = $this->shouldModuleBeChecked($record, [39, 120, 116, 110, 60]);
                                    $tp = $this->shouldModuleBeChecked($record, [40, 121, 117, 111, 60]);
                                    $tapp = $this->shouldModuleBeChecked($record, [59]);
                                    $thire = $this->shouldModuleBeChecked($record, [41, 112]);
                                    $tacc = $this->shouldModuleBeChecked($record, [93, 113]);
                                    $tpbi = $this->shouldModuleBeChecked($record, [42]);

                                    // If no modules are checked
                                    if (!$ta && !$tl && !$tc && !$tp && !$tapp && !$thire && !$tacc && !$tpbi) {
                                        return new HtmlString(
                                            '<div style="background-color: #f94449; border-left: 4px solid #F59E0B; padding: 12px; margin-top: 8px; border-radius: 4px;">
                                                <div style="display: flex; align-items: start; gap: 8px;">
                                                    <div>
                                                        <p style="color: #ffffff; font-weight: 600; margin: 0;">⚠️ No Modules Auto-Selected</p>
                                                        <p style="color: #ffffff; margin: 4px 0 0 0; font-size: 14px;">
                                                            No products found in the selected Proforma Invoice. Please inform Zi Lih. Thanks!
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>'
                                        );
                                    }
                                }),
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            $autoCountResult = null;
                            if ($data['create_autocount_invoice'] ?? false) {
                                // ✅ Check if quotations already have AutoCount invoices
                                $quotationIds = [];
                                if ($record->proforma_invoice_hrdf) {
                                    $hrdfPis = is_string($record->proforma_invoice_hrdf)
                                        ? json_decode($record->proforma_invoice_hrdf, true)
                                        : $record->proforma_invoice_hrdf;
                                    if (is_array($hrdfPis)) {
                                        $quotationIds = $hrdfPis;
                                    }
                                }

                                if (!empty($quotationIds)) {
                                    $alreadyProcessed = \App\Models\Quotation::whereIn('id', $quotationIds)
                                        ->where('autocount_generated_pi', true)
                                        ->pluck('pi_reference_no')
                                        ->toArray();

                                    if (!empty($alreadyProcessed)) {
                                        Notification::make()
                                            ->title('AutoCount Invoice Already Generated')
                                            ->body('The following quotations already have AutoCount invoices: ' . implode(', ', $alreadyProcessed))
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                }

                                $autoCountService = app(AutoCountIntegrationService::class);
                                $autoCountResult = $autoCountService->processHandoverInvoiceCreation($record, $data);

                                if (isset($autoCountResult['invoice_numbers']) && is_array($autoCountResult['invoice_numbers'])) {
                                    // Get the preview data to extract invoice details
                                    $service = app(AutoCountIntegrationService::class);
                                    $preview = $service->generateInvoicePreview($record);

                                    // Get salesperson autocount_name
                                    $salespersonId = $record->lead->salesperson ?? null;
                                    $salesperson = \App\Models\User::find($salespersonId);
                                    $salespersonName = $salesperson?->autocount_name ?? ($salesperson?->name ?? 'Unknown Salesperson');

                                    // ✅ Debug log to check data structure
                                    Log::info('Creating CrmHrdfInvoice records - Data check', [
                                        'invoice_numbers' => $autoCountResult['invoice_numbers'],
                                        'preview_invoices' => isset($preview['invoices']) ? count($preview['invoices']) : 'No preview invoices',
                                        'preview_structure' => $preview,
                                        'handover_id' => $record->id,
                                    ]);

                                    // ✅ Use invoice_numbers array directly if preview doesn't match
                                    foreach ($autoCountResult['invoice_numbers'] as $index => $invoiceNumber) {
                                        // ✅ Get the quotation ID and amount using same logic as preview
                                        $quotationId = null;
                                        $amount = 0;

                                        // Get quotation groups from autoCount result
                                        if (isset($autoCountResult['quotation_groups']) && isset($autoCountResult['quotation_groups'][$index])) {
                                            $quotationGroup = $autoCountResult['quotation_groups'][$index];

                                            // Get the first quotation ID from this group
                                            if (!empty($quotationGroup['quotation_ids'])) {
                                                $quotationId = $quotationGroup['quotation_ids'][0];
                                            }

                                            // ✅ Get amount from quotation_groups (same as preview)
                                            if (isset($quotationGroup['total_amount'])) {
                                                $amount = $quotationGroup['total_amount'];
                                            }

                                            Log::info('Got amount from quotation_groups (same as preview)', [
                                                'invoice_number' => $invoiceNumber,
                                                'quotation_id' => $quotationId,
                                                'amount_from_groups' => $amount,
                                                'group_index' => $index,
                                            ]);
                                        }

                                        // ✅ Fallback: If no quotation group found, get directly from quotation
                                        if (!$quotationId && !empty($quotationIds)) {
                                            $quotationId = $quotationIds[$index] ?? $quotationIds[0];

                                            if ($quotationId) {
                                                // ✅ Get amount from QuotationDetail total_after_tax (sum all items in the quotation)
                                                $amount = \App\Models\QuotationDetail::where('quotation_id', $quotationId)
                                                    ->sum('total_after_tax');

                                                Log::info('Got amount from QuotationDetail total_after_tax (fallback)', [
                                                    'invoice_number' => $invoiceNumber,
                                                    'quotation_id' => $quotationId,
                                                    'total_after_tax_sum' => $amount,
                                                ]);
                                            }
                                        }

                                        try {
                                            // Get company name from quotation subsidiary if available
                                            $customerName = $record->company_name;
                                            if (!empty($quotationId)) {
                                                $quotation = \App\Models\Quotation::with('subsidiary', 'lead.companyDetail')->find($quotationId);
                                                if ($quotation && $quotation->subsidiary && !empty($quotation->subsidiary->company_name)) {
                                                    $customerName = $quotation->subsidiary->company_name;
                                                } elseif ($quotation && $quotation->lead && $quotation->lead->companyDetail && !empty($quotation->lead->companyDetail->company_name)) {
                                                    $customerName = $quotation->lead->companyDetail->company_name;
                                                }
                                            }

                                            $crmInvoice = CrmHrdfInvoice::create([
                                                'invoice_no' => $invoiceNumber,
                                                'invoice_date' => now()->toDateString(),
                                                'company_name' => $customerName,
                                                'handover_type' => 'SW', // SW for Software Handover
                                                'salesperson' => $salespersonName,
                                                'handover_id' => $record->id,
                                                'quotation_id' => $quotationId, // ✅ Store the quotation ID
                                                'debtor_code' => $autoCountResult['debtor_code'],
                                                'total_amount' => $amount, // ✅ Use amount from quotation_groups
                                                'tt_invoice_number' => $data['tt_invoice_number'] ?? '',
                                            ]);

                                            Log::info('CrmHrdfInvoice record created successfully', [
                                                'crm_invoice_id' => $crmInvoice->id,
                                                'invoice_no' => $invoiceNumber,
                                                'quotation_id' => $quotationId,
                                                'amount' => $amount,
                                                'handover_id' => $record->id,
                                                'source' => 'quotation_groups',
                                            ]);

                                        } catch (\Exception $e) {
                                            Log::error('Failed to create individual CrmHrdfInvoice record', [
                                                'invoice_no' => $invoiceNumber,
                                                'quotation_id' => $quotationId,
                                                'handover_id' => $record->id,
                                                'error' => $e->getMessage(),
                                                'amount_attempted' => $amount,
                                            ]);
                                        }
                                    }

                                    Log::info('Software Handover HRDF Invoice records creation completed', [
                                        'invoice_numbers' => $autoCountResult['invoice_numbers'],
                                        'handover_id' => $record->id,
                                        'company_name' => $record->company_name,
                                        'total_invoices' => count($autoCountResult['invoice_numbers']),
                                        'handover_type' => 'SW'
                                    ]);

                                } else {
                                    Log::warning('No invoice numbers found in autoCountResult', [
                                        'autoCountResult' => $autoCountResult,
                                        'handover_id' => $record->id,
                                    ]);
                                }
                            }

                            // Handle file array encoding for invoice_file
                            if (isset($data['invoice_file']) && is_array($data['invoice_file'])) {
                                // Get existing invoice files
                                $existingInvoiceFiles = [];
                                if ($record->invoice_file) {
                                    if (is_string($record->invoice_file)) {
                                        $existingInvoiceFiles = json_decode($record->invoice_file, true) ?? [];
                                    } else if (is_array($record->invoice_file)) {
                                        $existingInvoiceFiles = $record->invoice_file;
                                    }
                                }

                                // Merge existing files with new ones
                                $mergedInvoiceFiles = array_merge($existingInvoiceFiles, $data['invoice_file']);

                                // Encode the merged files
                                $data['invoice_file'] = json_encode($mergedInvoiceFiles);
                            }

                            $implementerId = $data['implementer_id'];
                            $implementer = \App\Models\User::find($implementerId);
                            $implementerName = $implementer?->name ?? 'Unknown';
                            $implementerEmail = $implementer?->email ?? null;

                            ImplementerLogs::create([
                                'lead_id' => $record->lead_id,
                                'description' => 'NEW PROJECT ASSIGNMENT',
                                'subject_id' => $record->id, // The software handover ID
                                'causer_id' => auth()->id(), // Who assigned the project
                                'remark' => "Project assigned to {$implementer->name} for {$record->company_name}",
                            ]);

                            // Get the salesperson info
                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            // Prepare data for update
                            $updateData = [
                                'project_priority' => 'High',
                                'status' => 'Completed',
                                'completed_at' => now(),
                                'implementer' => $implementerName,
                                'ta' => $this->shouldModuleBeChecked($record, [31, 118, 114, 108, 60]), // TCL_TA USER-NEW, TCL_TA USER-ADDON, TCL_TA USER-ADDON(R), TCL_TA USER-RENEWAL
                                'tl' => $this->shouldModuleBeChecked($record, [38, 119, 115, 109, 60]), // TCL_LEAVE USER-NEW, TCL_LEAVE USER-ADDON, TCL_LEAVE USER-ADDON(R), TCL_LEAVE USER-RENEWAL
                                'tc' => $this->shouldModuleBeChecked($record, [39, 120, 116, 110, 60]), // TCL_CLAIM USER-NEW, TCL_CLAIM USER-ADDON, TCL_CLAIM USER-ADDON(R), TCL_CLAIM USER-RENEWAL
                                'tp' => $this->shouldModuleBeChecked($record, [40, 121, 117, 111, 60]), // TCL_PAYROLL USER-NEW, TCL_PAYROLL USER-ADDON, TCL_PAYROLL USER-ADDON(R), TCL_PAYROLL USER-RENEWAL
                                'tapp' => $this->shouldModuleBeChecked($record, [59]), // TCL_APPRAISAL USER-NEW
                                'thire' => $this->shouldModuleBeChecked($record, [41, 112]), // TCL_HIRE-NEW, TCL_HIRE-RENEWAL
                                'tacc' => $this->shouldModuleBeChecked($record, [93, 113]), // TCL_ACCESS-NEW, TCL_ACCESS-RENEWAL
                                'tpbi' => $this->shouldModuleBeChecked($record, [42]), // TCL_POWER BI
                                'follow_up_date' => now(),
                                'follow_up_counter' => true,
                            ];

                            // Add invoice file if it exists
                            if (isset($data['invoice_file'])) {
                                $updateData['invoice_file'] = $data['invoice_file'];
                            }

                            // Add license number if it exists
                            if (isset($data['tt_invoice_number'])) {
                                $updateData['tt_invoice_number'] = $data['tt_invoice_number'];
                            }

                            // Update the record
                            $record->update($updateData);

                            $notificationTitle = 'Software Handover Completed Successfully';
                            $notificationBody = "Assigned to: {$implementerName}";

                            if ($autoCountResult && $autoCountResult['success']) {
                                if (isset($autoCountResult['invoice_numbers']) && count($autoCountResult['invoice_numbers']) > 1) {
                                    $notificationBody .= "\n✅ AutoCount Invoices: " . implode(', ', $autoCountResult['invoice_numbers']);
                                } else {
                                    $invoiceNo = $autoCountResult['invoice_numbers'][0] ?? $autoCountResult['invoice_no'] ?? 'Unknown';
                                    $notificationBody .= "\n✅ AutoCount Invoice: {$invoiceNo}";
                                }
                                $notificationBody .= "\n📋 Debtor Code: {$autoCountResult['debtor_code']}";
                            }

                            Notification::make()
                                ->title($notificationTitle)
                                ->body($notificationBody)
                                ->success()
                                ->send();

                            try {
                                $selectedModules = $record->getSelectedModules();
                                $modulesToSync = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

                                $createdCount = \App\Filament\Resources\LeadResource\Tabs\ProjectPlanTabs::createProjectPlansForModules(
                                    $record->lead_id,
                                    $record->id,
                                    $modulesToSync
                                );

                                \Illuminate\Support\Facades\Log::info("Auto-created project plans on handover completion", [
                                    'handover_id' => $record->id,
                                    'lead_id' => $record->lead_id,
                                    'modules' => $modulesToSync,
                                    'created_count' => $createdCount
                                ]);

                                if ($createdCount > 0) {
                                    Notification::make()
                                        ->title('Project Plans Created')
                                        ->body("Created {$createdCount} project tasks for modules: " . implode(', ', $modulesToSync))
                                        ->success()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to auto-create project plans: {$e->getMessage()}");
                            }

                            // Send email notification
                            try {
                                $viewName = 'emails.handover_notification';

                                // Get implementer and company details
                                $implementerName = $implementer?->name ?? 'Unknown';
                                $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';
                                $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                                // Format the handover ID properly
                                $handoverId = $record->formatted_handover_id;

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
                                        'name' => $implementerName,
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
                                    'invoiceFiles' => $invoiceFiles, // Array of all invoice file URLs
                                ];

                                // Initialize recipients array with admin email
                                $recipients = ['faiz@timeteccloud.com']; // Always include admin

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
                                            ->subject("SOFTWARE HANDOVER ID {$handoverId} | {$companyName}");
                                    });

                                    \Illuminate\Support\Facades\Log::info("Project assignment email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                }
                            } catch (\Exception $e) {
                                // Log error but don't stop the process
                                \Illuminate\Support\Facades\Log::error("Email sending failed for handover #{$record->id}: {$e->getMessage()}");
                            }

                            Notification::make()
                                ->title('Software Handover marked as completed')
                                ->body("This handover has been marked as completed and assigned to $implementerName.")
                                ->success()
                                ->send();

                            $controller = app(\App\Http\Controllers\CustomerActivationController::class);

                            try {
                                // Decode implementation_pics
                                $pics = [];
                                if (is_string($record->implementation_pics)) {
                                    $pics = json_decode($record->implementation_pics, true) ?? [];
                                } elseif (is_array($record->implementation_pics)) {
                                    $pics = $record->implementation_pics;
                                }

                                // Collect all valid emails from implementation_pics
                                $picEmails = [];
                                foreach ($pics as $pic) {
                                    if (!empty($pic['pic_email_impl']) && filter_var($pic['pic_email_impl'], FILTER_VALIDATE_EMAIL)) {
                                        $picEmails[] = $pic['pic_email_impl'];
                                    }
                                }

                                if (!empty($picEmails)) {
                                    // Format the handover ID properly
                                    $handoverId = $record->formatted_handover_id;

                                    // Send group email to all PICs with implementer as sender and CC
                                    $controller = app(\App\Http\Controllers\CustomerActivationController::class);
                                    $controller->sendGroupActivationEmail($record->lead_id, $picEmails, $implementerEmail, $implementerName, $handoverId);

                                    Notification::make()
                                        ->title('Customer Portal Activation Emails Sent')
                                        ->success()
                                        ->body('Customer portal activation emails have been sent to: ' . implode(', ', $picEmails))
                                        ->send();

                                    // Log the activity
                                    activity()
                                        ->causedBy(auth()->user())
                                        ->performedOn($record)
                                        ->withProperties([
                                            'emails' => $picEmails,
                                            'implementer' => $implementerName,
                                            'handover_id' => $handoverId
                                        ])
                                        ->log('Customer portal activation emails sent to all implementation PICs');
                                } else {
                                    \Illuminate\Support\Facades\Log::warning("No implementation PICs found for handover {$handoverId}");
                                }

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Customer Portal Activation Error')
                                    ->danger()
                                    ->body('Failed to send customer portal activation emails: ' . $e->getMessage())
                                    ->send();

                                \Illuminate\Support\Facades\Log::error('Customer activation emails failed: ' . $e->getMessage());
                            }
                        })
                        ->modalHeading('Complete Software Handover')
                        ->hidden(
                            fn(SoftwareHandover $record): bool =>
                            $record->status !== 'New' || auth()->user()->role_id === 2
                        ),
                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn(SoftwareHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (SoftwareHandover $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('Handover converted to draft')
                                ->success()
                                ->send();
                        }),
                ])->button()

            ]);
    }

    protected function shouldModuleBeChecked(SoftwareHandover $record, array $productIds): bool
    {
        // Get all PI IDs from proforma_invoice_product and proforma_invoice_hrdf
        $allPiIds = [];

        if (!empty($record->proforma_invoice_product)) {
            $productPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }

        if (!empty($record->proforma_invoice_hrdf)) {
            $hrdfPis = is_string($record->proforma_invoice_hrdf)
                ? json_decode($record->proforma_invoice_hrdf, true)
                : $record->proforma_invoice_hrdf;
            if (is_array($hrdfPis)) {
                $allPiIds = array_merge($allPiIds, $hrdfPis);
            }
        }

        // ✅ If both are empty, fall back to software_hardware_pi
        if (!empty($record->software_hardware_pi)) {
            $softwareHardwarePis = is_string($record->software_hardware_pi)
                ? json_decode($record->software_hardware_pi, true)
                : $record->software_hardware_pi;
            if (is_array($softwareHardwarePis)) {
                $allPiIds = $softwareHardwarePis;
            }
        }

        if (empty($allPiIds)) {
            return false;
        }

        // ✅ Check if any quotation details have these product IDs
        $hasProduct = \App\Models\QuotationDetail::whereIn('quotation_id', $allPiIds)
            ->whereIn('product_id', $productIds)
            ->exists();

        if ($hasProduct) {
            // Get the matched product for logging
            $matchedDetail = \App\Models\QuotationDetail::whereIn('quotation_id', $allPiIds)
                ->whereIn('product_id', $productIds)
                ->with('product', 'quotation')
                ->first();

            if ($matchedDetail) {
                \Illuminate\Support\Facades\Log::info("Module auto-checked based on quotation", [
                    'product_code' => $matchedDetail->product->code ?? 'Unknown',
                    'product_id' => $matchedDetail->product_id,
                    'pi_reference' => $matchedDetail->quotation->pi_reference_no ?? 'Unknown',
                    'handover_id' => $record->id,
                    'source' => !empty($record->proforma_invoice_product) || !empty($record->proforma_invoice_hrdf)
                        ? 'proforma_invoice'
                        : 'software_hardware_pi'
                ]);
            }
        }

        return $hasProduct;
    }

    public function render()
    {
        return view('livewire.salesperson_dashboard.software-handover-new');
    }
}
