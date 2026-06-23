<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Message;
use App\Models\PinResetRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    #[Computed]
    public function stats(): array
    {
        return [
            'users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'online_now' => User::where('last_active_at', '>=', Carbon::now()->subMinutes(2))->count(),
            'messages_today' => Message::whereDate('created_at', today())->count(),
            'deleted_messages' => Message::onlyTrashed()->count(),
            'pending_pin_resets' => PinResetRequest::where('status', PinResetRequest::PENDING)->count(),
        ];
    }

    #[Computed]
    public function recentActivity()
    {
        return AuditLog::with('actor')
            ->latest('created_at')
            ->limit(15)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
