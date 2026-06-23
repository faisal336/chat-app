# ChatApp

Self-hosted enterprise messaging on Laravel 13 + MySQL 8, designed for Hostinger shared hosting (no Redis, no Reverb, no Supervisor).

## Stack at a glance

| Concern | What we use |
|---|---|
| Backend | Laravel 13 + PHP 8.3+ |
| Frontend | Livewire 4 + Alpine.js (bundled) + Tailwind 4 |
| Database | MySQL 8 |
| Realtime | **AJAX polling** (every 3s while chat open) — Reverb/WebSockets are *not* used on shared hosting. |
| Queue | `database` driver, ticked by a single cron entry |
| Cache / sessions | `database` driver |
| Push notifications | Web Push via `minishlink/web-push` + VAPID |
| PWA | Service worker + manifest, installable + offline shell + background push |

## What's in the box

**Auth & RBAC**
- Username + 6-digit PIN login (PIN is hashed via bcrypt, never stored plaintext)
- Lockout after 5 failed attempts (15 min) + rate-limiter on username/IP
- Remember-me, session table tracking, device list
- 3-tier RBAC: `super_admin`, `admin`, `user` with policies + gates

**Chat**
- 1:1 private conversations, message types: text / image / file
- Reply, copy, pin (chats and messages), archive, hide
- Read / delivered / sent ticks
- Delete-for-self with audit trail (admin can restore + browse deleted)
- 50 messages on open, "load older" pagination
- Direct public attachment URLs with UUID filenames

**Admin**
- Dashboard with live stats and recent audit feed
- User CRUD with role assignment, disable / enable / archive
- Reset PIN with one-time temp PIN modal
- Deleted messages browser with restore
- Full audit log with search/filter
- PIN reset request queue (approve → temp PIN → forced reset on first login)

**Notifications & PWA**
- Web Push to all subscribed devices via VAPID
- Service worker handles install, push, notification click, subscription renewal
- Installable PWA (manifest + maskable icons)
- Per-user notification preferences + global on/off

**Security & audit**
- Policies for `Conversation`, `Message`, `User`
- Audit trail covers login, logout, message delete/restore, user create/update/disable/enable/archive, PIN reset, PIN change, lockouts
- Activity tracking (last_active_at refreshed every 30s server-side)
- CSRF on all state-changing routes, validation on all inputs

## Local development

```bash
composer install
cp .env.example .env       # if needed
php artisan key:generate
php artisan migrate:fresh --seed
php artisan webpush:vapid  # generates VAPID keys into .env
npm install
npm run build              # or: npm run dev
php artisan serve
```

Default super admin: **username `admin` / PIN `000000`**. You will be forced to change the PIN on first sign-in.

## Hostinger shared-hosting deployment

This codebase is designed to work on Hostinger Premium / Business plans without any sudo, Redis, Supervisor, or persistent processes.

### One-time setup (SSH)

```bash
# 1. Upload code or clone the repo
cd ~/domains/yourdomain.com
git clone https://github.com/you/chat-app.git
cd chat-app

# 2. Install dependencies (Hostinger ships PHP 8.3 — set in hPanel if not default)
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Configure environment
cp .env.example .env
nano .env
#   - APP_ENV=production
#   - APP_DEBUG=false
#   - APP_URL=https://yourdomain.com
#   - DB_* (your MySQL creds from hPanel)
#   - QUEUE_CONNECTION=database
#   - CACHE_STORE=database
#   - SESSION_DRIVER=database
#   - BROADCAST_CONNECTION=null
#   - FILESYSTEM_DISK=public
#   - VAPID_SUBJECT=mailto:you@yourdomain.com

php artisan key:generate
php artisan webpush:vapid          # writes VAPID_PUBLIC_KEY + VAPID_PRIVATE_KEY into .env
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Point public_html at the public/ folder

Hostinger serves `public_html`. Either:

- **Symlink** (preferred): `ln -s ~/domains/yourdomain.com/chat-app/public ~/domains/yourdomain.com/public_html` (delete the old `public_html` first), or
- **Move the contents of `public/` into `public_html/`** and update `bootstrap/app.php` paths.

### One cron entry powers everything

In hPanel → Advanced → Cron Jobs, add a single job:

```
* * * * * cd /home/USERNAME/domains/yourdomain.com/chat-app && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

That runs `Schedule::command(...)` from `routes/console.php`, which:

1. Calls `queue:work --stop-when-empty --max-time=50` every minute (drains WebPush jobs)
2. Prunes failed jobs weekly
3. Prunes expired sessions daily

No Supervisor required.

### Web Push from a shared host

Web Push *just works* because the push goes from your PHP process → the browser vendor's push service. No persistent connection from your server is needed.

After deploy, your VAPID keys live in `.env`. Users will see the "Enable notifications" toggle the first time they open the chat in a browser that supports push (Chrome / Edge / Firefox / Safari 16+ on iOS for installed PWAs).

### What you give up vs the original spec

| Spec feature | Status on shared hosting |
|---|---|
| WebSocket realtime (Reverb / Echo) | **Replaced by 3s polling.** Messages still arrive automatically; just not instant. |
| Typing indicator | **Deferred** — needs WebSockets to feel right. |
| Redis presence | Replaced by `users.last_active_at` (refreshed per-request, throttled to 30s). |
| Supervisor queue workers | Replaced by `schedule:run` + `queue:work --stop-when-empty` ticked by cron. |

To unlock all of these, move to a VPS, set `BROADCAST_CONNECTION=reverb`, install Redis + Supervisor, and the codebase is ready.

## Folder structure

```
app/
├── Actions/Chat/          SendMessage, DeleteMessage, RestoreMessage, MarkConversationRead
├── Console/Commands/      GenerateVapidKeys
├── Http/
│   ├── Controllers/       AuthController, PushSubscriptionController
│   └── Middleware/        EnsureActive, EnsureAdmin, TrackActivity
├── Jobs/                  SendPushJob
├── Livewire/
│   ├── Auth/              Login, PinChange, ForgotPin
│   ├── Chat/              Index (the whole chat UI lives here)
│   └── Admin/             Dashboard, Users, DeletedMessages, AuditLogs, PinResetQueue
├── Models/                20 Eloquent models
├── Notifications/
│   ├── Channels/          WebPushChannel
│   ├── NewMessageNotification
│   └── PinResetIssuedNotification
├── Policies/              Conversation, Message, User
└── Services/              AuditService, ChatService, LoginHistoryService, PushService
```

## Database

22 tables managed by the migrations under `database/migrations/0001_01_01_*`. Run `php artisan migrate:fresh --seed` to recreate.

| Table | Purpose |
|---|---|
| users, user_settings, user_sessions, login_history | Identity, devices, history |
| roles, permissions, role_user, permission_role | RBAC |
| pin_reset_requests | Admin-mediated PIN recovery |
| conversations, conversation_participants | 1:1 chat threads |
| messages, attachments | Content (soft-deleted; never physically removed) |
| message_reads, message_deliveries | Per-user receipt timestamps |
| archived_chats, hidden_chats, pinned_chats, pinned_messages | Per-user chat/message state |
| push_subscriptions | Web Push endpoints |
| notification_preferences, notifications | Per-event toggles + Laravel DB channel |
| audit_logs | Polymorphic action log |

## Default credentials

`admin / 000000` — forced PIN change on first sign-in.

## License

MIT
