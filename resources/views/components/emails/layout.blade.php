<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<title>{{ $previewTitle ?? config('app.name') }}</title>
<!--[if mso]>
<style type="text/css">table, td, div, p, a { font-family: Arial, sans-serif !important; }</style>
<![endif]-->
</head>
<body style="margin:0;padding:0;width:100%;background:#f5f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#0f172a;-webkit-font-smoothing:antialiased;">

{{-- Hidden preview text (shows in inbox preview line) --}}
@if(isset($preview))
<div style="display:none;font-size:1px;color:#f5f5f7;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
    {{ $preview }}
</div>
@endif

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f7;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:600px;width:100%;">

                {{-- Header / brand --}}
                <tr>
                    <td style="padding:0 8px 16px 8px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td align="left" style="vertical-align:middle;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="background:linear-gradient(135deg,#3b82f6 0%,#8b5cf6 100%);border-radius:10px;padding:8px 10px;vertical-align:middle;" valign="middle">
                                                <span style="color:#ffffff;font-size:14px;font-weight:700;letter-spacing:-0.01em;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">💬</span>
                                            </td>
                                            <td style="padding-left:10px;vertical-align:middle;" valign="middle">
                                                <span style="color:#0f172a;font-size:16px;font-weight:700;letter-spacing:-0.01em;">{{ config('app.name') }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Card --}}
                <tr>
                    <td style="background:#ffffff;border-radius:16px;border:1px solid #e2e8f0;padding:0;box-shadow:0 1px 3px rgba(15,23,42,0.04);">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            {{-- Hero strip (gradient) --}}
                            <tr>
                                <td style="height:4px;background:linear-gradient(90deg,#3b82f6 0%,#8b5cf6 50%,#ec4899 100%);border-radius:16px 16px 0 0;font-size:0;line-height:0;">&nbsp;</td>
                            </tr>
                            {{-- Body --}}
                            <tr>
                                <td style="padding:40px 40px 32px 40px;">
                                    {{ $slot }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:24px 16px 0 16px;text-align:center;">
                        <p style="margin:0 0 8px 0;font-size:12px;color:#64748b;line-height:18px;">
                            You're receiving this email because you have an account on
                            <a href="{{ config('app.url') }}" style="color:#3b82f6;text-decoration:none;">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a>.
                        </p>
                        <p style="margin:0;font-size:11px;color:#94a3b8;line-height:16px;">
                            © {{ date('Y') }} {{ config('app.name') }}. Sent {{ now()->format('M j, Y · H:i') }} UTC.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
