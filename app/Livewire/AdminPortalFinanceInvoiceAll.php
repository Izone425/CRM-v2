<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\FinanceInvoice;
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
use Filament\Forms\Components\Textarea;
use Livewire\Attributes\On;

class AdminPortalFinanceInvoiceAll extends Component implements HasForms, HasTable
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
            ->query(FinanceInvoice::query()->where('portal_type', 'admin')->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('fc_number')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->action(
                        Action::make('view_details')
                            ->modalHeading(fn (FinanceInvoice $record) => $record->fc_number)
                            ->modalContent(fn (FinanceInvoice $record) => view('filament.modals.finance-invoice-details', ['record' => $record]))
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
                TextColumn::make('resellerHandover.timetec_proforma_invoice')
                    ->label('TT Invoice')
                    ->searchable()
                    ->url(fn (FinanceInvoice $record) => $record->resellerHandover?->invoice_url)
                    ->openUrlInNewTab()
                    ->color('primary'),
                TextColumn::make('autocount_invoice_number')
                    ->label('Autocount Invoice')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'new',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords($state)),
            ])
            ->actions([
                Action::make('complete_task')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (FinanceInvoice $record) => $record->status === 'new')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Task')
                    ->modalDescription('Are you sure you want to complete this task? This will update the autocount invoice number in the system.')
                    ->modalSubmitActionLabel('Yes, Complete')
                    ->action(function (FinanceInvoice $record) {
                        // Get the reseller handover and timetec proforma invoice
                        $resellerHandover = $record->resellerHandover;

                        if ($resellerHandover && $resellerHandover->timetec_proforma_invoice && $record->autocount_invoice_number) {
                            try {
                                // Find the f_id from ac_invoice table
                                $acInvoice = \Illuminate\Support\Facades\DB::connection('frontenddb')
                                    ->table('ac_invoice')
                                    ->where('f_invoice_no', $resellerHandover->timetec_proforma_invoice)
                                    ->first(['f_id']);

                                if ($acInvoice && $acInvoice->f_id) {
                                    // Update the autocount invoice number
                                    \Illuminate\Support\Facades\DB::connection('frontenddb')
                                        ->table('ac_invoice')
                                        ->where('f_id', $acInvoice->f_id)
                                        ->limit(1)
                                        ->update(['f_auto_count_inv' => $record->autocount_invoice_number]);
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to update autocount invoice', [
                                    'error' => $e->getMessage(),
                                    'finance_invoice_id' => $record->id
                                ]);
                            }
                        }

                        // Update finance invoice status
                        $record->update([
                            'status' => 'completed',
                        ]);

                        Notification::make()
                            ->title('Task completed successfully')
                            ->body('Autocount invoice number has been updated in the system.')
                            ->success()
                            ->send();

                        $this->dispatch('refresh-finance-invoice-counts');
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.admin-portal-finance-invoice-all');
    }
}
