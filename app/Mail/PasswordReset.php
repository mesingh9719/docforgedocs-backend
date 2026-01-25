<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $reset_link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $reset_link)
    {
        $this->name = $name;
        $this->reset_link = $reset_link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Reset Password Notification')
            ->view('emails.password_reset');
    }
}
