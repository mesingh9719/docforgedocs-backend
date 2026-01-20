<?php

namespace App\Jobs;

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
    public function handle(): void
    {
        \Illuminate\Support\Facades\Mail::to($this->email)
            ->send(new \App\Mail\DocumentShared($this->document, $this->shareLink, $this->customMessage));
    }
}
