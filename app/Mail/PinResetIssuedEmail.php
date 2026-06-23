<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PinResetIssuedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
