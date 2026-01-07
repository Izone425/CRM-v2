<?php

namespace App\Livewire\FinanceDashboard;

use App\Models\CompanyDetail;
use App\Models\EInvoiceHandover;
use App\Models\Subsidiary;
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

class EInvoiceHandoverRejected extends Component implements HasForms, HasTable
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
        return view('livewire.finance-dashboard.e-invoice-handover-rejected');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EInvoiceHandover::query()->where('status', 'Rejected')->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('project_code')
                    ->label('ID')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewEInvoiceDetails')
                            ->modalHeading(false)
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
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $displayName = $state;
                        $company = null;

                        // Check if there's a subsidiary_id and get subsidiary company name
                        if (!empty($record->subsidiary_id)) {
                            $subsidiary = Subsidiary::find($record->subsidiary_id);
                            if ($subsidiary) {
                                $displayName = $subsidiary->company_name;
                                $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                            }
                        } else {
                            // Fall back to regular company lookup
                            $company = CompanyDetail::where('company_name', $state)->first();

                            if (!empty($record->lead_id)) {
                                $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                            }
                        }

                        if ($company) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($displayName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $displayName . '
                                </a>');
                        }

                        return "<span title='{$displayName}'>{$displayName}</span>";
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Rejected' => new HtmlString('<span style="background-color: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                        default => new HtmlString('<span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                    }),
            ])
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->poll('300s');
    }
}
