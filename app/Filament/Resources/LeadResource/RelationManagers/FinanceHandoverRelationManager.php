<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\FinanceHandover;
use App\Models\HardwareHandoverV2;
use App\Models\Reseller;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Mail;

class FinanceHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'financeHandover';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function defaultForm()
    {
        return [
            Section::make('Step 1: Reseller Details')
                ->schema([
                    Select::make('related_hardware_handovers')
                        ->label('Select Hardware Handovers to Combine With')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $leadId = $this->getOwnerRecord()->id;

                            return HardwareHandoverV2::where('lead_id', $leadId)
                                ->get()
                                ->mapWithKeys(function ($handover) {
                                    // Format the display name with ID and any relevant info
                                    $formattedId = 'HW_250' . str_pad($handover->id, 4, '0', STR_PAD_LEFT);
                                    $displayName = $formattedId;

                                    // Add additional info if available (e.g., status, date)
                                    if ($handover->status) {
                                        $displayName .= ' - ' . $handover->status;
                                    }

                                    if ($handover->created_at) {
                                        $displayName .= ' (' . $handover->created_at->format('d M Y') . ')';
                                    }

                                    return [$handover->id => $displayName];
                                })
                                ->toArray();
                        })
                        ->required(),

                    Select::make('reseller_id')
                        ->label('Reseller Company Name')
                        ->required()
                        ->options(function () {
                            return Reseller::pluck('company_name', 'id')->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->live(),
                        // ->afterStateUpdated(function ($state, Forms\Set $set) {
                        //     if ($state) {
                        //         $reseller = Reseller::find($state);
                        //         if ($reseller) {
                        //             $set('pic_name', $reseller->name ?? '');
                        //             $set('pic_phone', $reseller->phone ?? '');
                        //             $set('pic_email', $reseller->email ?? '');
                        //         }
                        //     }
                        // }),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('pic_name')
                                ->label('Name')
                                ->required()
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => $state ? Str::upper($state) : $state)
                                ->afterStateUpdated(fn($state) => $state ? Str::upper($state) : $state),

                            TextInput::make('pic_phone')
                                ->label('HP Number')
                                ->required()
                                ->tel()
                                ->numeric(),

                            TextInput::make('pic_email')
                                ->label('Email Address')
                                ->required()
                                ->email(),
                        ]),
                ]),

            Section::make('Step 2: Upload Documents')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            FileUpload::make('invoice_by_customer')
                                ->label('Invoice by Customer')
                                ->disk('public')
                                ->directory('finance_handovers/invoice_customer')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $leadId = $this->getOwnerRecord()->id;
                                    $formattedId = 'FN_250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-INV-CUSTOMER-{$timestamp}-{$random}.{$extension}";
                                })
                                ->default(function (?FinanceHandover $record) {
                                    if (!$record || !$record->invoice_by_customer) {
                                        return [];
                                    }
                                    return is_string($record->invoice_by_customer)
                                        ? json_decode($record->invoice_by_customer, true) ?? []
                                        : $record->invoice_by_customer;
                                }),

                            FileUpload::make('payment_by_customer')
                                ->label('Payment by Customer')
                                ->disk('public')
                                ->directory('finance_handovers/payment_customer')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $leadId = $this->getOwnerRecord()->id;
                                    $formattedId = 'FN_250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-PAY-CUSTOMER-{$timestamp}-{$random}.{$extension}";
                                })
                                ->default(function (?FinanceHandover $record) {
                                    if (!$record || !$record->payment_by_customer) {
                                        return [];
                                    }
                                    return is_string($record->payment_by_customer)
                                        ? json_decode($record->payment_by_customer, true) ?? []
                                        : $record->payment_by_customer;
                                }),

                            FileUpload::make('invoice_by_reseller')
                                ->label('Invoice by Reseller')
                                ->disk('public')
                                ->directory('finance_handovers/invoice_reseller')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $leadId = $this->getOwnerRecord()->id;
                                    $formattedId = 'FN_250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-INV-RESELLER-{$timestamp}-{$random}.{$extension}";
                                })
                                ->default(function (?FinanceHandover $record) {
                                    if (!$record || !$record->invoice_by_reseller) {
                                        return [];
                                    }
                                    return is_string($record->invoice_by_reseller)
                                        ? json_decode($record->invoice_by_reseller, true) ?? []
                                        : $record->invoice_by_reseller;
                                }),
                        ]),
                ]),
        ];
    }

    public function headerActions(): array
    {
        $leadStatus = $this->getOwnerRecord()->lead_status ?? '';

        return [
            Tables\Actions\Action::make('AddFinanceHandover')
                ->label('Add Finance Handover')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->slideOver()
                ->modalSubmitActionLabel('Submit')
                ->modalHeading('Finance Handover')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->form($this->defaultForm())
                ->action(function (array $data): void {
                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'Completed';
                    $data['submitted_at'] = now();

                    // Handle file array encodings
                    if (isset($data['invoice_by_customer']) && is_array($data['invoice_by_customer'])) {
                        $data['invoice_by_customer'] = json_encode($data['invoice_by_customer']);
                    }

                    if (isset($data['payment_by_customer']) && is_array($data['payment_by_customer'])) {
                        $data['payment_by_customer'] = json_encode($data['payment_by_customer']);
                    }

                    if (isset($data['invoice_by_reseller']) && is_array($data['invoice_by_reseller'])) {
                        $data['invoice_by_reseller'] = json_encode($data['invoice_by_reseller']);
                    }

                    // Store related hardware handovers as JSON
                    if (isset($data['related_hardware_handovers']) && is_array($data['related_hardware_handovers'])) {
                        $data['related_hardware_handovers'] = json_encode($data['related_hardware_handovers']);
                    }

                    // Generate next available ID
                    $nextId = $this->getNextAvailableId();

                    // Create the handover record with specific ID
                    $handover = new FinanceHandover();
                    $handover->id = $nextId;
                    $handover->fill($data);
                    $handover->save();

                    // Send email notification
                    $this->sendFinanceHandoverEmail($handover);

                    Notification::make()
                        ->title('Finance Handover Created Successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, FinanceHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'FN_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('submitted_at')
                    ->label('Date Submit')
                    ->date('d M Y'),

                TextColumn::make('reseller.company_name')
                    ->label('Reseller Company')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn(string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: green;">New</span>'),
                        'Processing' => new HtmlString('<span style="color: orange;">Processing</span>'),
                        'Completed' => new HtmlString('<span style="color: blue;">Completed</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading('Finance Handover Details')
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (FinanceHandover $record): View {
                            return view('components.finance-handover-details', [
                                'record' => $record
                            ]);
                        }),

                    Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->visible(fn(FinanceHandover $record): bool => $record->status === 'New')
                        ->slideOver()
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->form($this->defaultForm())
                        ->action(function (FinanceHandover $record, array $data): void {
                            // Handle file array encodings
                            if (isset($data['invoice_by_customer']) && is_array($data['invoice_by_customer'])) {
                                $data['invoice_by_customer'] = json_encode($data['invoice_by_customer']);
                            }

                            if (isset($data['payment_by_customer']) && is_array($data['payment_by_customer'])) {
                                $data['payment_by_customer'] = json_encode($data['payment_by_customer']);
                            }

                            if (isset($data['invoice_by_reseller']) && is_array($data['invoice_by_reseller'])) {
                                $data['invoice_by_reseller'] = json_encode($data['invoice_by_reseller']);
                            }

                            $record->update($data);

                            Notification::make()
                                ->title('Finance Handover Updated Successfully')
                                ->success()
                                ->send();
                        }),
                ])->icon('heroicon-m-list-bullet')
                    ->size(ActionSize::Small)
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([]);
    }

    private function getNextAvailableId()
    {
        $existingIds = FinanceHandover::pluck('id')->toArray();

        if (empty($existingIds)) {
            return 1;
        }

        $maxId = max($existingIds);

        for ($i = 1; $i <= $maxId; $i++) {
            if (!in_array($i, $existingIds)) {
                return $i;
            }
        }

        return $maxId + 1;
    }

    private function sendFinanceHandoverEmail(FinanceHandover $handover)
    {
        try {
            $lead = $handover->lead;
            $reseller = $handover->reseller;

            // Get salesperson from Lead model properly
            $salesperson = null;
            if ($lead->salesperson) {
                // If salesperson is stored as user ID
                if (is_numeric($lead->salesperson)) {
                    $salesperson = User::find($lead->salesperson);
                } else {
                    // If salesperson is stored as name, search by name
                    $salesperson = User::where('name', $lead->salesperson)->first();
                }
            }

            // Fallback to auth user if no salesperson found
            if (!$salesperson) {
                $salesperson = auth()->user();
            }

            $formattedId = 'FN_250' . str_pad($handover->id, 3, '0', STR_PAD_LEFT);
            $companyName = $lead->companyDetail->company_name ?? $lead->name ?? 'Unknown Company';

            // Prepare attachment details
            $attachmentDetails = $this->formatAttachmentDetails($handover);

            // Prepare related hardware handovers details
            $relatedHandovers = $this->formatRelatedHandoverDetails($handover);

            $emailData = [
                'fn_id' => $formattedId,
                'submitted_date' => $handover->submitted_at->format('d M Y'),
                'salesperson' => $salesperson->name ?? 'Unknown',
                'customer' => $companyName,
                'reseller_company' => $reseller->company_name ?? 'Unknown',
                'pic_name' => $handover->pic_name,
                'pic_phone' => $handover->pic_phone,
                'pic_email' => $handover->pic_email,
                'attachment_details' => $attachmentDetails,
                'related_handovers' => $relatedHandovers,
            ];

            // Build recipients array
            $recipients = [];

            // Add salesperson email if available
            if ($salesperson && $salesperson->email) {
                $recipients[] = $salesperson->email;
            }

            // Always add soonhock email
            $recipients[] = 'soonhock@timeteccloud.com';

            // Remove duplicates and ensure valid emails
            $recipients = array_unique(array_filter($recipients, function($email) {
                return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            Log::info('Finance Handover Email Recipients: ', $recipients);
            Log::info('Salesperson found: ', [
                'id' => $salesperson->id ?? 'None',
                'name' => $salesperson->name ?? 'None',
                'email' => $salesperson->email ?? 'None'
            ]);

            // FIXED: Send to all recipients together, not one by one
            if (!empty($recipients)) {
                Mail::send('emails.finance-handover-notification', $emailData, function ($message) use ($recipients, $formattedId, $companyName) {
                    $message->to($recipients)
                        ->subject("FINANCE HANDOVER | {$formattedId} | {$companyName}");
                });

                Log::info("Finance handover email sent to all recipients: " . implode(', ', $recipients));
            }

        } catch (\Exception $e) {
            Log::error('Failed to send finance handover email: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    private function formatAttachmentDetails(FinanceHandover $handover): array
    {
        $details = [
            'invoice_by_customer' => [],
            'payment_by_customer' => [],
            'invoice_by_reseller' => [],
        ];

        // Process invoice by customer files
        if ($handover->invoice_by_customer) {
            $files = is_string($handover->invoice_by_customer)
                ? json_decode($handover->invoice_by_customer, true)
                : $handover->invoice_by_customer;

            if (is_array($files)) {
                foreach ($files as $index => $file) {
                    $details['invoice_by_customer'][] = [
                        'name' => "File " . ($index + 1),
                        'url' => asset('storage/' . $file)
                    ];
                }
            }
        }

        // Process payment by customer files
        if ($handover->payment_by_customer) {
            $files = is_string($handover->payment_by_customer)
                ? json_decode($handover->payment_by_customer, true)
                : $handover->payment_by_customer;

            if (is_array($files)) {
                foreach ($files as $index => $file) {
                    $details['payment_by_customer'][] = [
                        'name' => "File " . ($index + 1),
                        'url' => asset('storage/' . $file)
                    ];
                }
            }
        }

        // Process invoice by reseller files
        if ($handover->invoice_by_reseller) {
            $files = is_string($handover->invoice_by_reseller)
                ? json_decode($handover->invoice_by_reseller, true)
                : $handover->invoice_by_reseller;

            if (is_array($files)) {
                foreach ($files as $index => $file) {
                    $details['invoice_by_reseller'][] = [
                        'name' => "File " . ($index + 1),
                        'url' => asset('storage/' . $file)
                    ];
                }
            }
        }

        return $details;
    }

    private function formatRelatedHandoverDetails(FinanceHandover $handover): array
    {
        $details = [];

        if ($handover->related_hardware_handovers) {
            $handoverIds = is_string($handover->related_hardware_handovers)
                ? json_decode($handover->related_hardware_handovers, true)
                : $handover->related_hardware_handovers;

            if (is_array($handoverIds) && !empty($handoverIds)) {
                $hardwareHandovers = HardwareHandoverV2::whereIn('id', $handoverIds)->get();

                foreach ($hardwareHandovers as $hw) {
                    // Use dynamic year based on hardware handover creation date
                    $hwYear = $hw->created_at ? $hw->created_at->format('y') : now()->format('y');
                    $formattedId = 'HW_' . $hwYear . str_pad($hw->id, 4, '0', STR_PAD_LEFT);
                    $details[] = $formattedId;
                }
            }
        }

        return $details;
    }
}
