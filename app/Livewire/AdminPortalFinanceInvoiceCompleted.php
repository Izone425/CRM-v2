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
            ->query(FinanceInvoice::query()->where('portal_type', 'admin')->where('status', 'completed')->orderBy('created_at', 'desc'))
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
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.admin-portal-finance-invoice-completed');
    }
}
