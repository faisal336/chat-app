<?php

namespace App\Livewire\Auth;

use App\Models\PinResetRequest;
use App\Models\User;
use App\Services\AuditService;
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
    #[Validate('required|string|max:32')]
    public string $username = '';

    #[Validate('nullable|string|max:280')]
    public string $reason = '';

    public bool $submitted = false;

    public function submit(AuditService $audit): void
    {
        $this->validate();

        $key = 'forgot-pin|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Too many requests. Try again later.',
            ]);
        }

        RateLimiter::hit($key, 600);

        $user = User::where('username', $this->username)->first();

        // Always show success to prevent username enumeration.
        if ($user && $user->status === 'active' && ! $user->isSuperAdmin()) {
            $existing = PinResetRequest::where('user_id', $user->id)
                ->where('status', PinResetRequest::PENDING)
                ->first();

            if (! $existing) {
                $request = PinResetRequest::create([
                    'user_id' => $user->id,
                    'status' => PinResetRequest::PENDING,
                    'requester_ip' => request()->ip(),
                    'reason' => $this->reason ?: null,
                ]);

                $audit->log('pin_reset.requested', $request, [], $user->id);
            }
        }

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-pin');
    }
}
