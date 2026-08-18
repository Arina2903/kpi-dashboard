<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlatformPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
    ) {}

    public function build()
    {
        return $this->subject('Reset your Performix password')
            ->view('emails.platform-password-reset');
    }
}
