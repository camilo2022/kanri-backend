<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorizar aplicación - Kanri</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .caja { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.15); padding: 32px; width: 100%; max-width: 420px; }
        h1 { font-size: 22px; margin: 0 0 16px; text-align: center; }
        .dato { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; font-size: 14px; word-break: break-all; }
        .dato span { display: block; color: #6b7280; font-size: 12px; margin-bottom: 2px; }
        ul { padding-left: 18px; font-size: 14px; color: #374151; }
        .botones { display: flex; gap: 12px; margin-top: 20px; }
        .botones form { flex: 1; }
        button { width: 100%; padding: 11px; border-radius: 6px; font-size: 15px; cursor: pointer; }
        .autorizar { border: 0; background: #1f2937; color: #fff; }
        .cancelar { border: 1px solid #d1d5db; background: #fff; color: #111827; }
    </style>
</head>
<body>
    <div class="caja">
        <h1>Autorizar a {{ $client->name }}</h1>

        <div class="dato">
            <span>Esta aplicación trabajará a nombre del usuario</span>
            {{ $user->username }}
        </div>

        <div class="dato">
            <span>Al terminar volverá a este sitio</span>
            {{ parse_url((string) request()->query('redirect_uri'), PHP_URL_HOST) ?: 'desconocido' }}
        </div>

        @if (count($scopes) > 0)
            <p style="font-size:14px;margin:16px 0 4px">Podrá:</p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        @endif

        <div class="botones">
            <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="cancelar">Cancelar</button>
            </form>

            <form method="POST" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="autorizar">Autorizar</button>
            </form>
        </div>
    </div>
</body>
</html>
