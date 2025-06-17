<?php

namespace App\Livewire;

use App\Filament\Filters\SortFilter;
use App\Models\HardwareHandover;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HardwareHandoverPendingMigration extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

    public function getOverdueHardwareHandovers()
    {
        return HardwareHandover::query()
            ->whereIn('status', ['Pending Migration'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getOverdueHardwareHandovers())
            ->defaultSort('created_at', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Approved' => 'Approved',
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
                TextColumn::make('handover_pdf')
                    ->label('ID')
                    ->formatStateUsing(function ($state) {
                        // If handover_pdf is null, return a placeholder
                        if (!$state) {
                            return '-';
                        }

                        // Extract just the filename without extension
                        $filename = basename($state, '.pdf');

                        // Return just the formatted ID part
                        return $filename;
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
                        $shortened = strtoupper(Str::limit($fullName, 20, '...'));
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

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
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

                    Action::make('mark_as_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalWidth('3xl')
                        // ->requiresConfirmation()
                        ->modalHeading("Mark as Completed")
                        ->modalDescription('Are you sure you want to mark this handover as completed? This will complete the hardware handover process.')
                        ->modalSubmitActionLabel('Yes, Mark as Completed')
                        ->modalCancelActionLabel('No, Cancel')
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
                                    'status' => 'Completed',
                                    'admin_remarks' => json_encode($allAdminRemarks)
                                ];

                                $record->update($updateData);
                            }
                            else {
                                // If no remarks provided, just update status
                                $record->update([
                                    'completed_at' => now(),
                                    'status' => 'Completed'
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

                                // Always include admin
                                $recipients[] = 'admin.timetec.hr@timeteccloud.com';

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
                        })
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.hardware-handover-pending-migration');
    }
}
