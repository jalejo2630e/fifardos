@php $appUrl = rtrim(config('app.url'), '/'); @endphp
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>{{ $title ?? 'FIFARDOS' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#0a0a0c; color:#e9e9ee; -webkit-font-smoothing:antialiased; font-family:'Helvetica Neue',Arial,sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{ $preheader ?? '' }}</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0a0a0c; padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">

                    {{-- Header / logo --}}
                    <tr>
                        <td align="center" style="padding:8px 0 22px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle; padding-right:10px;">
                                        <img src="{{ $appUrl }}/icon-192.png" width="40" height="40" alt="FIFARDOS"
                                             style="display:block; width:40px; height:40px; border-radius:10px;">
                                    </td>
                                    <td style="vertical-align:middle; font-size:24px; font-weight:800; letter-spacing:1px; color:#ffffff;">
                                        FIFA<span style="color:#ff5f00;">RDOS</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background-color:#131313; border:1px solid rgba(255,255,255,0.06); border-radius:18px; overflow:hidden;">
                            {{-- accent bar --}}
                            <div style="height:5px; background:linear-gradient(90deg,#ff5f00,#ff9248);"></div>
                            <div style="padding:34px 34px 30px;">
                                @yield('content')
                            </div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:22px 16px 6px; color:#7a7a82; font-size:12px; line-height:1.6;">
                            Recibes este correo porque tienes un torneo en FIFARDOS.<br>
                            <a href="{{ $appUrl }}" style="color:#ff7a3d; text-decoration:none;">FIFARDOS</a> · Organiza tus torneos de FIFA con los panas.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
