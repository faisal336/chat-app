@php($preview = 'Your temporary PIN for '.config('app.name'))
<x-emails.layout :preview="$preview" :previewTitle="'Temporary PIN — '.config('app.name')">

<h1 style="margin:0 0 8px 0;font-size:22px;font-weight:700;letter-spacing:-0.02em;color:#0f172a;">
    Your temporary PIN
</h1>
<p style="margin:0 0 24px 0;font-size:15px;line-height:24px;color:#475569;">
    Hi {{ $user->name }} — an administrator approved your PIN reset request on <strong style="color:#0f172a;">{{ config('app.name') }}</strong>. Use the temporary PIN below to sign in. You'll be asked to choose a new PIN right after.
</p>

{{-- Temporary PIN box --}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px 0;">
    <tr>
        <td align="center" style="background:linear-gradient(135deg,#eef2ff 0%,#faf5ff 100%);border:1px solid #c7d2fe;border-radius:12px;padding:24px;">
            <p style="margin:0 0 8px 0;font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#4338ca;">
                Temporary PIN
            </p>
            <p style="margin:0;font-size:34px;font-weight:700;letter-spacing:0.5em;color:#1e1b4b;font-family:'SF Mono',Menlo,Consolas,monospace;">
                {{ $tempPin }}
            </p>
            <p style="margin:14px 0 0 0;font-size:12px;color:#6366f1;line-height:18px;">
                Sign in within the next 24 hours.
            </p>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
    <tr>
        <td style="background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);border-radius:10px;">
            <a href="{{ config('app.url') }}/login" target="_blank"
               style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;letter-spacing:-0.005em;">
                Sign in now
            </a>
        </td>
    </tr>
</table>

<h3 style="margin:0 0 12px 0;font-size:14px;font-weight:600;color:#0f172a;">Step-by-step</h3>
<ol style="margin:0 0 24px 0;padding:0 0 0 20px;font-size:14px;line-height:22px;color:#475569;">
    <li style="margin-bottom:6px;">Go to <strong>{{ config('app.url') }}/login</strong>.</li>
    <li style="margin-bottom:6px;">Sign in with your username <strong style="font-family:'SF Mono',Menlo,Consolas,monospace;">@ {{ $user->username }}</strong> and the temporary PIN above.</li>
    <li style="margin-bottom:6px;">You'll be redirected to a screen that asks you to pick a new PIN.</li>
    <li style="margin-bottom:6px;">Pick something you'll remember — avoid <code style="font-family:'SF Mono',Menlo,Consolas,monospace;font-size:13px;background:#f1f5f9;padding:1px 4px;border-radius:3px;">000000</code>, <code style="font-family:'SF Mono',Menlo,Consolas,monospace;font-size:13px;background:#f1f5f9;padding:1px 4px;border-radius:3px;">123456</code>, repeats, etc.</li>
</ol>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;margin:0 0 12px 0;">
    <tr>
        <td style="padding:14px 16px;">
            <p style="margin:0;font-size:13px;line-height:20px;color:#78350f;">
                <strong>Didn't request this?</strong> Reply to this email immediately — someone may be trying to take over your account. The temporary PIN is single-use and expires after you sign in.
            </p>
        </td>
    </tr>
</table>

<p style="margin:24px 0 0 0;padding-top:20px;border-top:1px solid #e2e8f0;font-size:12px;line-height:18px;color:#94a3b8;">
    For your security, this email was sent to the address you have on file. {{ config('app.name') }} staff will never ask for your PIN over email, chat, or phone.
</p>

</x-emails.layout>
