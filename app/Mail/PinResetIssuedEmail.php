<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Intentionally NOT queueable — forgot-PIN is interactive (user is on the
// reset page waiting for the email). Sending sync avoids dependency on cron
// and gives instant delivery for ~1 sec of extra request time.
class PinResetIssuedEmail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user, public string $tempPin) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your temporary PIN — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pin-reset',
            with: [
                'user' => $this->user,
                'tempPin' => $this->tempPin,
            ],
        );
    }
}
