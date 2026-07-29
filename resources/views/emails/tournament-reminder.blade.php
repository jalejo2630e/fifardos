@php
    $title = 'Recordatorio de tu torneo';
    $preheader = 'Tu torneo «' . $tournament->name . '» te espera. ' . $pendingMatches . ' partido(s) por jugar.';
@endphp

@extends('emails.layout')

@section('content')
    <p style="margin:0 0 6px; font-size:13px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#ff7a3d;">
        ⚽ Recordatorio de torneo
    </p>

    <h1 style="margin:0 0 16px; font-size:26px; line-height:1.2; color:#ffffff; font-weight:800;">
        Tu torneo «{{ $tournament->name }}» te espera
    </h1>

    <p style="margin:0 0 22px; font-size:15px; line-height:1.65; color:#c7c7cf;">
        Programaste un recordatorio para este torneo. Todavía hay partidos por definir,
        así que junta a los panas, prende la consola y a seguir jugando. 🎮
    </p>

    {{-- Stats --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 26px;">
        <tr>
            <td width="50%" style="padding:0 6px 0 0;">
                <div style="background-color:#1c1b1b; border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:16px 18px;">
                    <div style="font-size:28px; font-weight:800; color:#ff5f00; line-height:1;">{{ $playersCount }}</div>
                    <div style="font-size:12px; color:#8a8a92; margin-top:4px; text-transform:uppercase; letter-spacing:.5px;">Jugadores</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 6px;">
                <div style="background-color:#1c1b1b; border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:16px 18px;">
                    <div style="font-size:28px; font-weight:800; color:#ffffff; line-height:1;">{{ $pendingMatches }}</div>
                    <div style="font-size:12px; color:#8a8a92; margin-top:4px; text-transform:uppercase; letter-spacing:.5px;">Partidos por jugar</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- CTA --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
        <tr>
            <td style="border-radius:10px; background:linear-gradient(135deg,#ff7a3d,#ff5f00);">
                <a href="{{ $url }}"
                   style="display:inline-block; padding:14px 30px; font-size:15px; font-weight:800; color:#1a0a03; text-decoration:none; border-radius:10px;">
                    Ver mi torneo →
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:22px 0 0; font-size:13px; line-height:1.6; color:#7a7a82;">
        Si el botón no funciona, copia y pega este enlace:<br>
        <a href="{{ $url }}" style="color:#ff7a3d; text-decoration:none; word-break:break-all;">{{ $url }}</a>
    </p>
@endsection
