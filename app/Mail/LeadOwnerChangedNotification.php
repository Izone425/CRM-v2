<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class LeadOwnerChangedNotification extends Mailable
{
    public $content;
    public $viewName;
    public $fromEmail;

    public function __construct(array $content, string $fromEmail, string $viewName = 'emails.lead-owner-changed')
    {
        $this->content = $content;
        $this->fromEmail = $fromEmail;
        $this->viewName = $viewName;
    }

    public function build()
    {
        return $this->from($this->fromEmail, 'TimeTec CRM')
                    ->view($this->viewName)
                    ->subject('Lead Ownership Change | ' . $this->content['lead']['company'])
                    ->with([
                        'lead' => $this->content['lead'],
                        'previousOwnerName' => $this->content['previousOwnerName'],
                        'newOwnerName' => $this->content['newOwnerName'],
                        'rejected' => $this->content['rejected'] ?? false,  // Optional
                        'reason' => $this->content['reason'] ?? null,       // Optional
                    ]);
    }
}
