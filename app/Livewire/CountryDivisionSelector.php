<?php

namespace App\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Filament\Forms\Form;
use Filament\Forms;
use Livewire\Component;

class CountryDivisionSelector extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function render(): View
    {
        return view('livewire.country-division-selector');
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\Grid::make()
                ->columns(2)
                ->visible(fn () => auth()->user()->role_id == 3)
                ->schema([
                    Forms\Components\Select::make('country')
                        ->hiddenLabel()
                        ->placeholder('Country')
                        ->live()
                        // ->afterStateUpdated(function (Forms\Components\Select $component, $state) {
                        //     // Reset the division when country changes
                        //     $this->data['division'] = null;
                        // })
                        ->default('Malaysia')
                        ->options([
                            'Malaysia' => 'Malaysia',
                            'USA' => 'USA',
                        ]),

                    Forms\Components\Select::make('division')
                        ->hiddenLabel()
                        ->placeholder('Division')
                        ->live()
                        ->default('TimeTec HR')
                        ->options([
                                    'TimeTec HR' => 'TimeTec HR',
                                    'TimeTec Parking' => 'TimeTec Parking',
                                    'TimeTec Building' => 'TimeTec Building',
                                    'TimeTec Security' => 'TimeTec Security',
                                    'i-Neighbour' => 'i-Neighbour',
                                    'FingerTec' => 'FingerTec',
                        ]),
                ]),
        ])
        ->columns(1) // The outer form is single column to allow the Grid to control the layout
        ->inlineLabel();
    }
}
