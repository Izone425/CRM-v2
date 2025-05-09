<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Customer;

class CustomerResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $token;

    public function __construct(Customer $customer, $token)
    {
        $this->customer = $customer;
        $this->token = $token;
    }

    public function build()
    {
        $resetLink = route('customer.password.reset', $this->token);

        return $this->subject('Reset Your TimeTec CRM Password')
                   ->view('emails.customer-reset-password')
                   ->with([
                        'resetLink' => $resetLink,
                        'name' => $this->customer->name,
                   ]);
    }
}
