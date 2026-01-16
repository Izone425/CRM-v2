<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\FinanceInvoice;
use App\Models\ResellerHandover;
use App\Models\CrmInvoiceDetail;
use App\Models\AdminPortalInvoice;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class AdminPortalFinanceInvoiceNew extends Component implements HasForms, HasTable
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
            ->query(CrmInvoiceDetail::query()->with(['company', 'subscriber'])->pendingInvoices())
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('f_created_time')
                    ->label('Date')
                    ->formatStateUsing(fn ($state) => $state ? date('d M Y', strtotime($state)) : '-'),
                TextColumn::make('f_invoice_no')
                    ->label('TT Invoice')
                    ->searchable()
                    ->sortable()
                    ->url(function ($record) {
                        $aesKey = 'Epicamera@99';
                        try {
                            $encrypted = openssl_encrypt($record->f_id, "AES-128-ECB", $aesKey);
                            $encryptedBase64 = base64_encode($encrypted);
                            return 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('subscriber.f_company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? 'Available'))
                    ->tooltip(fn ($record) => strtoupper($record->subscriber?->f_company_name ?? 'Available'))
                    ->default('Available')
                    ->placeholder('Available'),
                TextColumn::make('company.f_company_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => 'Available')
                    ->tooltip(fn ($record) => strtoupper($record->company?->f_company_name ?? 'No subscriber information'))
                    ->default('Available')
                    ->placeholder('Available'),
                TextColumn::make('f_name')
                    ->label('Method')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->default('-')
                    ->placeholder('-'),
                TextColumn::make('f_currency')
                    ->label('Currency')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->placeholder('-'),
                TextColumn::make('f_sales_amount')
                    ->label('Amount')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->default('0.00')
                    ->placeholder('0.00'),
            ])
            ->actions([
                Action::make('update_autocount')
                    ->label('Update')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form([
                        Select::make('finance_invoice')
                            ->label('Select Finance Invoice')
                            ->options(function () {
                                return FinanceInvoice::where('portal_type', 'admin')
                                    ->where('status', 'new')
                                    ->pluck('fc_number', 'id');
                            })
                            ->searchable()
                            ->required(),
                        TextInput::make('autocount_invoice')
                            ->label('Autocount Invoice Number')
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {
                        try {
                            // Update ac_invoice
                            DB::connection('frontenddb')
                                ->table('ac_invoice')
                                ->where('f_id', $record->f_id)
                                ->limit(1)
                                ->update(['f_auto_count_inv' => $data['autocount_invoice']]);

                            // Get finance invoice details
                            $financeInvoice = FinanceInvoice::with('resellerHandover')->find($data['finance_invoice']);

                            // Get reseller and subscriber names
                            $resellerName = $financeInvoice?->resellerHandover?->reseller_name ?? '-';
                            $subscriberName = $financeInvoice?->resellerHandover?->subscriber_name ?? '-';

                            // Create AdminPortalInvoice record
                            AdminPortalInvoice::create([
                                'finance_invoice_id' => $data['finance_invoice'],
                                'reseller_name' => $resellerName,
                                'subscriber_name' => $subscriberName,
                                'tt_invoice' => $record->f_invoice_no,
                                'autocount_invoice' => $data['autocount_invoice'],
                            ]);

                            Notification::make()
                                ->title('Autocount invoice updated successfully')
                                ->success()
                                ->send();

                            $this->dispatch('refresh-finance-invoice-counts');
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to update autocount invoice')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.admin-portal-finance-invoice-new');
    }
}
