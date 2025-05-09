<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Customer;
use Carbon\Carbon;

class ResetPassword extends Component
{
    public $token;
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ];

    public function mount($token)
    {
        $this->token = $token;

        // Get email from token
        $resetRecord = DB::table('password_resets')
            ->where('token', $token)
            ->first();

        if ($resetRecord) {
            $this->email = $resetRecord->email;
        }
    }

    public function resetPassword()
    {
        $this->validate();

        // Find the reset record
        $resetRecord = DB::table('password_resets')
            ->where('token', $this->token)
            ->where('email', $this->email)
            ->first();

        // Check if token exists and is not expired (24 hours)
        if (!$resetRecord || Carbon::parse($resetRecord->created_at)->addHours(24)->isPast()) {
            $this->addError('email', 'This password reset link is invalid or has expired.');
            return;
        }

        // Update the customer's password
        $customer = Customer::where('email', $this->email)->first();

        if (!$customer) {
            $this->addError('email', 'We can\'t find a customer with that email address.');
            return;
        }

        $customer->update([
            'password' => Hash::make($this->password),
        ]);

        // Delete the reset record
        DB::table('password_resets')->where('email', $this->email)->delete();

        // Redirect to login with success message
        session()->flash('success', 'Your password has been reset successfully. You can now log in with your new password.');

        return redirect()->route('customer.login');
    }

    public function render()
    {
        return view('livewire.customer.reset-password');
    }
}
