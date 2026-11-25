<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Kanri</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .caja { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.15); padding: 32px; width: 100%; max-width: 360px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        p { color: #6b7280; font-size: 14px; margin: 0 0 20px; }
        label { display: block; font-size: 14px; margin-bottom: 4px; }
        input { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 15px; margin-bottom: 14px; }
        button { width: 100%; padding: 11px; border: 0; border-radius: 6px; background: #1f2937; color: #fff; font-size: 15px; cursor: pointer; }
        .error { background: #fee2e2; color: #991b1b; border-radius: 6px; padding: 10px; font-size: 14px; margin-bottom: 14px; }
    </style>
</head>
<body>
    <form class="caja" method="POST" action="{{ url('/login') }}">
        @csrf
        <h1>Kanri</h1>
        <p>Inicia sesión para continuar.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <label for="username">Nombre de usuario</label>
        <input id="username" name="username" type="text" value="{{ old('username') }}" maxlength="100" autocomplete="username" required autofocus>

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button type="submit">Entrar</button>
    </form>
</body>
</html>
