<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SmartCityLeadNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $lead;
    public $products;

    /**
     * Create a new message instance.
     *
     * @param $lead
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
        $this->products = json_decode($lead->products, true);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('PM Lead | TimeTec')
                    ->view('emails.property_management_lead_notification')
                    ->with([
                        'lead' => $this->lead,
                        'products' => $this->products,
                    ]);
    }
}
