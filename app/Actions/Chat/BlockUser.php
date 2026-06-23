<?php

namespace App\Actions\Chat;

use App\Models\ChatRequest;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlockUser
{
    public function __construct(private AuditService $audit) {}

    public function handle(User $blocker, User $target, ?string $reason = null): UserBlock
    {
        if ($blocker->id === $target->id) {
            throw new \InvalidArgumentException('Cannot block yourself.');
        }

        return DB::transaction(function () use ($blocker, $target, $reason) {
            $block = UserBlock::firstOrCreate(
                ['blocker_id' => $blocker->id, 'blocked_id' => $target->id],
                ['reason' => $reason, 'blocked_at' => Carbon::now()]
            );

            // Cancel any pending request between the two so they aren't dangling.
            ChatRequest::query()
                ->between($blocker->id, $target->id)
                ->where('status', ChatRequest::STATUS_PENDING)
                ->update([
                    'status' => ChatRequest::STATUS_CANCELLED,
                    'responded_at' => Carbon::now(),
                ]);

            $this->audit->log('user.blocked', $target, [
                'reason' => $reason,
            ], $blocker->id);

            return $block;
        });
    }
}
