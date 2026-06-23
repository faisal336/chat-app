<?php

namespace App\Livewire\Auth;

use App\Services\AuditService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Change PIN')]
class PinChange extends Component
{
    #[Validate('required|digits:6')]
    public string $current_pin = '';

    #[Validate('required|digits:6|different:current_pin')]
    public string $new_pin = '';

    #[Validate('required|same:new_pin')]
    public string $new_pin_confirmation = '';

    public function submit(AuditService $audit): void
    {
        $this->validate();

        $user = auth()->user();

        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if (! $user->checkPin($this->current_pin)) {
            throw ValidationException::withMessages([
                'current_pin' => 'Current PIN is incorrect.',
            ]);
        }

        if ($this->isWeak($this->new_pin)) {
            throw ValidationException::withMessages([
                'new_pin' => 'Choose a less predictable PIN.',
            ]);
        }

        $user->setPin($this->new_pin);
        $user->pin_must_change = false;
        $user->save();

        $audit->log('user.pin_changed', $user);

        session()->flash('status', 'Your PIN has been updated.');

        $this->redirectRoute('chat.index', navigate: true);
    }

    private function isWeak(string $pin): bool
    {
        $weak = ['000000', '111111', '222222', '333333', '444444', '555555',
                 '666666', '777777', '888888', '999999', '123456', '654321',
                 '123123', '121212', '012345'];

        return in_array($pin, $weak, true);
    }

    public function render()
    {
        return view('livewire.auth.pin-change');
    }
}
