<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function privateConversationBetween(User $a, User $b): Conversation
    {
        if ($a->id === $b->id) {
            throw new \InvalidArgumentException('Cannot start a conversation with yourself.');
        }

        $existing = Conversation::query()
            ->between($a->id, $b->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($a, $b) {
            $conversation = Conversation::create([
                'type' => 'private',
                'created_by' => $a->id,
            ]);

            ConversationParticipant::insert([
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $a->id,
                    'joined_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $b->id,
                    'joined_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ]);

            return $conversation->fresh('participants');
        });
    }
}
