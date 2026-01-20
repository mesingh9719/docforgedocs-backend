<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentShared extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $customMessage;
    public $shareLink;

    /**
     * Create a new message instance.
     */
    public function __construct($document, $shareLink, $customMessage = null)
    {
        $this->document = $document;
        $this->shareLink = $shareLink;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Shared: ' . $this->document->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.document-shared',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->document->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->document->pdf_path)) {
            return [
                \Illuminate\Mail\Mailables\Attachment::fromPath(
                    \Illuminate\Support\Facades\Storage::disk('public')->path($this->document->pdf_path)
                )->as($this->document->name . '.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
