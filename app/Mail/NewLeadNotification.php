<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewLeadNotification extends Mailable
{
    public $content;
    public $viewName; // This holds the Blade template to use

    public function __construct($content, $viewName)
    {
        $this->content = $content;
        $this->viewName = $viewName; // Set the view name dynamically
    }

    public function build()
    {
        return $this->view($this->viewName) // Use the selected template dynamically
                    ->subject("New HR Lead | TimeTec ")
                    ->with([
                        'lead' => $this->content['lead'],
                        'leadOwnerName' => $this->content['leadOwnerName'], // Lead Owner/Manager Name
                        'remark' => $this->content['remark'],
                        'formatted_products' => $this->content['formatted_products'],
                    ]);
    }
}
