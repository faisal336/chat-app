<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function archive(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function hide(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function pin(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    private function isParticipant(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }
}
