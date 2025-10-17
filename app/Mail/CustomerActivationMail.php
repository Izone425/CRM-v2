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
    public $email;
    public $password;
    public $name;

    public function __construct(Customer $customer, $email, $password, $name)
    {
        $this->customer = $customer;
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
    }

    public function build()
    {
        return $this->subject('Your TimeTec CRM Customer Portal Access Credentials')
                   ->view('emails.customer-activation')
                   ->with([
                        'customer' => $this->customer,
                        'email' => $this->email,
                        'password' => $this->password,
                        'name' => $this->name,
                        'loginUrl' => route('customer.login')
                   ]);
    }
}
