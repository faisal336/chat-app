<?php

namespace App\Livewire\Chat;

use App\Actions\Chat\AcceptChatRequest;
use App\Actions\Chat\BlockUser;
use App\Actions\Chat\ClearChatHistory;
use App\Actions\Chat\DeleteMessage;
use App\Actions\Chat\MarkConversationRead;
use App\Actions\Chat\RejectChatRequest;
use App\Actions\Chat\SendChatRequest;
use App\Actions\Chat\SendMessage;
use App\Actions\Chat\UnblockUser;
use App\Models\ArchivedChat;
use App\Models\ChatRequest;
use App\Models\Conversation;
use App\Models\HiddenChat;
use App\Models\Message;
use App\Models\PinnedChat;
use App\Models\PinnedMessage;
use App\Models\User;
use App\Services\ChatPermissionService;
use App\Services\ChatService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Chat')]
class Index extends Component
{
    use WithFileUploads;

    public const MESSAGES_INITIAL = 50;

    public const MESSAGES_PAGE = 30;

    #[Url(as: 'c', except: null)]
    public ?int $activeConversationId = null;

    #[Url(as: 'view')]
    public string $view = 'chats';

    public string $search = '';

    public string $messageText = '';

    public ?int $replyToId = null;

    public bool $newChatOpen = false;

    public string $newChatSearch = '';

    public bool $searchOpen = false;

    public string $searchQuery = '';

    // Chat request modal (non-admin "new chat" flow)
    public ?int $requestingUserId = null;

    public string $requestMessage = '';

    public bool $infoOpen = false;

    public int $messagesShown = self::MESSAGES_INITIAL;

    public array $attachments = [];

    public ?int $lastSeenMessageId = null;

    /** Synced from the browser via Alpine on visibilitychange. */
    public bool $tabVisible = true;

    public function mount(): void
    {
        if ($this->activeConversationId) {
            $this->selectConversation($this->activeConversationId, mark: true);
        }
    }

    #[Computed]
    public function conversations(): Collection
    {
        $user = auth()->user();

        $archivedIds = ArchivedChat::where('user_id', $user->id)->pluck('conversation_id');
        $hiddenIds = HiddenChat::where('user_id', $user->id)->pluck('conversation_id');
        $pinnedIds = PinnedChat::where('user_id', $user->id)->pluck('conversation_id');
        $blockedIds = $this->blockedByMeIds;

        $query = Conversation::query()
            ->forUser($user->id)
            ->with(['users', 'lastMessage.sender']);

        // Exclude conversations where the other participant is someone *I* blocked.
        if (! empty($blockedIds)) {
            $query->whereDoesntHave('participants', fn ($q) => $q->whereIn('user_id', $blockedIds));
        }

        if ($this->view === 'archived') {
            $query->whereIn('id', $archivedIds);
        } elseif ($this->view === 'hidden') {
            $query->whereIn('id', $hiddenIds);
        } else {
            $query->whereNotIn('id', $archivedIds)
                  ->whereNotIn('id', $hiddenIds);
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->whereHas('users', fn ($q) => $q
                ->where('users.id', '!=', $user->id)
                ->where(fn ($q2) => $q2
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%")
                )
            );
        }

        return $query
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->sortByDesc(function (Conversation $c) use ($pinnedIds) {
                return [$pinnedIds->contains($c->id) ? 1 : 0, $c->last_message_at ?? $c->created_at];
            })
            ->values();
    }

    #[Computed]
    public function activeConversation(): ?Conversation
    {
        if (! $this->activeConversationId) {
            return null;
        }

        return Conversation::with([
                'users',
                'participants',
                'pinnedMessages.message.sender',
            ])
            ->forUser(auth()->id())
            ->find($this->activeConversationId);
    }

    /**
     * The thread of messages for the active conversation. NOTE: named "thread"
     * (not "messages") because Livewire reserves messages() for custom
     * validation error message providers — colliding crashes validate().
     */
    #[Computed]
    public function thread(): Collection
    {
        $conversation = $this->activeConversation;

        if (! $conversation) {
            return new Collection;
        }

        $cleared = (int) ($conversation->participants
            ->firstWhere('user_id', auth()->id())
            ?->cleared_through_message_id ?? 0);

        $query = Message::query()
            ->where('conversation_id', $conversation->id)
            ->when($cleared > 0, fn ($q) => $q->where('id', '>', $cleared))
            ->with(['sender:id,username,name,avatar_path', 'attachments', 'replyTo.sender:id,name,username']);

        if ($this->searchQuery !== '') {
            $term = $this->searchQuery;
            // Use a wider window when searching so older matches show up.
            $query->where('body', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%')
                  ->orderByDesc('id')
                  ->limit(200);
        } else {
            $query->orderByDesc('id')->limit($this->messagesShown);
        }

        return $query->get()->reverse()->values();
    }

    #[Computed]
    public function searchHitCount(): int
    {
        if ($this->searchQuery === '' || ! $this->activeConversation) {
            return 0;
        }

        $term = str_replace(['%', '_'], ['\%', '\_'], $this->searchQuery);

        return Message::query()
            ->where('conversation_id', $this->activeConversation->id)
            ->where('body', 'like', '%'.$term.'%')
            ->count();
    }

    public function toggleSearch(): void
    {
        $this->searchOpen = ! $this->searchOpen;
        if (! $this->searchOpen) {
            $this->searchQuery = '';
            unset($this->thread, $this->searchHitCount);
        }
    }

    public function clearSearch(): void
    {
        $this->searchQuery = '';
        unset($this->thread, $this->searchHitCount);
    }

    public function updatedSearchQuery(): void
    {
        unset($this->thread, $this->searchHitCount);
    }

    #[Computed]
    public function pinnedChatIds(): array
    {
        return PinnedChat::where('user_id', auth()->id())->pluck('conversation_id')->all();
    }

    #[Computed]
    public function archivedChatIds(): array
    {
        return ArchivedChat::where('user_id', auth()->id())->pluck('conversation_id')->all();
    }

    #[Computed]
    public function hiddenChatIds(): array
    {
        return HiddenChat::where('user_id', auth()->id())->pluck('conversation_id')->all();
    }

    /**
     * Map conversation_id => latest *visible* Message (respects the current
     * user's cleared-history watermark). Used by the sidebar so a cleared chat
     * doesn't keep showing the last message body.
     */
    #[Computed]
    public function previewByConversation(): array
    {
        $userId = auth()->id();
        $convIds = $this->conversations->pluck('id')->all();

        if (empty($convIds)) {
            return [];
        }

        $rows = DB::table('messages as m')
            ->join('conversation_participants as cp', function ($j) use ($userId) {
                $j->on('cp.conversation_id', '=', 'm.conversation_id')
                  ->where('cp.user_id', $userId);
            })
            ->whereIn('m.conversation_id', $convIds)
            ->whereNull('m.deleted_at')
            ->where(function ($q) {
                $q->whereNull('cp.cleared_through_message_id')
                  ->orWhereColumn('m.id', '>', 'cp.cleared_through_message_id');
            })
            ->select('m.conversation_id', DB::raw('MAX(m.id) as max_id'))
            ->groupBy('m.conversation_id')
            ->get();

        $maxIds = $rows->pluck('max_id')->all();

        if (empty($maxIds)) {
            return [];
        }

        return Message::with('sender:id,name,username')
            ->whereIn('id', $maxIds)
            ->get()
            ->keyBy('conversation_id')
            ->all();
    }

    #[Computed]
    public function unreadCounts(): array
    {
        $user = auth()->user();

        return DB::table('messages')
            ->join('conversation_participants', function ($join) use ($user) {
                $join->on('conversation_participants.conversation_id', '=', 'messages.conversation_id')
                    ->where('conversation_participants.user_id', $user->id);
            })
            ->whereNull('messages.deleted_at')
            ->where('messages.sender_id', '!=', $user->id)
            ->where(function ($q) {
                $q->whereNull('conversation_participants.last_read_message_id')
                  ->orWhereColumn('messages.id', '>', 'conversation_participants.last_read_message_id');
            })
            ->where(function ($q) {
                $q->whereNull('conversation_participants.cleared_through_message_id')
                  ->orWhereColumn('messages.id', '>', 'conversation_participants.cleared_through_message_id');
            })
            ->selectRaw('messages.conversation_id, COUNT(*) as c')
            ->groupBy('messages.conversation_id')
            ->pluck('c', 'conversation_id')
            ->all();
    }

    /**
     * Look up the candidate to start a chat with. EXACT username match only —
     * users have to know the exact handle (privacy by obscurity). Partial
     * matches return nothing.
     */
    #[Computed]
    public function newChatCandidates(): Collection
    {
        if (! $this->newChatOpen || trim($this->newChatSearch) === '') {
            return new Collection;
        }

        return User::query()
            ->active()
            ->where('id', '!=', auth()->id())
            ->where('username', trim($this->newChatSearch))
            ->limit(1)
            ->get(['id', 'username', 'name', 'avatar_path', 'last_active_at']);
    }

    public function updatedView(): void
    {
        $this->activeConversationId = null;
        unset($this->conversations);
    }

    public function selectConversation(int $id, bool $mark = true): void
    {
        $conversation = Conversation::forUser(auth()->id())->find($id);

        if (! $conversation) {
            $this->activeConversationId = null;

            return;
        }

        $this->activeConversationId = $conversation->id;
        $this->messageText = '';
        $this->replyToId = null;
        $this->messagesShown = self::MESSAGES_INITIAL;
        $this->attachments = [];
        $this->lastSeenMessageId = Message::where('conversation_id', $conversation->id)
            ->max('id');

        if ($mark) {
            // Always at least mark delivered when the chat is opened. Read
            // only if the tab is actually visible at this moment.
            app(MarkConversationRead::class)->handle(
                auth()->user(),
                $conversation,
                markRead: $this->tabVisible,
            );
        }

        $this->dispatch('chat-selected', conversationId: $conversation->id);
    }

    public function startChatWith(int $userId, ChatService $chats, ChatPermissionService $perm): void
    {
        $target = User::active()->find($userId);
        $actor = auth()->user();

        if (! $target || $target->id === $actor->id) {
            return;
        }

        $status = $perm->status($actor, $target);

        // Admin path (or already-accepted) — open conversation directly.
        if ($status === ChatPermissionService::ALLOWED) {
            $conversation = $chats->privateConversationBetween($actor, $target);
            $this->newChatOpen = false;
            $this->newChatSearch = '';
            $this->selectConversation($conversation->id);

            return;
        }

        // Invited (they already sent ME a request) — bring up requests tab.
        if ($status === ChatPermissionService::INVITED) {
            $this->newChatOpen = false;
            $this->newChatSearch = '';
            $this->view = 'requests';
            $this->activeConversationId = null;

            return;
        }

        if ($status === ChatPermissionService::REQUEST_PENDING) {
            session()->flash('chat_message', 'You already sent a chat request to '.$target->name.'. Waiting for them to accept.');
            $this->newChatOpen = false;

            return;
        }

        if (in_array($status, [
            ChatPermissionService::BLOCKED_BY_ME,
            ChatPermissionService::BLOCKED_BY_THEM,
            ChatPermissionService::TARGET_INACTIVE,
        ], true)) {
            session()->flash('chat_message', $perm->reason($status));
            $this->newChatOpen = false;

            return;
        }

        // NEEDS_REQUEST — switch the new-chat modal into "compose request" mode.
        $this->requestingUserId = $target->id;
        $this->requestMessage = '';
    }

    public function cancelRequestComposer(): void
    {
        $this->requestingUserId = null;
        $this->requestMessage = '';
    }

    public function sendChatRequest(SendChatRequest $action): void
    {
        if (! $this->requestingUserId) {
            return;
        }

        $target = User::active()->find($this->requestingUserId);
        if (! $target) {
            $this->cancelRequestComposer();

            return;
        }

        $this->validate([
            'requestMessage' => 'nullable|string|max:500',
        ]);

        try {
            $action->handle(auth()->user(), $target, $this->requestMessage ?: null);
        } catch (\Throwable $e) {
            session()->flash('chat_message', $e->getMessage());
            $this->cancelRequestComposer();

            return;
        }

        $this->cancelRequestComposer();
        $this->newChatOpen = false;
        $this->newChatSearch = '';
        session()->flash('chat_message', 'Chat request sent to '.$target->name.'.');
    }

    public function acceptRequest(int $requestId, AcceptChatRequest $action): void
    {
        $request = ChatRequest::with('requester')->find($requestId);
        if (! $request || $request->recipient_id !== auth()->id()) {
            return;
        }

        try {
            $conversation = $action->handle(auth()->user(), $request);
        } catch (\Throwable $e) {
            session()->flash('chat_message', $e->getMessage());

            return;
        }

        $this->view = 'chats';
        unset($this->conversations, $this->previewByConversation, $this->incomingRequests, $this->outgoingRequests);
        $this->selectConversation($conversation->id);
    }

    public function rejectRequest(int $requestId, RejectChatRequest $action): void
    {
        $request = ChatRequest::find($requestId);
        if (! $request || $request->recipient_id !== auth()->id()) {
            return;
        }

        $action->handle(auth()->user(), $request);
        unset($this->incomingRequests);
    }

    public function cancelOutgoingRequest(int $requestId): void
    {
        $request = ChatRequest::find($requestId);
        if (! $request || $request->requester_id !== auth()->id() || ! $request->isPending()) {
            return;
        }

        $request->forceFill([
            'status' => ChatRequest::STATUS_CANCELLED,
            'responded_at' => Carbon::now(),
        ])->save();

        app(\App\Services\AuditService::class)->log('chat_request.cancelled', $request);
        unset($this->outgoingRequests);
    }

    public function blockOther(BlockUser $action): void
    {
        $conv = $this->activeConversation;
        if (! $conv) {
            return;
        }

        $other = $conv->otherParticipant(auth()->id());
        if (! $other) {
            return;
        }

        $action->handle(auth()->user(), $other);

        // Clear active conversation since blocker shouldn't see the chat.
        $this->activeConversationId = null;
        unset(
            $this->thread,
            $this->activeConversation,
            $this->conversations,
            $this->previewByConversation,
            $this->permissionStatus,
            $this->blockedByMeIds,
        );
        session()->flash('chat_message', $other->name.' blocked.');
    }

    public function unblockOther(UnblockUser $action): void
    {
        $conv = $this->activeConversation;
        if (! $conv) {
            return;
        }

        $other = $conv->otherParticipant(auth()->id());
        if (! $other) {
            return;
        }

        $action->handle(auth()->user(), $other);
        unset(
            $this->thread,
            $this->activeConversation,
            $this->conversations,
            $this->previewByConversation,
            $this->permissionStatus,
            $this->blockedByMeIds,
        );
        session()->flash('chat_message', $other->name.' unblocked.');
    }

    #[Computed]
    public function permissionStatus(): ?string
    {
        $conv = $this->activeConversation;
        if (! $conv) {
            return null;
        }

        $other = $conv->otherParticipant(auth()->id());
        if (! $other) {
            return null;
        }

        return app(ChatPermissionService::class)->status(auth()->user(), $other);
    }

    #[Computed]
    public function incomingRequests(): Collection
    {
        return ChatRequest::with('requester:id,username,name,avatar_path')
            ->where('recipient_id', auth()->id())
            ->where('status', ChatRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function outgoingRequests(): Collection
    {
        return ChatRequest::with('recipient:id,username,name,avatar_path')
            ->where('requester_id', auth()->id())
            ->where('status', ChatRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function blockedByMeIds(): array
    {
        return \App\Models\UserBlock::where('blocker_id', auth()->id())
            ->pluck('blocked_id')
            ->all();
    }

    public function sendMessage(SendMessage $send): void
    {
        $conversation = $this->activeConversation;

        if (! $conversation || ! Gate::allows('create', [Message::class, $conversation])) {
            return;
        }

        $this->validate([
            'messageText' => 'nullable|string|max:5000',
            'attachments.*' => 'nullable|file|max:20480',
        ]);

        if (trim($this->messageText) === '' && empty($this->attachments)) {
            return;
        }

        $files = array_values(array_filter($this->attachments));

        try {
            $send->handle(
                sender: auth()->user(),
                conversation: $conversation,
                body: $this->messageText !== '' ? $this->messageText : null,
                attachments: $files,
                replyToId: $this->replyToId,
            );
        } catch (\Throwable $e) {
            $this->addError('messageText', 'Failed to send message.');

            return;
        }

        $this->messageText = '';
        $this->replyToId = null;
        $this->attachments = [];

        unset($this->thread, $this->conversations, $this->previewByConversation);

        $this->dispatch('message-sent');
    }

    /**
     * Called from the browser when the tab becomes visible. Catches up on
     * read receipts that we deliberately skipped while the tab was hidden.
     */
    public function tabBecameVisible(): void
    {
        $this->tabVisible = true;

        if ($conv = $this->activeConversation) {
            app(MarkConversationRead::class)->handle(auth()->user(), $conv);
            unset($this->thread, $this->conversations, $this->unreadCounts, $this->previewByConversation);
        }
    }

    public function tabBecameHidden(): void
    {
        $this->tabVisible = false;
    }

    public function refreshMessages(): void
    {
        unset($this->thread, $this->conversations, $this->previewByConversation, $this->unreadCounts);

        // The user is online (they're polling) → every incoming undelivered
        // message they have, across ALL their conversations, can be marked
        // delivered. Otherwise the sender's ✓ would only flip to ✓✓ once the
        // recipient happened to open that exact chat. Bulk-update keeps it cheap.
        $this->markAllIncomingDelivered();

        $conversation = $this->activeConversation;

        // Detect newly-arrived incoming messages on this conversation so the
        // browser can ping a sound / update the title (polling never wakes the
        // tab on its own).
        if ($conversation) {
            $latest = Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', auth()->id())
                ->orderByDesc('id')
                ->first(['id', 'sender_id']);

            if ($latest && (! $this->lastSeenMessageId || $latest->id > $this->lastSeenMessageId)) {
                if ($this->lastSeenMessageId !== null) {
                    $sender = User::find($latest->sender_id);
                    $this->dispatch('chatapp:incoming-message',
                        conversationId: $conversation->id,
                        senderName: $sender?->name ?? 'Someone',
                        playSound: (bool) (auth()->user()->settings?->notifications_sound ?? true),
                    );
                }
                $this->lastSeenMessageId = $latest->id;
            }

            // Always mark delivered (recipient's browser has the data, that's
            // delivery). Only mark *read* when the tab is actually visible —
            // otherwise a hidden background tab would silently acknowledge
            // messages the user never saw.
            app(MarkConversationRead::class)->handle(
                auth()->user(),
                $conversation,
                markRead: $this->tabVisible,
            );
        }

        // Always ping the unread total so off-conversation messages still update the badge.
        $total = array_sum($this->unreadCounts);
        $this->dispatch('chatapp:unread-changed', count: $total);
    }

    /**
     * Bulk-mark every incoming undelivered message addressed to the current
     * user as delivered. Called on every poll; the user has the chat page
     * open, that *is* delivery — they just haven't opened the specific chat.
     */
    private function markAllIncomingDelivered(): void
    {
        $userId = auth()->id();
        $now = Carbon::now();

        $ids = DB::table('messages')
            ->join('conversation_participants as cp', function ($j) use ($userId) {
                $j->on('cp.conversation_id', '=', 'messages.conversation_id')
                  ->where('cp.user_id', $userId);
            })
            ->where('messages.sender_id', '!=', $userId)
            ->whereNull('messages.delivered_at')
            ->whereNull('messages.deleted_at')
            ->pluck('messages.id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('messages')
            ->whereIn('id', $ids)
            ->whereNull('delivered_at')
            ->update(['delivered_at' => $now]);

        \App\Models\MessageDelivery::insertOrIgnore(
            $ids->map(fn ($id) => [
                'message_id' => $id,
                'user_id' => $userId,
                'delivered_at' => $now,
            ])->all()
        );
    }

    public function loadOlder(): void
    {
        $this->messagesShown += self::MESSAGES_PAGE;
        unset($this->thread);
    }

    public function deleteMessage(int $id, DeleteMessage $deleter): void
    {
        $message = Message::find($id);

        if (! $message || ! Gate::allows('delete', $message)) {
            return;
        }

        $deleter->handle(auth()->user(), $message);
        unset($this->thread);
    }

    public function setReply(int $id): void
    {
        $message = Message::find($id);

        if (! $message || ! Gate::allows('reply', $message)) {
            return;
        }

        $this->replyToId = $message->id;
    }

    public function cancelReply(): void
    {
        $this->replyToId = null;
    }

    public function togglePinChat(): void
    {
        $conv = $this->activeConversation;
        if (! $conv) {
            return;
        }

        $existing = PinnedChat::where('user_id', auth()->id())
            ->where('conversation_id', $conv->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            PinnedChat::create([
                'user_id' => auth()->id(),
                'conversation_id' => $conv->id,
                'pinned_at' => Carbon::now(),
            ]);
        }

        unset($this->conversations, $this->previewByConversation, $this->pinnedChatIds);
    }

    public function toggleArchiveChat(): void
    {
        $conv = $this->activeConversation;
        if (! $conv) {
            return;
        }

        $existing = ArchivedChat::where('user_id', auth()->id())
            ->where('conversation_id', $conv->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            ArchivedChat::create([
                'user_id' => auth()->id(),
                'conversation_id' => $conv->id,
                'archived_at' => Carbon::now(),
            ]);

            if ($this->view === 'chats') {
                $this->activeConversationId = null;
            }
        }

        unset($this->conversations, $this->previewByConversation, $this->archivedChatIds);
    }

    public function toggleHideChat(): void
    {
        $conv = $this->activeConversation;
        if (! $conv) {
            return;
        }

        $existing = HiddenChat::where('user_id', auth()->id())
            ->where('conversation_id', $conv->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            HiddenChat::create([
                'user_id' => auth()->id(),
                'conversation_id' => $conv->id,
                'hidden_at' => Carbon::now(),
            ]);

            if ($this->view !== 'hidden') {
                $this->activeConversationId = null;
            }
        }

        unset($this->conversations, $this->previewByConversation, $this->hiddenChatIds);
    }

    public function pinMessage(int $id): void
    {
        $message = Message::find($id);

        if (! $message || ! Gate::allows('pin', $message)) {
            return;
        }

        $existing = PinnedMessage::where('conversation_id', $message->conversation_id)
            ->where('message_id', $message->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            PinnedMessage::create([
                'conversation_id' => $message->conversation_id,
                'message_id' => $message->id,
                'pinned_by' => auth()->id(),
            ]);
        }

        unset($this->activeConversation);
    }

    public function toggleInfo(): void
    {
        $this->infoOpen = ! $this->infoOpen;
    }

    public function clearChatHistory(ClearChatHistory $clearer): void
    {
        $conv = $this->activeConversation;

        if (! $conv || ! Gate::allows('view', $conv)) {
            return;
        }

        $clearer->handle(auth()->user(), $conv);

        $this->messagesShown = self::MESSAGES_INITIAL;
        unset(
            $this->thread,
            $this->activeConversation,
            $this->conversations,
            $this->previewByConversation,
            $this->unreadCounts,
        );
    }

    public function render()
    {
        return view('livewire.chat.index');
    }
}
