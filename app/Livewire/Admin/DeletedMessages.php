<?php

namespace App\Livewire\Admin;

use App\Actions\Chat\RestoreMessage;
use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Deleted messages')]
class DeletedMessages extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewDeletedMessages', auth()->user());
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Named "rows" instead of "messages" — Livewire reserves messages() for
     * custom validation error message providers, so colliding crashes validate().
     */
    #[Computed]
    public function rows()
    {
        $query = Message::onlyTrashed()
            ->with(['sender', 'deletedBy', 'attachments', 'conversation.users']);

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('body', 'like', "%{$term}%")
                  ->orWhereHas('sender', fn ($q2) => $q2->where('name', 'like', "%{$term}%")->orWhere('username', 'like', "%{$term}%"));
            });
        }

        return $query->latest('deleted_at')->paginate(20);
    }

    public function restore(int $id, RestoreMessage $restorer): void
    {
        $message = Message::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $message);

        $restorer->handle(auth()->user(), $message);
        unset($this->rows);
    }

    public function render()
    {
        return view('livewire.admin.deleted-messages');
    }
}
