<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\Customer;
use Carbon\Carbon;
use App\Mail\CustomerResetPassword;

class ForgotPassword extends Component
{
    public $email = '';
    public $successMessage = '';

    protected $rules = [
        'email' => 'required|email',
    ];

    public function sendResetLink()
    {
        $this->validate();

        // Check if the customer exists
        $customer = Customer::where('email', $this->email)->first();

        if (!$customer) {
            $this->addError('email', 'We can\'t find a customer with that email address.');
            return;
        }

        // Create a new token
        $token = Str::random(64);

        // Store the token in the password_resets table
        DB::table('password_resets')->where('email', $this->email)->delete();

        DB::table('password_resets')->insert([
            'email' => $this->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        // Send reset password email
        Mail::to($this->email)->send(new CustomerResetPassword($customer, $token));

        $this->successMessage = 'We have emailed your password reset link.';
    }

    public function render()
    {
        return view('livewire.customer.forgot-password');
    }
}
