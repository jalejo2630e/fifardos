@php
    $title = 'Nuevo mensaje de contacto';
    $preheader = $senderName . ' te escribió desde el formulario del landing.';
@endphp

@extends('emails.layout')

@section('content')
    <p style="margin:0 0 6px; font-size:13px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#ff7a3d;">
        ✉️ Formulario de contacto
    </p>

    <h1 style="margin:0 0 16px; font-size:26px; line-height:1.2; color:#ffffff; font-weight:800;">
        {{ $senderName }}
    </h1>

    <p style="margin:0 0 22px; font-size:15px; line-height:1.65; color:#c7c7cf;">
        <a href="mailto:{{ $senderEmail }}" style="color:#ff8a3d; text-decoration:none;">{{ $senderEmail }}</a>
        · escribió desde el formulario del landing. Puedes responder directamente a este correo.
    </p>

    <div style="background-color:#1c1b1b; border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:18px 20px;">
        <div style="font-size:12px; color:#8a8a92; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px;">Mensaje</div>
        <div style="font-size:15px; line-height:1.65; color:#e7e7ea; white-space:pre-wrap;">{{ $body }}</div>
    </div>
@endsection
