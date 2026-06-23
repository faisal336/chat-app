<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Sync, not queued — signup is interactive and welcome email arriving
// 60 seconds later (after cron) feels broken. ~1 sec extra on the signup
// request is a fair trade.
class WelcomeEmail extends Mailable
{
    use SerializesModels;

    /**
     * @param  User    $user     The recipient.
     * @param  ?string $tempPin  Only set when an admin creates a user with a PIN
     *                           we want to communicate; self-signup leaves null.
     */
    public function __construct(public User $user, public ?string $tempPin = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
                'tempPin' => $this->tempPin,
            ],
        );
    }
}
