<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoverNotification extends Mailable
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
        $handoverId = $this->emailContent['handover_id'] ?? '250000';
        $companyName = $this->emailContent['company']['name'] ?? 'New Company';

        return new Envelope(
            subject: "SOFTWARE HANDOVER ID {$handoverId} | {$companyName}",
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
