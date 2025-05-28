<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BDReferralClosure extends Mailable
{
    use Queueable, SerializesModels;

    public $emailContent;
    public $viewName;

    /**
     * Create a new message instance.
     */
    public function __construct($emailContent, $viewName)
    {
        $this->emailContent = $emailContent;
        $this->viewName = $viewName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = $this->emailContent['lead']['status'];
        $company = $this->emailContent['lead']['company_name'];

        return new Envelope(
            subject: "BD Referral Lead {$status}: {$company}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->viewName,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
