<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
        public string $name,
    ) {}

    public function build()
    {
        return $this->subject('Reset your KPI Dashboard password')
            ->view('emails.password-reset');
    }
}
