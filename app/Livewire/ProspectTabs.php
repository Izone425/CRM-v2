<?php

namespace App\Livewire;

use Filament\Tables;
use Livewire\Component;
use App\Models\Lead; // Import your model for leads
use App\Models\AnotherModel; // Import any other models as necessary
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

class ProspectTabs extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    // Computed property to get the company name
    public function getCompanyNameProperty()
    {
        $lead = Lead::where('customer_id', $this->customerId)->first();
        return $lead ? $lead->company_name : 'N/A';
    }

    public function render()
    {
        return view('filament.pages.customer-tabs');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(lead::query())
            ->columns([
                TextColumn::make('id')->label('NO.'),
                TextColumn::make('updated_at')->label('DATE & TIME'),
                TextColumn::make('id')->label('NO.'),

            ])
            ->searchable()
            ->defaultSort('created_at', 'desc')
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
