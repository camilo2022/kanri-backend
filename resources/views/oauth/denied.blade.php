<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin permiso - Kanri</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .caja { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.15); padding: 32px; width: 100%; max-width: 420px; text-align: center; }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { color: #374151; font-size: 14px; margin: 0; }
    </style>
</head>
<body>
    <div class="caja">
        <h1>No tienes permiso</h1>
        <p>El usuario {{ $username }} no puede conectar el asistente. Pide a un administrador de Kanri que te dé el permiso.</p>
    </div>
</body>
</html>
