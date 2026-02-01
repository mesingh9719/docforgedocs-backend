<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignatureRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $signerName;
    public $senderName;
    public $documentName;
    public $signLink;

    public function __construct($signerName, $senderName, $documentName, $signLink)
    {
        $this->signerName = $signerName;
        $this->senderName = $senderName;
        $this->documentName = $documentName;
        $this->signLink = $signLink;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Signature Request: ' . $this->documentName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signature-request',
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
