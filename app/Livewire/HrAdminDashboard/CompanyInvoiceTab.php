<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\CrmHrdfInvoice;
use App\Models\Invoice;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class CompanyInvoiceTab extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public ?int $softwareHandoverId = null;
    public array $companyData = [];

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
    }

    public function table(Table $table): Table
    {
        $companyName = $this->companyData['company_name'] ?? '';

        return $table
            ->query(
                CrmHrdfInvoice::query()
                    ->when($this->softwareHandoverId, function (Builder $query) {
                        $query->where('handover_id', $this->softwareHandoverId)
                            ->where('handover_type', 'SW');
                    })
                    ->when($companyName, function (Builder $query) use ($companyName) {
                        $query->orWhere('company_name', 'like', "%{$companyName}%");
                    })
            )
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->defaultSort('invoice_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Paid' => 'Paid',
                        'Pending' => 'Pending',
                        'Cancel' => 'Cancel',
                    ])
                    ->placeholder('All Status'),
            ])
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('Y-m-d')
                    ->sortable()
                    ->default('-'),

                TextColumn::make('description')
                    ->label('Description')
                    ->default('TimeTec License Purchase')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Pending' => 'warning',
                        'Cancel' => 'danger',
                        default => 'gray',
                    })
                    ->default('Pending'),
            ])
            ->actions([
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => '#')
                    ->openUrlInNewTab(),
            ])
            ->striped();
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-invoice-tab');
    }
}
