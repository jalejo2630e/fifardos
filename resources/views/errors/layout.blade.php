<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · FIFARDOS</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Barlow+Condensed:wght@600;700&family=Chakra+Petch:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #ff5f00; --accent-hover: #ff7a26; --bg: #08080a;
            --tp: #f2f2f0; --tm: #8f8f8b;
            --f-anton: 'Anton', Impact, sans-serif;
            --f-barlow: 'Barlow Condensed', sans-serif;
            --f-body: 'Chakra Petch', system-ui, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; background: var(--bg); color: var(--tp);
            font-family: var(--f-body); display: flex; align-items: center; justify-content: center;
            padding: 24px; position: relative; overflow: hidden; text-align: center;
        }
        ::selection { background: var(--accent); color: var(--bg); }
        .glow { position: absolute; top: -180px; left: 50%; transform: translateX(-50%); width: 720px; height: 720px; pointer-events: none;
            background: radial-gradient(circle, rgba(255,95,0,.16), transparent 62%); }
        .grid { position: absolute; inset: 0; pointer-events: none;
            background-image: linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 60px 60px; mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 30%, transparent 100%); }
        .wrap { position: relative; z-index: 1; max-width: 560px; }
        .logo { font-family: var(--f-anton); font-size: 26px; letter-spacing: -.5px; color: var(--tp); text-decoration: none; transform: skewX(-8deg); display: inline-block; margin-bottom: 30px; }
        .logo span { color: var(--accent); }
        .code { font-family: var(--f-anton); font-size: clamp(96px, 22vw, 190px); line-height: .85; letter-spacing: -3px;
            background: linear-gradient(120deg, var(--accent), #ffb37a); -webkit-background-clip: text; background-clip: text; color: transparent; }
        h1 { font-family: var(--f-anton); text-transform: uppercase; font-size: clamp(26px, 5vw, 40px); letter-spacing: -.5px; margin: 6px 0 12px; }
        p { color: var(--tm); font-size: 16px; line-height: 1.6; margin: 0 auto 30px; max-width: 420px; }
        .actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer;
            font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; font-size: 16px; padding: 13px 26px; transition: background-color .2s, border-color .2s, color .2s; }
        .btn-primary { background: var(--accent); color: var(--bg);
            clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px); }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-ghost { border: 1px solid rgba(255,255,255,.18); color: var(--tp); }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
    </style>
</head>
<body>
    <div class="glow"></div>
    <div class="grid"></div>
    <div class="wrap">
        <a href="/" class="logo">FIFAR<span>DOS</span></a>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a href="/" class="btn btn-primary">Volver al inicio →</a>
            <a href="/tournaments" class="btn btn-ghost">Ver torneos</a>
        </div>
    </div>
</body>
</html>
