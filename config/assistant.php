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
    | la documentación, el callback de OAuth, los enlaces firmados de los PDF (no sirven sin su firma; los PDF con sesión son download_pdf) y los QR de los procesos. Todo lo demás se
    | puede usar con los permisos de la persona.
    |
    */

    'excluded_routes' => [
        'api/auth/*',
        'api/assistant/key/*',
        'api/documentation',
        'api/oauth2-callback',
        'api/technical_sheets/pdf/*',
        'api/technical_sheets/process_pdf/*',
        'api/technical_sheets/production_orders/pdf/*',
        'api/workflow/*/qr/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas POST que solo leen
    |--------------------------------------------------------------------------
    |
    | read_data solo ejecuta GET, salvo estas rutas que generan datos o un archivo sin guardar nada en Kanri.
    |
    */

    'read_post_routes' => [
        'api/reports/*/generate',
        'api/reports/*/export',
    ],

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
