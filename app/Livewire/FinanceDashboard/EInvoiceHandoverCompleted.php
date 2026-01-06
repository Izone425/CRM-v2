<?php

namespace App\Livewire\FinanceDashboard;

use App\Models\EInvoiceHandover;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class EInvoiceHandoverCompleted extends Component implements HasForms, HasTable
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

    public function render()
    {
        return view('livewire.finance-dashboard.e-invoice-handover-completed');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EInvoiceHandover::query()->where('status', 'Completed')->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('project_code')
                    ->label('ID')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewEInvoiceDetails')
                            ->modalHeading(function (EInvoiceHandover $record) {
                                return 'E-Invoice Details - ' . $record->project_code;
                            })
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (EInvoiceHandover $record) {
                                return view('components.einvoice-handover-details', [
                                    'record' => $record
                                ]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Completed' => new HtmlString('<span style="background-color: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                        default => new HtmlString('<span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                    }),
            ])
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->poll('300s');
    }
}
