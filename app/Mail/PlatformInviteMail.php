<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlatformInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $acceptUrl,
        public string $name,
        public string $companyName,
        public string $roleLabel,
    ) {}

    public function build()
    {
        return $this->subject("You're invited to {$this->companyName} on Performix")
            ->view('emails.platform-invite');
    }
}
