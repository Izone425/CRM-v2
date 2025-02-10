<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SalespersonNotification extends Mailable
{
    public $content;
    public $fromEmail;
    public $fromName;
    public $viewName; // This holds the Blade template to use

    public function __construct($content, $fromEmail, $fromName, $viewName)
    {
        $this->content = $content;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->viewName = $viewName; // Set the view name dynamically
    }

    public function build()
    {
        return $this->from($this->fromEmail, $this->fromName) // Set the sender email and name here dynamically
                    ->view($this->viewName) // Use the selected template dynamically
                    ->subject("NEW LEADS | RFQ | " . $this->content['lead']['company'])
                    ->with([
                        'lead' => $this->content['lead'],
                        'salespersonName' => $this->content['salespersonName'],
                        'leadOwnerName' => $this->content['leadOwnerName'], // Lead Owner/Manager Name
                        'remark' => $this->content['remark'],
                        'formatted_products' => $this->content['formatted_products'],
                    ]);
    }
}

