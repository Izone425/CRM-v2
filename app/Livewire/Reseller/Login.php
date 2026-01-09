<?php

namespace App\Livewire\Reseller;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Carbon\Carbon;
use App\Models\ResellerV2;
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

    public function render()
    {
        return view('livewire.reseller.login');
    }

    public function login()
    {
        try {
            Log::info('Reseller login attempt', ['email' => $this->email]);

            $validatedData = $this->validate();

            $credentials = [
                'email' => $this->email,
                'password' => $this->password
            ];

            if (Auth::guard('reseller')->attempt($credentials, $this->remember)) {
                Log::info('Reseller authentication successful');

                $reseller = ResellerV2::where('email', $this->email)->first();
                if ($reseller) {
                    $reseller->last_login_at = Carbon::now();
                    $reseller->save();
                }

                return redirect()->intended(route('reseller.dashboard'));
            } else {
                Log::info('Reseller authentication failed');

                session()->flash('error', 'Invalid email or password.');
                $this->password = '';

                return;
            }
        } catch (\Exception $e) {
            Log::error('Reseller login exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'An error occurred: ' . $e->getMessage());
            return;
        }
    }
}
