<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class CreateRanking extends Component 
{

    public $users;
    public $counter;

    public function mount(){
        $this->users = User::where("role_id","2")->select("id","name")->get()->toArray();
    }

    public function render()
    {
        return view('livewire.create-ranking');
    }
}
