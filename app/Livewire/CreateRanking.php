<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;

class CreateRanking extends Component implements HasForms
{

    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {

        $users= User::where("role_id","2")->get();
        $userCount = count($users);
        $options = collect(range(1, $userCount)) // Generate options 1 to N dynamically
        ->mapWithKeys(fn ($num) => [$num => "No.{$num}"])
        ->toArray();

        return $form->schema([
            Section::make('Rank Users')
                ->schema([
                    Grid::make(3)
                        ->schema(
                            $users->map(function ($user) use ($options) {
                                return Select::make("rank_{$user->id}")
                                    ->label($user->name)
                                    ->options($options)
                                    ->required()
                                    ->live()
                                    ->rules([
                                        function ($attribute, $value, $fail) {
                                            // Get all other rank values except current field
                                            $otherRanks = collect($this->data)
                                                ->except($attribute)
                                                ->filter(function ($v, $k) {
                                                    return str_starts_with($k, 'rank_');
                                                })
                                                ->values();
    
                                            // Check if value exists in other ranks
                                            if ($otherRanks->contains($value)) {
                                                $fail("This rank is already assigned to another user.");
                                            }
                                        },
                                    ]);
                            })->toArray()
                        )
                ]),
        ])->statePath('data');
    }



    public function create(): void
    {       
        dd($this->form->getState());
    }


    public function render()
    {
        return view('livewire.create-ranking');
    }
}
