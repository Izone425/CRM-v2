<?php
// filepath: /var/www/html/timeteccrm/app/Livewire/AdminHeadcountDashboard/HeadcountNewTable.php

namespace App\Livewire\AdminHeadcountDashboard;

use App\Models\HeadcountHandover;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class HeadcountNewTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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

    #[On('refresh-hrdf-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getNewHeadcountHandovers()
    {
        return HeadcountHandover::with(['lead.companyDetail', 'lead.salespersonUser'])
            ->where('status', 'New')
            ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNewHeadcountHandovers())
            ->columns([
                TextColumn::make('id')
                    ->label('Headcount ID')
                    ->formatStateUsing(function ($state, HeadcountHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHeadcountHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HeadcountHandover $record): View {
                                return view('components.headcount-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('submitted_at')
                    ->label('Date Submitted')
                    ->dateTime('d M Y, g:ia')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('salesperson_name')
                    ->label('Salesperson')
                    ->getStateUsing(function (HeadcountHandover $record) {
                        if ($record->lead && $record->lead->salesperson) {
                            $user = User::find($record->lead->salesperson);
                            return $user ? $user->name : 'N/A';
                        }
                        return 'N/A';
                    })
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue; font-weight: bold;">New</span>'),
                        default => new HtmlString('<span style="font-weight: bold;">' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HeadcountHandover $record): View {
                            return view('components.headcount-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalHeading(function (HeadcountHandover $record): string {
                            $formattedId = $record->formatted_handover_id;
                            $companyName = $record->lead->companyDetail->company_name ?? 'Unknown Company';
                            return "Headcount Handover | {$formattedId} | {$companyName}";
                        })
                        ->modalSubmitActionLabel('Mark as Completed')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->form([
                            // Add PI and Invoice tracking based on quotations
                            Grid::make(1)
                                ->schema(function (Get $get, HeadcountHandover $record) {
                                    $sections = [];

                                    // Check for proforma_invoice_product (Type 1)
                                    if (!empty($record->proforma_invoice_product)) {
                                        $productPiIds = is_array($record->proforma_invoice_product)
                                            ? $record->proforma_invoice_product
                                            : json_decode($record->proforma_invoice_product, true);

                                        if (is_array($productPiIds) && !empty($productPiIds)) {
                                            $quotations = \App\Models\Quotation::whereIn('id', $productPiIds)
                                                ->with(['lead.companyDetail', 'subsidiary'])
                                                ->get();

                                            if ($quotations->isNotEmpty()) {
                                                $sections[] = Repeater::make('type_1_entries')
                                                    ->label(false)
                                                    ->schema([
                                                        Grid::make(3)->schema([
                                                            TextInput::make('pi_number')
                                                                ->label('PI Number')
                                                                ->readOnly()
                                                                ->default(fn($state, $get) => $quotations->get($get('../../quotation_id') ?? 0)?->pi_reference_no ?? 'N/A'),
                                                            TextInput::make('company_name')
                                                                ->label('Company Name')
                                                                ->readOnly()
                                                                ->default(function ($state, $get) use ($quotations) {
                                                                    $quotation = $quotations->get($get('../../quotation_id') ?? 0);
                                                                    if (!$quotation) return 'N/A';

                                                                    if ($quotation->subsidiary_id && $quotation->subsidiary) {
                                                                        return $quotation->subsidiary->company_name;
                                                                    }
                                                                    return $quotation->lead?->companyDetail?->company_name ?? 'N/A';
                                                                }),
                                                            TextInput::make('invoice_number')
                                                                ->label('Invoice Number')
                                                                ->required()
                                                                ->maxLength(13)
                                                                ->regex('/^[A-Z0-9-]+$/')
                                                                ->validationMessages([
                                                                    'regex' => 'Invoice number can only contain letters, numbers, and dashes.',
                                                                ])
                                                                ->live(onBlur: true)
                                                                ->extraAlpineAttributes([
                                                                    'x-on:input' => '
                                                                        const start = $el.selectionStart;
                                                                        const end = $el.selectionEnd;
                                                                        $el.value = $el.value.toUpperCase();
                                                                        $el.setSelectionRange(start, end);
                                                                    '
                                                                ])
                                                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                                        ])
                                                    ])
                                                    ->default(function () use ($quotations) {
                                                        return $quotations->map(function ($quotation, $index) {
                                                            $companyName = $quotation->subsidiary_id && $quotation->subsidiary
                                                                ? $quotation->subsidiary->company_name
                                                                : $quotation->lead?->companyDetail?->company_name ?? 'N/A';

                                                            return [
                                                                'quotation_id' => $quotation->id,
                                                                'pi_number' => $quotation->pi_reference_no ?? 'N/A',
                                                                'company_name' => $companyName,
                                                                'invoice_number' => ''
                                                            ];
                                                        })->toArray();
                                                    })
                                                    ->addable(false)
                                                    ->deletable(false)
                                                    ->reorderable(false)
                                                    ->collapsible(false);
                                            }
                                        }
                                    }

                                    // Check for proforma_invoice_hrdf (Type 2 for HRDF)
                                    if (!empty($record->proforma_invoice_hrdf)) {
                                        $hrdfPiIds = is_array($record->proforma_invoice_hrdf)
                                            ? $record->proforma_invoice_hrdf
                                            : json_decode($record->proforma_invoice_hrdf, true);

                                        if (is_array($hrdfPiIds) && !empty($hrdfPiIds)) {
                                            $quotations = \App\Models\Quotation::whereIn('id', $hrdfPiIds)
                                                ->with(['lead.companyDetail', 'subsidiary'])
                                                ->get();

                                            if ($quotations->isNotEmpty()) {
                                                $sections[] = Repeater::make('type_2_entries')
                                                    ->label(false)
                                                    ->schema([
                                                        Grid::make(3)->schema([
                                                            TextInput::make('pi_number')
                                                                ->label('PI Number')
                                                                ->readOnly()
                                                                ->default(fn($state, $get) => $quotations->get($get('../../quotation_id') ?? 0)?->pi_reference_no ?? 'N/A'),
                                                            TextInput::make('company_name')
                                                                ->label('Company Name')
                                                                ->readOnly()
                                                                ->default(function ($state, $get) use ($quotations) {
                                                                    $quotation = $quotations->get($get('../../quotation_id') ?? 0);
                                                                    if (!$quotation) return 'N/A';

                                                                    if ($quotation->subsidiary_id && $quotation->subsidiary) {
                                                                        return $quotation->subsidiary->company_name;
                                                                    }
                                                                    return $quotation->lead?->companyDetail?->company_name ?? 'N/A';
                                                                }),
                                                            TextInput::make('invoice_number')
                                                                ->label('Invoice Number')
                                                                ->required()
                                                                ->maxLength(13)
                                                                ->regex('/^[A-Z0-9-]+$/')
                                                                ->validationMessages([
                                                                    'regex' => 'Invoice number can only contain letters, numbers, and dashes.',
                                                                ])
                                                                ->live(onBlur: true)
                                                                ->extraAlpineAttributes([
                                                                    'x-on:input' => '
                                                                        const start = $el.selectionStart;
                                                                        const end = $el.selectionEnd;
                                                                        $el.value = $el.value.toUpperCase();
                                                                        $el.setSelectionRange(start, end);
                                                                    '
                                                                ])
                                                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                                        ])
                                                    ])
                                                    ->default(function () use ($quotations) {
                                                        return $quotations->map(function ($quotation, $index) {
                                                            $companyName = $quotation->subsidiary_id && $quotation->subsidiary
                                                                ? $quotation->subsidiary->company_name
                                                                : $quotation->lead?->companyDetail?->company_name ?? 'N/A';

                                                            return [
                                                                'quotation_id' => $quotation->id,
                                                                'pi_number' => $quotation->pi_reference_no ?? 'N/A',
                                                                'company_name' => $companyName,
                                                                'invoice_number' => ''
                                                            ];
                                                        })->toArray();
                                                    })
                                                    ->addable(false)
                                                    ->deletable(false)
                                                    ->reorderable(false)
                                                    ->collapsible(false);
                                            }
                                        }
                                    }

                                    // If no PI data found, show a message
                                    if (empty($sections)) {
                                        $sections[] = \Filament\Forms\Components\Placeholder::make('no_pi_data')
                                            ->label(false)
                                            ->content(new HtmlString(
                                                '<div style="background-color: #FEF3C7; border-left: 4px solid #F59E0B; padding: 12px; margin-top: 8px; border-radius: 4px;">
                                                    <div style="display: flex; align-items: start; gap: 8px;">
                                                        <div>
                                                            <p style="color: #92400E; font-weight: 600; margin: 0;">⚠️ No PI Data Available</p>
                                                            <p style="color: #92400E; margin: 4px 0 0 0; font-size: 14px;">
                                                                No Proforma Invoice data found for this headcount handover.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>'
                                            ));
                                    }

                                    return $sections;
                                }),
                        ])
                        ->action(function (HeadcountHandover $record, array $data): void {
                            try {
                                // Prepare update data
                                $updateData = [
                                    'status' => 'Completed',
                                    'completed_by' => auth()->id(),
                                    'completed_at' => now(),
                                ];

                                // Handle PI and Invoice tracking data
                                if (isset($data['type_1_entries']) && !empty($data['type_1_entries'])) {
                                    $updateData['product_pi_invoice_data'] = json_encode($data['type_1_entries']);
                                }
                                if (isset($data['type_2_entries']) && !empty($data['type_2_entries'])) {
                                    $updateData['hrdf_pi_invoice_data'] = json_encode($data['type_2_entries']);
                                }

                                // Update the record
                                $record->update($updateData);

                                // Get necessary data for email
                                $handoverId = $record->formatted_handover_id;
                                $companyDetail = $record->lead->companyDetail;
                                $companyName = $companyDetail ? $companyDetail->company_name : 'Unknown Company';

                                // Get salesperson from lead->salesperson (user ID)
                                $salesperson = null;
                                if ($record->lead && $record->lead->salesperson) {
                                    $salesperson = User::find($record->lead->salesperson);
                                }

                                $completedBy = auth()->user();

                                // Send email notification to salesperson
                                if ($salesperson && $salesperson->email) {
                                    try {
                                        Mail::send('emails.headcount-handover-completed', [
                                            'handoverId' => $handoverId,
                                            'companyName' => $companyName,
                                            'salesperson' => $salesperson,
                                            'completedBy' => $completedBy,
                                            'completedAt' => now(),
                                            'record' => $record
                                        ], function ($mail) use ($salesperson, $completedBy, $handoverId) {
                                            $mail->to($salesperson->email, $salesperson->name)
                                                ->subject("HEADCOUNT HANDOVER | {$handoverId} | COMPLETED");
                                        });

                                        \Illuminate\Support\Facades\Log::info("Headcount handover completion email sent", [
                                            'handover_id' => $handoverId,
                                            'salesperson_email' => $salesperson->email,
                                            'completed_by' => $completedBy->email
                                        ]);

                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error("Failed to send headcount handover completion email", [
                                            'error' => $e->getMessage(),
                                            'handover_id' => $handoverId
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->title('Headcount Handover Completed')
                                    ->body("Headcount handover {$handoverId} has been marked as completed and notification sent to salesperson.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to complete headcount handover: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->modalHeading(function (HeadcountHandover $record): string {
                            $formattedId = $record->formatted_handover_id;
                            return "Reject Headcount Handover {$formattedId}";
                        })
                        ->modalSubmitActionLabel('Reject Handover')
                        ->form([
                            Section::make('Rejection Reason')
                                ->description('Please provide a reason for rejecting this headcount handover')
                                ->schema([
                                    Textarea::make('reject_reason')
                                        ->label('Rejection Reason')
                                        ->required()
                                        ->placeholder('Enter the reason for rejection...')
                                        ->rows(4)
                                        ->maxLength(1000)
                                        ->helperText('This reason will be visible to the salesperson'),
                                ]),
                        ])
                        ->action(function (HeadcountHandover $record, array $data): void {
                            $record->update([
                                'status' => 'Rejected',
                                'rejected_by' => auth()->id(),
                                'rejected_at' => now(),
                                'reject_reason' => $data['reject_reason'],
                            ]);

                            $handoverId = $record->formatted_handover_id;

                            Notification::make()
                                ->title('Headcount Handover Rejected')
                                ->body("Headcount handover {$handoverId} has been rejected.")
                                ->warning()
                                ->send();
                        }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->label('Actions')
                ->color('primary')
                ->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('300s');
    }

    public function render()
    {
        return view('livewire.admin-headcount-dashboard.headcount-new-table');
    }
}
