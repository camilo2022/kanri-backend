<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conector de Claude (Passport)
    |--------------------------------------------------------------------------
    |
    | Años que dura el token de acceso del conector. El conector trabaja con un
    | usuario sin permisos de negocio (solo assistant.connect), así que su token
    | es de larga duración: no hay que volver a conectar cada pocas horas. Passport
    | no tiene «sin vencimiento»; para cortarlo antes se revoca el token a mano.
    |
    */

    'mcp_token_years' => (int) env('MCP_TOKEN_YEARS', 10),

    /*
    |--------------------------------------------------------------------------
    | Llave de IA (Sanctum, name = ai_token)
    |--------------------------------------------------------------------------
    */

    'ai_token_inactivity_minutes' => (int) env('AI_TOKEN_INACTIVITY_MINUTES', 30),

    'ai_token_max_hours' => (int) env('AI_TOKEN_MAX_HOURS', 4),

    /*
    |--------------------------------------------------------------------------
    | Inicio de sesión desde el chat
    |--------------------------------------------------------------------------
    |
    | Minutos que vale el enlace que devuelve start_login. En ese tiempo la persona debe iniciar sesión en la página
    | de Kanri y el chat debe recoger la llave con finish_login.
    |
    */

    'login_minutes' => (int) env('ASSISTANT_LOGIN_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Código de confirmación de las escrituras
    |--------------------------------------------------------------------------
    |
    | Minutos que vale el código firmado que devuelve prepare_write.
    |
    */

    'confirmation_minutes' => (int) env('ASSISTANT_CONFIRMATION_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Peticiones por minuto de cada actor en /mcp
    |--------------------------------------------------------------------------
    */

    'requests_per_minute' => (int) env('MCP_REQUESTS_PER_MINUTE', 60),

    /*
    |--------------------------------------------------------------------------
    | Solo lectura
    |--------------------------------------------------------------------------
    |
    | Si está activo, las herramientas que escriben responden «read_only».
    |
    */

    'read_only' => (bool) env('KANRI_READ_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Rutas que el asistente no puede usar
    |--------------------------------------------------------------------------
    |
    | Patrones sobre la URI de la ruta (Str::is). No se listan en search_routes, no se
    | pueden ejecutar y no tienen prompt. Solo son las de acceso (login), la llave de IA,
    | la documentación, el callback de OAuth, la generación y exportación de reportes (tardan mucho: se hacen en Kanri), los enlaces firmados de los PDF (no sirven sin su firma; los PDF con sesión son download_pdf) y los QR de los procesos. Todo lo demás se
    | puede usar con los permisos de la persona.
    |
    */

    'excluded_routes' => [
        'api/auth/*',
        'api/assistant/key/*',
        'api/documentation',
        'api/oauth2-callback',
        'api/reports/*/generate',
        'api/reports/*/export',
        'api/technical_sheets/pdf/*',
        'api/technical_sheets/process_pdf/*',
        'api/technical_sheets/production_orders/pdf/*',
        'api/workflow/*/qr/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas que el asistente no puede escribir
    |--------------------------------------------------------------------------
    |
    | Rutas de escritura que no se pueden usar desde el chat: las que reciben un archivo, porque el chat no puede
    | enviarlo. Se pueden consultar y preparar a mano en Kanri.
    |
    */

    'write_excluded_routes' => [
        'api/typification/supply_types/supplies/import/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas POST que solo leen
    |--------------------------------------------------------------------------
    |
    | read_data solo ejecuta GET, salvo las rutas POST que se listen aquí y que solo lean (hoy ninguna).
    |
    */

    'read_post_routes' => [],

    /*
    |--------------------------------------------------------------------------
    | Tamaño máximo de una respuesta de read_data
    |--------------------------------------------------------------------------
    |
    | Caracteres del JSON. Si se supera, la herramienta pide a la IA que use per_page,
    | page, search o un filtro, para no llenar su contexto.
    |
    */

    'max_response_chars' => (int) env('ASSISTANT_MAX_RESPONSE_CHARS', 500000),

    /*
    |--------------------------------------------------------------------------
    | Consultas SQL de solo lectura (query_data)
    |--------------------------------------------------------------------------
    |
    | Todas las rutas `all` se consultan por SQL con un usuario de base de datos que solo puede leer (conexión
    | assistant_readonly, ver config/database.php). Qué tablas y con qué permiso: config/assistant_tables.php.
    |
    | sql_max_rows: filas que se devuelven al chat si no se pide otra cantidad; sql_max_rows_limit: máximo que se puede
    | pedir; sql_max_chars: tamaño máximo del resultado en el chat (lo que sobra se corta); sql_timeout_seconds: tiempo
    | máximo de cada consulta; sql_export_max_rows: filas máximas de un Excel.
    |
    */

    'sql_connection' => env('ASSISTANT_SQL_CONNECTION', 'assistant_readonly'),

    'sql_max_rows' => (int) env('ASSISTANT_SQL_MAX_ROWS', 200),

    'sql_max_rows_limit' => (int) env('ASSISTANT_SQL_MAX_ROWS_LIMIT', 1000),

    'sql_max_chars' => (int) env('ASSISTANT_SQL_MAX_CHARS', 100000),

    'sql_timeout_seconds' => (int) env('ASSISTANT_SQL_TIMEOUT_SECONDS', 15),

    'sql_export_max_rows' => (int) env('ASSISTANT_SQL_EXPORT_MAX_ROWS', 50000),

    /*
    |--------------------------------------------------------------------------
    | Archivos que devuelven las rutas
    |--------------------------------------------------------------------------
    |
    | Excel y otros archivos se guardan unos minutos y se entregan con una dirección firmada. file_minutes es lo que
    | dura esa dirección y max_file_mb el tamaño máximo del archivo.
    |
    */

    'file_minutes' => (int) env('ASSISTANT_FILE_MINUTES', 10),

    'max_file_mb' => (int) env('ASSISTANT_MAX_FILE_MB', 20),

];
