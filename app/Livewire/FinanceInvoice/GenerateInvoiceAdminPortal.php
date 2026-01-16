<?php

namespace App\Livewire\FinanceInvoice;

use App\Models\FinanceInvoice;
use App\Models\ResellerHandover;
use App\Models\CrmInvoiceDetail;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\Attributes\On;

class GenerateInvoiceAdminPortal extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected $listeners = ['refresh-finance-invoice-tables' => '$refresh'];

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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FinanceInvoice::where('portal_type', 'admin')
                    ->with(['resellerHandover', 'creator'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('autocount_invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reseller_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reseller_commission_amount')
                    ->label('Amount')
                    ->money('MYR')
                    ->sortable(),
            ])
            ->actions([
                Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (FinanceInvoice $record): string => route('pdf.print-finance-invoice', $record))
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('crm_invoice_detail_id')
                            ->label('TT Invoice')
                            ->options(function () {
                                return CrmInvoiceDetail::query()
                                    ->with(['company', 'subscriber'])
                                    ->whereHas('company')
                                    ->pendingInvoices()
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        $companyName = $invoice->subscriber?->f_company_name ?? 'Unknown Company';
                                        $subscriberName = $invoice->company?->f_company_name ?? 'Available';
                                        $amount = number_format($invoice->f_sales_amount, 2);
                                        return [$invoice->f_id => "{$invoice->f_invoice_no} - {$companyName} - {$subscriberName} - {$invoice->f_currency} {$amount}"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $invoice = CrmInvoiceDetail::with(['company', 'subscriber'])->where('f_id', $state)->first();
                                    if ($invoice) {
                                        $set('reseller_name', strtoupper($invoice->subscriber?->f_company_name ?? ''));
                                        $set('subscriber_name', strtoupper($invoice->company?->f_company_name ?? ''));
                                        $set('reseller_commission_amount', $invoice->f_sales_amount ?? 0);
                                    }
                                }
                            }),

                        TextInput::make('autocount_invoice_number')
                            ->label('AutoCount Invoice Number')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('reseller_commission_amount')
                            ->label('Reseller Commission Amount')
                            ->required()
                            ->numeric()
                            ->prefix('RM')
                            ->step('0.01'),

                        TextInput::make('reseller_name')
                            ->label('Reseller Company Name')
                            ->disabled()
                            ->dehydrated(true),

                        TextInput::make('subscriber_name')
                            ->label('Subscriber Name')
                            ->disabled()
                            ->dehydrated(true),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $invoice = FinanceInvoice::create([
                                'reseller_handover_id' => null,
                                'autocount_invoice_number' => $data['autocount_invoice_number'],
                                'reseller_name' => $data['reseller_name'],
                                'subscriber_name' => $data['subscriber_name'],
                                'reseller_commission_amount' => $data['reseller_commission_amount'],
                                'portal_type' => 'admin',
                                'created_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('Invoice Generated')
                                ->success()
                                ->body('Finance invoice has been generated successfully.')
                                ->send();

                            $this->dispatch('refresh-finance-invoice-tables');

                            // Open PDF in new tab
                            $this->dispatch('open-url', url: route('pdf.print-finance-invoice', $invoice));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body('Failed to generate invoice: ' . $e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('300s');
    }

    public function render()
    {
        return view('livewire.finance-invoice.generate-invoice-admin-portal');
    }
}
