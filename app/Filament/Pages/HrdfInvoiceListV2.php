<?php

namespace App\Filament\Pages;

use App\Models\CrmHrdfInvoiceV2;
use App\Models\SoftwareHandover;
use App\Models\HardwareHandoverV2;
use App\Models\RenewalHandover;
use App\Models\Quotation;
use App\Models\HrdfClaim;
use App\Classes\Encryptor;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Tables\Actions\HeaderAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HrdfInvoiceListV2 extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'HRDF Invoices V2';
    protected static ?string $title = 'HRDF Invoice List';
    protected static string $view = 'filament.pages.hrdf-invoice-list-v2';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CrmHrdfInvoiceV2::query()
            )
            ->defaultSort('invoice_no', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->placeholder('EHIN2601-0001'),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Customer Name')
                    ->formatStateUsing(function ($state, CrmHrdfInvoiceV2 $record) {
                        // Get the quotation from proforma_invoice_data
                        $quotation = null;
                        if ($record->proforma_invoice_data) {
                            $quotation = Quotation::with(['subsidiary', 'lead.companyDetail'])->find($record->proforma_invoice_data);
                        }

                        // Determine which company name to display
                        $companyName = 'N/A';
                        $leadId = null;

                        if ($quotation) {
                            if ($quotation->subsidiary_id && $quotation->subsidiary) {
                                $companyName = $quotation->subsidiary->company_name;
                            } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                                $companyName = $quotation->lead->companyDetail->company_name;
                            }
                            // Get lead ID for the link
                            if ($quotation->lead) {
                                $leadId = $quotation->lead->id;
                            }
                        }

                        // Fall back to HRDF invoice company name if no other source
                        if ($companyName === 'N/A' && $record->company_name) {
                            $companyName = $record->company_name;
                        }

                        // Format the display name
                        $fullName = $companyName;
                        $shortened = strtoupper(Str::limit($fullName, 35, '...'));

                        // Create clickable link if we have a lead ID
                        if ($leadId) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($leadId);

                            return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                        target="_blank"
                                        title="' . e($fullName) . '"
                                        class="inline-block"
                                        style="color:#338cf0;">
                                        ' . $shortened . '
                                    </a>';
                        }

                        // Return plain text if no link available
                        return $shortened;
                    })
                    ->html(),

                TextColumn::make('handover_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SW' => 'success',
                        'HW' => 'warning',
                        'RW' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'SW' => 'SOFTWARE',
                        'HW' => 'HARDWARE',
                        'RW' => 'RENEWAL',
                        default => $state,
                    }),

                TextColumn::make('pi_reference_no')
                    ->label('PI Reference')
                    ->limit(20)
                    ->getStateUsing(function (CrmHrdfInvoiceV2 $record) {
                        if ($record->proforma_invoice_data) {
                            $quotation = Quotation::find($record->proforma_invoice_data);
                            if ($quotation && $quotation->pi_reference_no) {
                                return $quotation->pi_reference_no;
                            }
                        }
                        return 'N/A';
                    })
                    ->tooltip(function (CrmHrdfInvoiceV2 $record) {
                        if ($record->proforma_invoice_data) {
                            $quotation = Quotation::find($record->proforma_invoice_data);
                            if ($quotation && $quotation->pi_reference_no) {
                                return $quotation->pi_reference_no;
                            }
                        }
                        return 'No PI Reference Available';
                    }),

                TextColumn::make('handover_id')
                    ->label('Handover ID')
                    ->formatStateUsing(fn (CrmHrdfInvoiceV2 $record) => $record->formatted_handover_id)
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        TableAction::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (CrmHrdfInvoiceV2 $record): View {
                                // Get the actual handover record based on type
                                $handoverRecord = null;

                                switch ($record->handover_type) {
                                    case 'SW':
                                        $handoverRecord = SoftwareHandover::find($record->handover_id);
                                        break;
                                    case 'HW':
                                        $handoverRecord = HardwareHandoverV2::find($record->handover_id);
                                        break;
                                    case 'RW':
                                        $handoverRecord = RenewalHandover::find($record->handover_id);
                                        break;
                                }

                                if (!$handoverRecord) {
                                    return view('components.handover-not-found')
                                        ->with('extraAttributes', ['record' => $record]);
                                }

                                // Show different components based on handover type
                                switch ($record->handover_type) {
                                    case 'SW':
                                        return view('components.software-handover')
                                            ->with('extraAttributes', ['record' => $handoverRecord]);

                                    case 'HW':
                                        return view('components.hardware-handover')
                                            ->with('extraAttributes', ['record' => $handoverRecord]);

                                    case 'RW':
                                        return view('components.renewal-handover')
                                            ->with('extraAttributes', ['record' => $handoverRecord]);

                                    default:
                                        return view('components.software-handover')
                                            ->with('extraAttributes', ['record' => $handoverRecord]);
                                }
                            })
                    ),

                TextColumn::make('tt_invoice_number')
                    ->label('Details')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ? 'View' : 'N/A')
                    ->action(
                        TableAction::make('viewTTInvoice')
                            ->modalHeading('TT Invoice / Sales Order Number')
                            ->modalWidth('md')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalContent(function (CrmHrdfInvoiceV2 $record): View {
                                return view('components.tt-invoice-modal')
                                    ->with('extraAttributes', [
                                        'tt_invoice_number' => $record->tt_invoice_number,
                                        'company_name' => $record->company_name,
                                        'invoice_no' => $record->invoice_no
                                    ]);
                            })
                            ->visible(fn (CrmHrdfInvoiceV2 $record) => !is_null($record->tt_invoice_number))
                    )
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('subtotal')
                    ->label('Sub Total')
                    ->money('MYR')
                    ->sortable()
                    ->getStateUsing(function (CrmHrdfInvoiceV2 $record) {
                        if (!$record->proforma_invoice_data) {
                            return 0;
                        }

                        $quotation = Quotation::find($record->proforma_invoice_data);
                        if (!$quotation) {
                            return 0;
                        }

                        return $quotation->items()->sum('total_before_tax');
                    }),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('MYR')
                    ->sortable()
                    ->getStateUsing(function (CrmHrdfInvoiceV2 $record) {
                        if (!$record->proforma_invoice_data) {
                            return 0;
                        }

                        $quotation = Quotation::find($record->proforma_invoice_data);
                        if (!$quotation) {
                            return 0;
                        }

                        return $quotation->items()->sum('total_after_tax');
                    }),

                TextColumn::make('created_at')
                    ->label('Created At ')
                    ->dateTime('H:i')
                    ->sortable(),
            ])
            ->actions([
                TableAction::make('exportHrdfInvoice')
                    ->label('')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(function (CrmHrdfInvoiceV2 $record) {
                        return route('hrdf-invoice-data.export', [
                            'hrdfInvoice' => \App\Classes\Encryptor::encrypt($record->id)
                        ]);
                    })
                    ->openUrlInNewTab(),
            ])
            ->filters([
                SelectFilter::make('handover_type')
                    ->label('Handover Type')
                    ->options([
                        'SW' => 'Software',
                        'HW' => 'Hardware',
                        'RW' => 'Renewal',
                    ])
                    ->multiple(),
            ])
            ->defaultPaginationPageOption(50);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('createHrdfInvoice')
                ->label('CREATE')
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->size(ActionSize::Large)
                ->modalHeading('CREATE HRDF INVOICE')
                ->modalWidth('2xl')
                ->form([
                    Select::make('handover_type')
                        ->label('Select Type')
                        ->options([
                            'SW' => 'SOFTWARE HANDOVER',
                            'HW' => 'HARDWARE HANDOVER',
                            'RW' => 'RENEWAL HANDOVER'
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('handover_id', null);
                            $set('pi_references', []);
                        }),

                    Select::make('handover_id')
                        ->label('Choose handover ID')
                        ->options(function (callable $get) {
                            $handoverType = $get('handover_type');
                            if (!$handoverType) return [];

                            $handovers = match ($handoverType) {
                                'SW' => SoftwareHandover::with(['lead'])
                                    ->where('status', 'New')
                                    ->whereNotNull('proforma_invoice_hrdf')
                                    ->limit(50)
                                    ->get(),
                                'HW' => HardwareHandoverV2::with(['lead'])
                                    ->where('status', 'New')
                                    ->whereNotNull('proforma_invoice_hrdf')
                                    ->limit(50)
                                    ->get(),
                                'RW' => RenewalHandover::with(['lead'])
                                    ->where('status', 'New')
                                    ->limit(50)
                                    ->get(),
                                default => collect([])
                            };

                            if ($handovers->isEmpty()) {
                                return ['no_data' => 'No handovers found'];
                            }

                            return $handovers->mapWithKeys(function ($handover) {
                                $formattedId = $handover->formatted_handover_id ?? "ID_{$handover->id}";
                                $companyName = $handover->company_name
                                    ?? $handover->lead?->companyDetail?->company_name
                                    ?? 'Unknown Company';
                                return [$handover->id => "{$formattedId} / {$companyName}"];
                            })->toArray();
                        })
                        ->required()
                        ->searchable()
                        ->placeholder('Select handover type first')
                        ->disabled(fn (callable $get) => !$get('handover_type'))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $set('pi_references', []);

                            if (!$state) return;

                            $handoverType = $get('handover_type');
                            $handover = match ($handoverType) {
                                'SW' => SoftwareHandover::find($state),
                                'HW' => HardwareHandoverV2::find($state),
                                'RW' => RenewalHandover::find($state),
                                default => null
                            };

                            if (!$handover) return;

                            // Extract hrdf_grant_ids from handover record
                            $hrdfGrantIds = null;
                            if ($handoverType === 'SW' || $handoverType === 'RW') {
                                $hrdfGrantIds = $handover->hrdf_grant_ids;
                                if (is_string($hrdfGrantIds)) {
                                    $hrdfGrantIds = json_decode($hrdfGrantIds, true);
                                }
                            }

                            if (!$hrdfGrantIds || !is_array($hrdfGrantIds)) return;

                            // Create PI references from stored hrdf_grant_ids
                            $piReferences = [];
                            foreach ($hrdfGrantIds as $grantEntry) {
                                if (isset($grantEntry['quotation_id']) && isset($grantEntry['hrdf_grant_id'])) {
                                    $quotation = Quotation::find($grantEntry['quotation_id']);
                                    if ($quotation) {
                                        $piReferences[] = [
                                            'quotation_id' => $quotation->id,
                                            'pi_reference' => $quotation->pi_reference_no ?? 'No PI Reference',
                                            'hrdf_grant_id' => $grantEntry['hrdf_grant_id']
                                        ];
                                    }
                                }
                            }

                            $set('pi_references', $piReferences);
                        }),

                    Repeater::make('pi_references')
                        ->label('PI References & HRDF Grant IDs (from Handover)')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('pi_reference')
                                    ->label('PI Reference')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('hrdf_grant_id')
                                    ->label('HRDF Grant ID')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->visible(fn (callable $get) => !empty($get('pi_references')))
                        ->columnSpanFull(),

                    TextInput::make('tt_invoice_number')
                        ->label('TT Invoice / Sales Order Number')
                        ->required()
                        ->rule('regex:/^[A-Z0-9,_]+$/')
                        ->reactive(),
                ])
                ->action(function (array $data): void {
                    // Get handover details
                    $handover = match($data['handover_type']) {
                        'SW' => SoftwareHandover::find($data['handover_id']),
                        'HW' => HardwareHandoverV2::find($data['handover_id']),
                        'RW' => RenewalHandover::find($data['handover_id']),
                        default => null
                    };

                    if (!$handover) {
                        Notification::make()
                            ->title('Error')
                            ->body('Handover not found!')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Get company name from handover
                    $companyName = $handover->company_name
                        ?? $handover->lead?->company_name
                        ?? $handover->lead?->companyDetail?->company_name
                        ?? 'Unknown Company';

                    $invoicesCreated = 0;

                    // Check if we have PI references with HRDF grant IDs
                    if (!empty($data['pi_references'])) {
                        // Create invoice for each PI reference
                        foreach ($data['pi_references'] as $piReference) {
                            $invoiceNo = CrmHrdfInvoiceV2::generateInvoiceNumber();

                            CrmHrdfInvoiceV2::create([
                                'invoice_no' => $invoiceNo,
                                'invoice_date' => now(),
                                'company_name' => $companyName,
                                'handover_type' => $data['handover_type'],
                                'handover_id' => $data['handover_id'],
                                'tt_invoice_number' => $data['tt_invoice_number'],
                                'hrdf_grant_id' => $piReference['hrdf_grant_id'] ?? null,
                                'subtotal' => 0,
                                'total_amount' => 0,
                                'status' => 'draft',
                                'handover_data' => $handover->toArray(),
                                'proforma_invoice_data' => (int)$piReference['quotation_id']
                            ]);

                            $invoicesCreated++;
                        }
                    } else {
                        // Fallback to original logic for single PI
                        $proformaInvoices = null;

                        if ($data['handover_type'] === 'RW') {
                            $proformaInvoices = $handover->selected_quotation_ids;
                            if (is_string($proformaInvoices)) {
                                $proformaInvoices = json_decode($proformaInvoices, true);
                            }
                        } else {
                            $proformaInvoices = $handover->proforma_invoice_hrdf;
                        }

                        if (!$proformaInvoices) {
                            Notification::make()
                                ->title('Error')
                                ->body('No proforma invoice found for this handover!')
                                ->danger()
                                ->send();
                            return;
                        }

                        $invoiceNo = CrmHrdfInvoiceV2::generateInvoiceNumber();

                        CrmHrdfInvoiceV2::create([
                            'invoice_no' => $invoiceNo,
                            'invoice_date' => now(),
                            'company_name' => $companyName,
                            'handover_type' => $data['handover_type'],
                            'handover_id' => $data['handover_id'],
                            'tt_invoice_number' => $data['tt_invoice_number'],
                            'hrdf_grant_id' => null,
                            'subtotal' => 0,
                            'total_amount' => 0,
                            'status' => 'draft',
                            'handover_data' => $handover->toArray(),
                            'proforma_invoice_data' => (int)$proformaInvoices
                        ]);

                        $invoicesCreated = 1;
                    }

                    Notification::make()
                        ->title('HRDF Invoice(s) Created Successfully')
                        ->body("Created {$invoicesCreated} invoice(s) for {$companyName}")
                        ->success()
                        ->send();
                })
        ];
    }
}
