<?php

namespace App\Livewire;

use App\Models\Invoice;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InvoiceTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable, InteractsWithForms;

    public $selectedUser;
    public $selectedMonth;

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser, $selectedMonth)
    {
        $this->selectedUser = $selectedUser === "" ? null : $selectedUser;
        $this->selectedMonth = $selectedMonth === "" ? null : $selectedMonth;

        session(['selectedUser' => $this->selectedUser]);
        session(['selectedMonth' => $this->selectedMonth]);

        $this->resetTable();
    }

    protected function getFilteredInvoicesQuery()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser', null);
        $this->selectedMonth = $this->selectedMonth ?? session('selectedMonth', null);

        $query = Invoice::query();

        if ($this->selectedUser !== null) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth !== null) {
            $query->whereMonth('invoice_date', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('invoice_date', Carbon::parse($this->selectedMonth)->year);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query($this->getFilteredInvoicesQuery())
            ->defaultSort('invoice_date', 'desc')
            ->heading('Invoice')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                TextColumn::make('company_name')->label('COMPANY NAME')->sortable()->searchable(),
                TextColumn::make('amount')
                    ->label('AMOUNT')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'RM ' . number_format($state, 2)),
                TextColumn::make('invoice_no')->label('INVOICE NO')->sortable(),
                TextColumn::make('invoice_date')
                    ->label('INVOICE DATE')
                    ->sortable()
                    ->dateTime('d M Y'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->form([
                        TextInput::make('company_name')->required(),
                        TextInput::make('amount')->numeric()->required(),
                        TextInput::make('invoice_no')->required(),
                        DatePicker::make('invoice_date')->required(),
                    ])
                    ->action(fn ($record, $data) => $record->update($data)),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('create')
                    ->label('Create New Invoice')
                    ->form([
                        TextInput::make('company_name')->required(),
                        TextInput::make('amount')->numeric()->required(),
                        TextInput::make('invoice_no')->required(),
                        DatePicker::make('invoice_date')->required(),
                    ])
                    ->action(function ($data) {
                        Invoice::create([
                            'company_name' => $data['company_name'],
                            'amount' => $data['amount'],
                            'invoice_no' => $data['invoice_no'],
                            'invoice_date' => $data['invoice_date'],
                            'salesperson' => auth()->user()->id, // Record salesperson automatically
                        ]);
                    })
                    ->visible(auth()->user()->role_id == 2), // Only for role_id = 2
            ]);
    }

    public function render()
    {
        return view('livewire.invoice-table');
    }
}
