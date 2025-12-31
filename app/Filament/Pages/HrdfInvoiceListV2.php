<?php

namespace App\Filament\Pages;

use App\Models\CrmHrdfInvoiceV2;
use App\Models\SoftwareHandover;
use App\Models\HardwareHandoverV2;
use App\Models\RenewalHandover;
use App\Models\Quotation;
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
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HrdfInvoiceListV2 extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'HRDF Invoices V2';
    protected static ?string $title = 'HRDF Invoice List V2';
    protected static string $view = 'filament.pages.hrdf-invoice-list-v2';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CrmHrdfInvoiceV2::query()
            )
            ->defaultSort('created_at', 'desc')
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
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->company_name),

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
                    ->label('TT Invoice')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ? 'View' : 'N/A')
                    ->action(
                        TableAction::make('viewTTInvoice')
                            ->modalHeading('TT Proforma Invoice Number')
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
                    ->weight('bold')
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
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
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
                ->label('CREATE HRDF INVOICE')
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->size(ActionSize::Large)
                ->modalHeading('CREATE HRDF INVOICE')
                ->modalWidth('2xl')
                ->form([
                    Select::make('handover_type')
                        ->label('SELECT TYPE')
                        ->options([
                            'SW' => 'SOFTWARE HANDOVER',
                            'HW' => 'HARDWARE HANDOVER',
                            'RW' => 'RENEWAL HANDOVER'
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('handover_id', null)),

                    Select::make('handover_id')
                        ->label('CHOOSE HANDOVER ID')
                        ->options(function (callable $get) {
                            $handoverType = $get('handover_type');
                            if (!$handoverType) return [];

                            $handovers = match ($handoverType) {
                                'SW' => SoftwareHandover::with(['lead'])
                                    ->where('status', 'New')
                                    ->limit(50) // Add limit to prevent too many results
                                    ->get(),
                                'HW' => HardwareHandoverV2::with(['lead'])
                                    ->where('status', 'New')
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

                            return $handovers->mapWithKeys(function ($handover) use ($handoverType) {
                                // Use the formatted handover ID from each model
                                $formattedId = $handover->formatted_handover_id ?? "ID_{$handover->id}";

                                // Try multiple ways to get company name
                                $companyName = $handover->company_name
                                    ?? $handover->lead?->company_name
                                    ?? $handover->lead?->companyDetail?->company_name
                                    ?? "Company ID: {$handover->lead_id}"
                                    ?? 'Unknown Company';

                                return [$handover->id => "{$formattedId} / {$companyName}"];
                            })->toArray();
                        })
                        ->required()
                        ->searchable()
                        ->placeholder('Select handover type first')
                        ->disabled(fn (callable $get) => !$get('handover_type')),

                    TextInput::make('tt_invoice_number')
                        ->label('TT PROFORMA INVOICE')
                        ->required()
                        ->rule('regex:/^[A-Z0-9,]+$/')
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

                    // Check if there are multiple proforma invoices based on handover type
                    $proformaInvoices = null;

                    if ($data['handover_type'] === 'RW') {
                        // For renewal handovers, use selected_quotation_ids
                        $proformaInvoices = $handover->selected_quotation_ids;
                        if (is_string($proformaInvoices)) {
                            $proformaInvoices = json_decode($proformaInvoices, true);
                        }
                    } else {
                        // For software and hardware handovers, use proforma_invoice_hrdf
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

                    // Handle both array and JSON string formats
                    if (is_string($proformaInvoices)) {
                        $proformaInvoices = json_decode($proformaInvoices, true);
                    }

                    // Check if it's a single proforma or multiple
                    $invoicesCreated = 0;

                    // If it's an array of IDs (like ["95","96"]), we need to fetch each proforma invoice
                    if (is_array($proformaInvoices) && !empty($proformaInvoices)) {
                        // Check if it's an array of IDs or an array of objects
                        $firstItem = $proformaInvoices[0];

                        if (is_string($firstItem) || is_numeric($firstItem)) {
                            // Array of IDs - need to fetch each proforma invoice data
                            foreach ($proformaInvoices as $proformaId) {
                                $invoiceNo = CrmHrdfInvoiceV2::generateInvoiceNumber();

                                CrmHrdfInvoiceV2::create([
                                    'invoice_no' => $invoiceNo,
                                    'invoice_date' => now(),
                                    'company_name' => $companyName,
                                    'handover_type' => $data['handover_type'],
                                    'handover_id' => $data['handover_id'],
                                    'tt_invoice_number' => $data['tt_invoice_number'],
                                    'subtotal' => 0, // Will be calculated dynamically
                                    'total_amount' => 0, // Will be calculated dynamically
                                    'status' => 'draft',
                                    'handover_data' => $handover->toArray(),
                                    'proforma_invoice_data' => (int)$proformaId // Store quotation ID as integer
                                ]);

                                $invoicesCreated++;
                            }
                        } elseif (is_array($firstItem)) {
                            // Array of objects - use existing logic
                            foreach ($proformaInvoices as $index => $proformaData) {
                                $invoiceNo = CrmHrdfInvoiceV2::generateInvoiceNumber();

                                CrmHrdfInvoiceV2::create([
                                    'invoice_no' => $invoiceNo,
                                    'invoice_date' => now(),
                                    'company_name' => $companyName,
                                    'handover_type' => $data['handover_type'],
                                    'handover_id' => $data['handover_id'],
                                    'tt_invoice_number' => $data['tt_invoice_number'],
                                    'subtotal' => $proformaData['subtotal'] ?? 0,
                                    'total_amount' => $proformaData['total'] ?? $proformaData['total_amount'] ?? 0,
                                    'status' => 'draft',
                                    'handover_data' => $handover->toArray(),
                                    'proforma_invoice_data' => $proformaData // Store the specific proforma data
                                ]);

                                $invoicesCreated++;
                            }
                        } else {
                            // Single item in array, treat as single proforma
                            $invoiceNo = CrmHrdfInvoiceV2::generateInvoiceNumber();

                            CrmHrdfInvoiceV2::create([
                                'invoice_no' => $invoiceNo,
                                'invoice_date' => now(),
                                'company_name' => $companyName,
                                'handover_type' => $data['handover_type'],
                                'handover_id' => $data['handover_id'],
                                'tt_invoice_number' => $data['tt_invoice_number'],
                                'subtotal' => 0,
                                'total_amount' => 0,
                                'status' => 'draft',
                                'handover_data' => $handover->toArray(),
                                'proforma_invoice_data' => (int)$firstItem // Store quotation ID as integer
                            ]);

                            $invoicesCreated = 1;
                        }
                    } else {
                        // Single proforma invoice (not in array)
                        $invoiceNo = CrmHrdfInvoiceV2::generateInvoiceNumber();

                        CrmHrdfInvoiceV2::create([
                            'invoice_no' => $invoiceNo,
                            'invoice_date' => now(),
                            'company_name' => $companyName,
                            'handover_type' => $data['handover_type'],
                            'handover_id' => $data['handover_id'],
                            'tt_invoice_number' => $data['tt_invoice_number'],
                            'subtotal' => 0,
                            'total_amount' => 0,
                            'status' => 'draft',
                            'handover_data' => $handover->toArray(),
                            'proforma_invoice_data' => (int)$proformaInvoices // Store quotation ID as integer
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
