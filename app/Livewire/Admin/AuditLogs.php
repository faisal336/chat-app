<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Audit logs')]
class AuditLogs extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'action', except: '')]
    public string $actionFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActionFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function actions(): array
    {
        return AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }

    #[Computed]
    public function logs()
    {
        $query = AuditLog::with('actor');

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->whereHas('actor', fn ($q2) => $q2->where('name', 'like', "%{$term}%")->orWhere('username', 'like', "%{$term}%"))
                  ->orWhere('ip_address', 'like', "%{$term}%")
                  ->orWhere('action', 'like', "%{$term}%");
            });
        }

        if ($this->actionFilter !== '') {
            $query->where('action', $this->actionFilter);
        }

        return $query->latest('created_at')->paginate(30);
    }

    public function render()
    {
        return view('livewire.admin.audit-logs');
    }
}
