<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Customer;

class CustomerActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $token;
    public $name;

    public function __construct(Customer $customer, $token, $name)
    {
        $this->customer = $customer;
        $this->token = $token;
        $this->name = $name;
        info( $this->name);
    }

    public function build()
    {
        $activationLink = route('customer.activate', $this->token);

        return $this->subject('Activate Your TimeTec CRM Account')
                   ->view('emails.customer-activation')
                   ->with([
                        'activationLink' => $activationLink,
                        'name' => $this->name,
                   ]);
    }
}
