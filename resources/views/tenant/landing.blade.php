<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenant?->name ?? 'Bienvenido' }} • Kartenant</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; margin:0; }
        .wrap { min-height:100dvh; display:grid; place-items:center; padding:24px; background: linear-gradient(135deg, #0ea5e9 0%, #1e293b 100%); }
        .card { width:min(720px, 100%); background: white; color:#0f172a; border-radius:16px; box-shadow: 0 10px 30px rgba(0,0,0,.15); overflow:hidden; }
        @media (prefers-color-scheme: dark) { .card { background:#0b1220; color:#e5e7eb; } }
        .hero { padding:28px 24px; background: rgba(255,255,255,0.08); backdrop-filter: blur(6px); display:flex; align-items:center; gap:16px; }
        .logo { inline-size:44px; block-size:44px; border-radius:10px; background:#0284c7; display:grid; place-items:center; color:white; font-weight:700; }
        .content { padding:28px 24px; }
        .title { font-size: clamp(22px, 2.4vw, 28px); margin:0 0 8px; }
        .subtitle { margin:0; opacity:.8; }
        .actions { margin-top:22px; display:flex; gap:12px; flex-wrap:wrap; }
        .btn { appearance:none; border:0; padding:12px 16px; border-radius:10px; cursor:pointer; font-weight:600; }
        .btn-primary { background:#0ea5e9; color:white; }
        .btn-primary:hover { background:#0284c7; }
        .muted { font-size:14px; opacity:.8; margin-top:16px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="hero">
            <div class="logo">ED</div>
            <div>
                <h1 class="title">{{ $tenant?->name ?? parse_url(request()->root(), PHP_URL_HOST) }}</h1>
                <p class="subtitle">Tu espacio en Kartenant</p>
            </div>
        </div>
        <div class="content">
            <p>Bienvenido al portal de tu empresa. Desde aquí podrás gestionar inventario, ventas y más.</p>
            <div class="actions">
                <a class="btn btn-primary" href="/app/login">Ingresar al panel</a>
            </div>
            <p class="muted">Dominio: {{ request()->getHost() }}</p>
        </div>
    </div>
</div>
</body>
</html>
