<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDocumentEmail implements ShouldQueue
{
    use Queueable;

    protected $email;
    protected $document;
    protected $shareLink;
    protected $customMessage;

    /**
     * Create a new job instance.
     */
    public function __construct($email, $document, $shareLink, $customMessage = null)
    {
        $this->email = $email;
        $this->document = $document;
        $this->shareLink = $shareLink;
        $this->customMessage = $customMessage;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\NotificationService $notificationService): void
    {
        // Try to identify sender name vs recipient name
        // The service needs proper names
        // document->created_by user name is sender
        $senderName = $this->document->creator ? $this->document->creator->name : config('app.name');

        $recipientUser = User::where('email', $this->email)->first();
        $recipientName = $recipientUser ? $recipientUser->name : 'Recipient';

        $notificationService->sendDocumentShared(
            $this->email,
            $recipientName,
            $senderName,
            $this->document->name,
            $this->shareLink
        );
    }
}
