<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\FinanceInvoice;
use App\Models\ResellerHandover;
use App\Models\AdminPortalInvoice;
use App\Models\CrmInvoiceDetail;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class AdminPortalFinanceInvoiceCompleted extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

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

    #[On('refresh-adminrepair-tables')]
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

    public function table(Table $table): Table
    {
        return $table
            ->query(AdminPortalInvoice::query()->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->action(
                        Action::make('view_details')
                            ->modalHeading(fn (AdminPortalInvoice $record) => $record->formatted_id ?? $record->tt_invoice)
                            ->modalContent(fn (AdminPortalInvoice $record) => view('filament.modals.finance-invoice-details', ['record' => $record]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalWidth('2xl')
                    )
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Invoice Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('reseller_name')
                    ->label('Reseller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tt_invoice')
                    ->label('TT Invoice')
                    ->searchable()
                    ->sortable()
                    ->url(function ($record) {
                        $aesKey = 'Epicamera@99';
                        try {
                            // Get f_id from crm_invoice_details
                            $invoiceDetail = CrmInvoiceDetail::where('f_invoice_no', $record->tt_invoice)->first();
                            if (!$invoiceDetail) {
                                return null;
                            }

                            $encrypted = openssl_encrypt($invoiceDetail->f_id, "AES-128-ECB", $aesKey);
                            $encryptedBase64 = base64_encode($encrypted);
                            return 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('autocount_invoice')
                    ->label('Autocount Invoice')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.admin-portal-finance-invoice-completed');
    }
}
