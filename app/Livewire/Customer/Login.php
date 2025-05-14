<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Carbon\Carbon;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    public $loginError = '';

    // Override mount to clear error on page load
    public function mount()
    {
        $this->loginError = '';
    }

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    // This approach should be more reliable
    public function render()
    {
        return view('livewire.customer.login');
    }

    public function login()
    {
        try {
            Log::info('Login attempt', ['email' => $this->email]);

            $validatedData = $this->validate();

            $credentials = [
                'email' => $this->email,
                'password' => $this->password
            ];

            if (Auth::guard('customer')->attempt($credentials, $this->remember)) {
                Log::info('Authentication successful');

                $customer = Customer::where('email', $this->email)->first();
                if ($customer) {
                    $customer->last_login_at = Carbon::now();
                    $customer->save();
                }

                return redirect()->intended(route('customer.dashboard'));
            } else {
                Log::info('Authentication failed');

                session()->flash('error', 'Invalid email or password.');
                $this->password = '';

                // This return is important - it stops execution
                return;
            }
        } catch (\Exception $e) {
            Log::error('Login exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'An error occurred: ' . $e->getMessage());
            return;
        }
    }
}
