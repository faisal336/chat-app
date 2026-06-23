<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin() && in_array($ability, ['view', 'viewDeleted', 'restore', 'forceDelete'], true)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function view(User $user, Message $message): bool
    {
        return $message->conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user, Conversation $conversation): bool
    {
        return $user->isActive() && $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, Message $message): bool
    {
        return $message->sender_id === $user->id && ! $message->isDeleted();
    }

    public function delete(User $user, Message $message): bool
    {
        return $message->sender_id === $user->id || $user->isAdmin();
    }

    public function pin(User $user, Message $message): bool
    {
        return $message->conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function reply(User $user, Message $message): bool
    {
        return $this->view($user, $message);
    }

    public function viewDeleted(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Message $message): bool
    {
        return $user->isAdmin();
    }
}
