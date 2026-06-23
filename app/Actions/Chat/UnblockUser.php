<?php

namespace App\Actions\Chat;

use App\Models\User;
use App\Models\UserBlock;
use App\Services\AuditService;

class UnblockUser
{
    public function __construct(private AuditService $audit) {}

    public function handle(User $blocker, User $target): void
    {
        $deleted = UserBlock::where('blocker_id', $blocker->id)
            ->where('blocked_id', $target->id)
            ->delete();

        if ($deleted) {
            $this->audit->log('user.unblocked', $target, [], $blocker->id);
        }
    }
}
