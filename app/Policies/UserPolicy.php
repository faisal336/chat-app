<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->id === $target->id || $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        if (! $actor->isAdmin()) {
            return false;
        }

        return ! $target->isSuperAdmin();
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->isAdmin()
            && $actor->id !== $target->id
            && ! $target->isSuperAdmin();
    }

    public function disable(User $actor, User $target): bool
    {
        return $this->delete($actor, $target);
    }

    public function enable(User $actor, User $target): bool
    {
        return $actor->isAdmin() && ! $target->isSuperAdmin();
    }

    public function archive(User $actor, User $target): bool
    {
        return $this->delete($actor, $target);
    }

    public function resetPin(User $actor, User $target): bool
    {
        return $actor->isAdmin() && $actor->id !== $target->id && ! $target->isSuperAdmin();
    }

    public function impersonate(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $actor->id !== $target->id;
    }

    public function manageRoles(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function viewActivity(User $actor, User $target): bool
    {
        return $actor->isAdmin() || $actor->id === $target->id;
    }

    public function viewDeletedMessages(User $actor): bool
    {
        return $actor->isAdmin();
    }
}
