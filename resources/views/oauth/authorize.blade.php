<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autorizar acceso · FIFARDOS</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #0b0f19; color: #e6e9f0; padding: 24px;
        }
        .card {
            width: 100%; max-width: 440px; background: #131a2a; border: 1px solid #223052;
            border-radius: 16px; padding: 28px 26px; box-shadow: 0 20px 60px rgba(0,0,0,.45);
        }
        h1 { font-size: 20px; margin: 0 0 6px; }
        .sub { color: #9aa5c0; font-size: 14px; margin: 0 0 20px; }
        .client { font-weight: 700; color: #7cc7ff; }
        .scopes { list-style: none; padding: 0; margin: 0 0 22px; }
        .scopes li {
            padding: 10px 12px; background: #0f1626; border: 1px solid #223052;
            border-radius: 10px; margin-bottom: 8px; font-size: 14px;
        }
        .actions { display: flex; gap: 10px; }
        button {
            flex: 1; padding: 12px 14px; border-radius: 10px; border: 0; cursor: pointer;
            font-size: 15px; font-weight: 600;
        }
        .approve { background: #2f6bff; color: #fff; }
        .deny { background: transparent; color: #cbd3e6; border: 1px solid #33406a; }
        .muted { color: #6b7796; font-size: 12px; margin-top: 16px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Autorizar acceso</h1>
        <p class="sub">
            <span class="client">{{ $client->name }}</span>
            quiere conectarse a tu cuenta de FIFARDOS
            (<strong>{{ $user->name ?? $user->email }}</strong>).
        </p>

        @if (count($scopes) > 0)
            <ul class="scopes">
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        @endif

        <div class="actions">
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="approve">Autorizar</button>
            </form>

            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">Cancelar</button>
            </form>
        </div>

        <p class="muted">Podrás revocar este acceso en cualquier momento desde tu cuenta.</p>
    </div>
</body>
</html>
