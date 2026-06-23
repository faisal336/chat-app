<?php

namespace App\Livewire\Auth;

use App\Mail\PinResetIssuedEmail;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Reset PIN')]
class ForgotPin extends Component
{
    public const MAX_ATTEMPTS_PER_IP_PER_HOUR = 5;

    public const MAX_ATTEMPTS_PER_USER_PER_HOUR = 3;

    #[Validate('required|string|max:32')]
    public string $username = '';

    public bool $submitted = false;

    public function submit(AuditService $audit): void
    {
        $this->validate();

        $ipKey = 'forgot-pin-ip|'.request()->ip();
        $userKey = 'forgot-pin-user|'.strtolower($this->username);

        // IP-level cap (defense against scripted enumeration / spam)
        if (RateLimiter::tooManyAttempts($ipKey, self::MAX_ATTEMPTS_PER_IP_PER_HOUR)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            throw ValidationException::withMessages([
                'username' => "Too many requests. Try again in {$minutes} minute(s).",
            ]);
        }

        // Per-username cap (so an attacker can't hammer one specific user's inbox)
        if (RateLimiter::tooManyAttempts($userKey, self::MAX_ATTEMPTS_PER_USER_PER_HOUR)) {
            // Don't reveal that this username is rate-limited — show the generic success
            $this->submitted = true;

            return;
        }

        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($userKey, 3600);

        $user = User::where('username', $this->username)->first();

        // Quietly succeed for non-existent / disabled / archived / no-email accounts.
        // Never enumerate. The user always sees the same "if there's an email, check it" page.
        if ($user
            && $user->isActive()
            && ! $user->isSuperAdmin()      // super admin must reset via SSH / DB — not over email
            && $user->email
        ) {
            $tempPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user->setPin($tempPin);
            // Don't force the user to recreate the PIN — they can keep this one.
            $user->save();

            try {
                Mail::to($user->email)->queue(new PinResetIssuedEmail($user, $tempPin));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    'PinResetIssuedEmail dispatch failed in self-service flow: '.$e->getMessage()
                );
            }

            $audit->log('pin_reset.self_serviced', $user, [
                'via' => 'email',
                'ip' => request()->ip(),
            ], $user->id);
        }

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-pin');
    }
}
