<?php

namespace App\Livewire;

use App\Models\ProformaInvoice;
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

class ProformaInvoiceTable extends Component implements HasForms, HasTable
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

        $query = ProformaInvoice::query();

        if ($this->selectedUser !== null) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth !== null) {
            $query->whereMonth('created_at', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('created_at', Carbon::parse($this->selectedMonth)->year);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query($this->getFilteredInvoicesQuery())
            ->defaultSort('created_at', 'desc')
            ->heading('Proforma Invoice')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->rowIndex(),
                TextColumn::make('company_name')->label('COMPANY NAME')->sortable()->searchable(),
                TextColumn::make('amount')
                    ->label('AMOUNT')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'RM ' . number_format($state, 2)),
                TextColumn::make('inv_type')->label('INV TYPE'),
                TextColumn::make('remark')->label('REMARK'),
                TextColumn::make('created_at')
                    ->label('CREATED AT')
                    ->sortable()
                    ->dateTime('d M Y'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->form([
                        TextInput::make('company_name')->required(),
                        TextInput::make('amount')->numeric()->required(),
                        Select::make('inv_type')
                            ->options([
                                'HRDF' => 'HRDF',
                                'NON HRDF' => 'NON HRDF',
                            ]),
                        TextInput::make('remark'),
                    ])
                    ->action(fn ($record, $data) => $record->update($data)),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('create')
                    ->label('Create New Proforma Invoice')
                    ->form([
                        TextInput::make('company_name')->required(),
                        TextInput::make('amount')->numeric()->required(),
                        Select::make('inv_type')
                            ->options([
                                'HRDF' => 'HRDF',
                                'NON HRDF' => 'NON HRDF',
                            ]),
                        TextInput::make('remark'),
                    ])
                    ->action(function ($data) {
                        ProformaInvoice::create([
                            'company_name' => $data['company_name'],
                            'amount' => $data['amount'],
                            'inv_type' => $data['inv_type'],
                            'remark' => $data['remark'],
                            'salesperson' => auth()->user()->id, // Store auth user ID
                        ]);
                    })
                    ->visible(auth()->user()->role_id == 2), // Only visible for role_id = 2
            ]);
    }

    public function render()
    {
        return view('livewire.proforma-invoice-table');
    }
}
