<?php

namespace App\Livewire\Admin;

use App\Models\PinResetRequest;
use App\Notifications\PinResetIssuedNotification;
use App\Services\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('PIN reset requests')]
class PinResetQueue extends Component
{
    use WithPagination;

    public string $statusFilter = PinResetRequest::PENDING;

    #[Computed]
    public function requests()
    {
        return PinResetRequest::with(['user', 'handler'])
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('created_at')
            ->paginate(20);
    }

    public function approve(int $id, AuditService $audit): string
    {
        $request = PinResetRequest::with('user')->findOrFail($id);

        if ($request->status !== PinResetRequest::PENDING) {
            return '';
        }

        $tempPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $request->user->setPin($tempPin);
        $request->user->pin_must_change = true;
        $request->user->save();

        $request->forceFill([
            'status' => PinResetRequest::APPROVED,
            'handled_by' => auth()->id(),
            'handled_at' => Carbon::now(),
            'temp_pin_hash' => Hash::make($tempPin),
        ])->save();

        $audit->log('pin_reset.approved', $request, ['user_id' => $request->user_id]);
        $request->user->notify(new PinResetIssuedNotification);

        if ($request->user->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($request->user->email)
                    ->queue(new \App\Mail\PinResetIssuedEmail($request->user, $tempPin));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('PinResetIssuedEmail dispatch failed: '.$e->getMessage());
            }
        }

        $this->dispatch('temp-pin-issued', userId: $request->user_id, pin: $tempPin);
        unset($this->requests);

        return $tempPin;
    }

    public function reject(int $id, AuditService $audit): void
    {
        $request = PinResetRequest::findOrFail($id);

        if ($request->status !== PinResetRequest::PENDING) {
            return;
        }

        $request->forceFill([
            'status' => PinResetRequest::REJECTED,
            'handled_by' => auth()->id(),
            'handled_at' => Carbon::now(),
        ])->save();

        $audit->log('pin_reset.rejected', $request);
        unset($this->requests);
    }

    public function render()
    {
        return view('livewire.admin.pin-reset-queue');
    }
}
