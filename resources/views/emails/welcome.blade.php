@php($preview = 'Your '.config('app.name').' account is ready.')
@php($appUrl = config('app.url'))
<x-emails.layout :preview="$preview" :previewTitle="'Welcome to '.config('app.name')">

<h1 style="margin:0 0 8px 0;font-size:22px;font-weight:700;letter-spacing:-0.02em;color:#0f172a;">
    Welcome, {{ $user->name }} 👋
</h1>
<p style="margin:0 0 24px 0;font-size:15px;line-height:24px;color:#475569;">
    Your account on <strong style="color:#0f172a;">{{ config('app.name') }}</strong> is ready to go. You can sign in any time with your username and 6-digit PIN.
</p>

{{-- Account summary --}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 24px 0;">
    <tr>
        <td style="padding:18px 20px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="padding-bottom:10px;">
                        <span style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;font-weight:600;color:#64748b;">Display name</span><br>
                        <span style="font-size:15px;color:#0f172a;font-weight:600;">{{ $user->name }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom:10px;border-top:1px solid #e2e8f0;padding-top:10px;">
                        <span style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;font-weight:600;color:#64748b;">Username</span><br>
                        <span style="font-size:15px;color:#0f172a;font-weight:600;font-family:'SF Mono',Menlo,Consolas,monospace;">@ {{ $user->username }}</span>
                    </td>
                </tr>
                @if($tempPin)
                <tr>
                    <td style="border-top:1px solid #e2e8f0;padding-top:10px;">
                        <span style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;font-weight:600;color:#64748b;">Temporary PIN</span><br>
                        <span style="font-size:24px;color:#1e3a8a;font-weight:700;letter-spacing:0.4em;font-family:'SF Mono',Menlo,Consolas,monospace;">{{ $tempPin }}</span>
                        <p style="margin:8px 0 0 0;font-size:12px;color:#dc2626;line-height:18px;">
                            ⚠ You'll be asked to change this PIN the first time you sign in.
                        </p>
                    </td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- CTA --}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
    <tr>
        <td style="background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);border-radius:10px;">
            <a href="{{ $appUrl }}/login" target="_blank"
               style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;letter-spacing:-0.005em;">
                Sign in to {{ config('app.name') }}
            </a>
        </td>
    </tr>
</table>

<h3 style="margin:0 0 12px 0;font-size:14px;font-weight:600;color:#0f172a;">A few things to know</h3>
<ul style="margin:0 0 24px 0;padding:0 0 0 18px;font-size:14px;line-height:22px;color:#475569;">
    <li style="margin-bottom:6px;">Your PIN is hashed — even admins can't read it.</li>
    <li style="margin-bottom:6px;">Install {{ config('app.name') }} as an app (browser menu → <em>Install app</em>) to get push notifications when chats arrive.</li>
    <li style="margin-bottom:6px;">Forgot your PIN later? An admin can issue a temporary one — and if you keep this email on file, the temp PIN comes straight to you.</li>
</ul>

<p style="margin:24px 0 0 0;padding-top:20px;border-top:1px solid #e2e8f0;font-size:12px;line-height:18px;color:#94a3b8;">
    Didn't sign up for {{ config('app.name') }}? Reply to this email — we'll investigate and remove the account.
</p>

</x-emails.layout>
