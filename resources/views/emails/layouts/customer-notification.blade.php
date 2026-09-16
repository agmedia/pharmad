<!doctype html>
<html lang="hr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>@yield('email_title', 'Ljekarne PharmAD')</title>
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #f3f7f5;
        }

        table, td {
            mso-table-lspace: 0 !important;
            mso-table-rspace: 0 !important;
            border-collapse: collapse !important;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        a { text-decoration: none; }

        .ag-mail-tableset {
            padding: 9px 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 22px;
            color: #4f5e54;
        }

        @media screen and (max-width: 640px) {
            .mail-shell { width: 100% !important; border-radius: 0 !important; }
            .mail-gutter { padding-left: 24px !important; padding-right: 24px !important; }
        }
    </style>
    @stack('css')
</head>
<body style="margin:0;padding:0;background-color:#f3f7f5;color:#25342b;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
    @yield('preheader')
</div>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f3f7f5">
    <tr>
        <td align="center" style="padding:32px 12px;">
            <table role="presentation" width="620" cellspacing="0" cellpadding="0" border="0" class="mail-shell" style="width:100%;max-width:620px;background-color:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(37,52,43,.08);">
                <tr>
                    <td height="5" style="height:5px;background-color:#26b67f;font-size:0;line-height:0;">&nbsp;</td>
                </tr>
                <tr>
                    <td align="center" bgcolor="#ffffff" style="padding:26px 32px 24px;background-color:#ffffff;border-bottom:1px solid #e3eee9;">
                        <a href="{{ config('app.url') }}" target="_blank" style="display:inline-block;">
                            <img src="{{ asset('media/img/logo-ljekarne-pharmad.png') }}" width="200" alt="Ljekarne PharmAD" style="display:block;width:200px;max-width:100%;height:auto;">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="mail-gutter" style="padding:44px 48px 42px;background-color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td class="mail-gutter" align="center" style="padding:26px 48px 28px;background-color:#f7faf8;border-top:1px solid #e3eee9;font-family:Arial,Helvetica,sans-serif;">
                        <p style="margin:0 0 12px;font-size:12px;line-height:18px;">
                            <a href="{{ config('app.url') }}" style="color:#1f7f5b;font-weight:bold;">Posjetite webshop</a>
                            <span style="padding:0 7px;color:#26b67f;">•</span>
                            <a href="mailto:webshop@ljekarne-pharmad.hr" style="color:#1f7f5b;font-weight:bold;">webshop@ljekarne-pharmad.hr</a>
                        </p>
                        <p style="margin:0;font-size:11px;line-height:17px;color:#8a8f89;">Ljekarne PharmAD © {{ now()->year }}. Sva prava pridržana.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
