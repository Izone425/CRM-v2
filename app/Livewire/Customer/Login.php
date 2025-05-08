<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Carbon\Carbon;
use App\Models\Customer;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')] // Add this attribute for Livewire 3
class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::guard('customer')->attempt([
            'email' => $this->email,
            'password' => $this->password
        ], $this->remember)) {
            // Update last login timestamp
            $customer = Customer::where('email', $this->email)->first();
            if ($customer) {
                $customer->last_login_at = Carbon::now();
                $customer->save();
            }

            return redirect()->intended(route('customer.dashboard'));
        }

        $this->addError('email', 'The provided credentials do not match our records.');
    }

    public function render()
    {
        return view('livewire.customer.login');
    }
}
