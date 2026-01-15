<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandover;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use App\Services\InvoiceOcrService;
use Filament\Forms\Set;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ResellerHandoverPendingTimetecInvoice extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];

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

    #[On('refresh-leadowner-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function openFilesModal($recordId)
    {
        $handover = ResellerHandover::find($recordId);

        if ($handover) {
            $this->selectedHandover = $handover;
            $this->handoverFiles = $handover->getCategorizedFilesForModal();

            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
        $this->handoverFiles = [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerHandover::query()->where('status', 'pending_timetec_invoice')->orderBy('confirmed_proceed_at', 'desc'))
            ->columns([
                TextColumn::make('fb_id')
                    ->label('FB ID')
                    ->searchable()
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandover $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('reseller_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('confirmed_proceed_at')
                    ->label('Confirmed At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'pending_confirmation',
                        'info' => 'pending_timetec_invoice',
                        'success' => 'completed',
                        'danger' => 'rejected',
                        'secondary' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
            ])
            ->actions([
                Action::make('complete_task')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->fillForm(function (ResellerHandover $record) {
                        // Check if there's a finance invoice for this handover
                        $financeInvoice = \App\Models\FinanceInvoice::where('reseller_handover_id', $record->id)
                            ->latest()
                            ->first();

                        $formData = [];

                        // If finance invoice exists, pre-fill the reseller_invoice field with the PDF path
                        if ($financeInvoice) {
                            $invoiceFilename = 'FI_' . $financeInvoice->fc_number . '_' .
                                \Illuminate\Support\Str::upper(\Illuminate\Support\Str::replace('-', '_', \Illuminate\Support\Str::slug($financeInvoice->reseller_name))) . '.pdf';

                            // Check if the file exists in finance-invoices directory
                            $filePath = 'finance-invoices/' . $invoiceFilename;
                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                                $formData['reseller_invoice'] = [$filePath];
                            }
                        }

                        return $formData;
                    })
                    ->form([
                        FileUpload::make('autocount_invoice')
                            ->label('Autocount Invoice')
                            ->required()
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')
                            ->directory('reseller-handover/autocount-invoices')
                            ->maxSize(10240)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    return;
                                }

                                try {
                                    $ocrService = app(InvoiceOcrService::class);
                                    $filePaths = [];

                                    if (is_array($state)) {
                                        foreach ($state as $file) {
                                            if ($file instanceof TemporaryUploadedFile) {
                                                $filePaths[] = $file->getRealPath();
                                            }
                                        }
                                    } elseif ($state instanceof TemporaryUploadedFile) {
                                        $filePaths[] = $state->getRealPath();
                                    }

                                    if (!empty($filePaths)) {
                                        $invoiceNumber = $ocrService->extractInvoiceNumberFromMultipleFiles($filePaths);

                                        if ($invoiceNumber) {
                                            $set('autocount_invoice_number', $invoiceNumber);

                                            Notification::make()
                                                ->title('Invoice number detected')
                                                ->body("Found: {$invoiceNumber}")
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('No invoice number detected')
                                                ->body('Please enter manually')
                                                ->warning()
                                                ->send();
                                        }
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('OCR failed in ResellerHandover', [
                                        'error' => $e->getMessage()
                                    ]);

                                    Notification::make()
                                        ->title('OCR scan failed')
                                        ->body('Please enter invoice number manually')
                                        ->warning()
                                        ->send();
                                }
                            })
                            ->live(),
                        FileUpload::make('reseller_invoice')
                            ->label('Sample Reseller Invoice')
                            ->required()
                            ->disabled()
                            ->dehydrated(true)
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')
                            ->openable()
                            ->directory('reseller-handover/reseller-invoices')
                            ->maxSize(10240),
                        TextInput::make('autocount_invoice_number')
                            ->label('Autocount Invoice Number')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Auto-detected from invoice upload or enter manually'),
                        Radio::make('reseller_option')
                            ->label('Reseller Option')
                            ->required()
                            ->options([
                                'reseller_normal_invoice_with_payment_slip' => 'RESELLER NORMAL INVOICE + PAYMENT SLIP',
                                'reseller_normal_invoice' => 'RESELLER NORMAL INVOICE',
                            ])
                            ->default('reseller_normal_invoice_with_payment_slip'),
                    ])
                    ->action(function (ResellerHandover $record, array $data) {
                        $record->update([
                            'autocount_invoice' => $data['autocount_invoice'],
                            'reseller_invoice' => $data['reseller_invoice'],
                            'autocount_invoice_number' => $data['autocount_invoice_number'],
                            'reseller_option' => $data['reseller_option'],
                            'status' => 'pending_reseller_invoice',
                            'completed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Task completed successfully')
                            ->success()
                            ->send();

                        $this->dispatch('refresh-leadowner-tables');
                    })
                    ->modalHeading('Complete Task')
                    ->modalButton('Complete')
                    ->modalWidth('2xl'),
            ])
            ->defaultSort('confirmed_proceed_at', 'desc');
    }

    public function render()
    {
        return view('livewire.reseller-handover-pending-timetec-invoice');
    }
}
