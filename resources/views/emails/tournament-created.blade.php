@php
    $title = 'Torneo creado';
    $preheader = 'Tu torneo «' . $tournament->name . '» ya está listo con ' . $playersCount . ' jugadores.';
    $reminderText = $reminderAt
        ? \Illuminate\Support\Carbon::parse($reminderAt)->locale('es')->translatedFormat('l j \d\e F, g:i A')
        : null;
@endphp

@extends('emails.layout')

@section('content')
    <p style="margin:0 0 6px; font-size:13px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#ff7a3d;">
        🏆 Torneo creado
    </p>

    <h1 style="margin:0 0 16px; font-size:26px; line-height:1.2; color:#ffffff; font-weight:800;">
        «{{ $tournament->name }}» ya está en marcha
    </h1>

    <p style="margin:0 0 22px; font-size:15px; line-height:1.65; color:#c7c7cf;">
        Generamos el fixture de la fase de grupos automáticamente. Ya puedes empezar a cargar
        los resultados y seguir la tabla de posiciones en vivo.
    </p>

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
                    <div style="font-size:28px; font-weight:800; color:#ffffff; line-height:1;">{{ $matchesCount }}</div>
                    <div style="font-size:12px; color:#8a8a92; margin-top:4px; text-transform:uppercase; letter-spacing:.5px;">Partidos de grupos</div>
                </div>
            </td>
        </tr>
    </table>

    @if($reminderText)
    <div style="background-color:rgba(255,95,0,0.08); border:1px solid rgba(255,95,0,0.25); border-radius:12px; padding:14px 18px; margin:0 0 26px;">
        <span style="font-size:14px; color:#ffb68d;">⏰ Te enviaremos un recordatorio el <strong style="color:#ffffff;">{{ $reminderText }}</strong>.</span>
    </div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:10px; background:linear-gradient(135deg,#ff7a3d,#ff5f00);">
                <a href="{{ $url }}"
                   style="display:inline-block; padding:14px 30px; font-size:15px; font-weight:800; color:#1a0a03; text-decoration:none; border-radius:10px;">
                    Abrir torneo →
                </a>
            </td>
        </tr>
    </table>
@endsection
