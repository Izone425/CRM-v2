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
use Filament\Forms\Components\Grid;

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
            ->emptyState(fn () => view('components.empty-state-question'))
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
                    ->label('AutoCount Invoice Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('timetec_invoice_number')
                    ->label('TimeTec Invoice Number')
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
                    ->label('PDF')
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
                            ->label('Admin Portal')
                            ->options(function () {
                                return CrmInvoiceDetail::query()
                                    ->pendingInvoices()
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        $companyName = strtoupper($invoice->subscriber_name ?? 'Unknown Company');
                                        $subscriberName = strtoupper($invoice->company_name ?? 'Not Available');
                                        $amount = number_format($invoice->f_total_amount, 2);
                                        return [$invoice->f_id => "{$invoice->f_invoice_no} - {$companyName} - {$subscriberName} - {$invoice->f_currency} {$amount}"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if ($state) {
                                    $invoice = CrmInvoiceDetail::query()
                                        ->pendingInvoices()
                                        ->havingRaw('MIN(crm_invoice_details.f_id) = ?', [$state])
                                        ->first();
                                    
                                    if ($invoice) {
                                        $set('reseller_name', strtoupper($invoice->subscriber_name ?? ''));
                                        $set('subscriber_name', strtoupper($invoice->company_name ?? ''));
                                        $set('timetec_invoice_number', strtoupper($invoice->f_invoice_no ?? ''));
                                    }
                                }
                            }),

                        TextInput::make('timetec_invoice_number')
                            ->label('TimeTec Invoice Number')
                            ->disabled()
                            ->dehydrated(true),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('reseller_name')
                                    ->label('Reseller Name')
                                    ->disabled()
                                    ->dehydrated(true),

                                TextInput::make('autocount_invoice_number')
                                    ->label('AutoCount Invoice Number')
                                    ->required()
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
                                    ->minLength(13)
                                    ->maxLength(13),
                            ]),
                    
                        Grid::make(2)
                            ->schema([
                                TextInput::make('subscriber_name')
                                    ->label('Subscriber Name')
                                    ->disabled()
                                    ->dehydrated(true),

                                TextInput::make('reseller_commission_amount')
                                    ->label('Reseller Commission Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('RM')
                                    ->step('0.01'),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $invoice = FinanceInvoice::create([
                                'reseller_handover_id' => null,
                                'autocount_invoice_number' => $data['autocount_invoice_number'],
                                'timetec_invoice_number' => $data['timetec_invoice_number'] ?? null,
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
                            $this->js('window.open("' . route('pdf.print-finance-invoice', $invoice) . '", "_blank")');
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
