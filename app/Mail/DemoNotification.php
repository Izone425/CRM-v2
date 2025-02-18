<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DemoNotification extends Mailable
{
    public $emailContent;
    public $viewName;
    public $senderEmail;
    public $senderName;

    public function __construct($emailContent, $viewName, $senderEmail, $senderName)
    {
        $this->emailContent = $emailContent;
        $this->viewName = $viewName;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    public function build()
    {
        return $this->from($this->senderEmail, $this->senderName)
                    ->replyTo($this->senderEmail, $this->senderName) // Ensure replies go to the actual sender
                    ->view($this->viewName)
                    ->subject(
                        strtoupper($this->emailContent['lead']['demo_type']) . " | " .
                        strtoupper($this->emailContent['lead']['appointment_type']) . " | TIMETEC HR | " .
                        $this->emailContent['lead']['company']
                    )
                    ->with([
                        'lead' => $this->emailContent['lead'],
                        'leadOwnerName' => $this->emailContent['leadOwnerName'],
                    ]);
    }
}
