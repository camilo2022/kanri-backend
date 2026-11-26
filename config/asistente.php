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

    'mcp_token_anios' => (int) env('MCP_TOKEN_ANIOS', 10),

    /*
    |--------------------------------------------------------------------------
    | Llave de IA (Sanctum, name = ai_token)
    |--------------------------------------------------------------------------
    */

    'ia_token_inactividad_min' => (int) env('IA_TOKEN_INACTIVIDAD_MIN', 30),

    'ia_token_duracion_max_horas' => (int) env('IA_TOKEN_DURACION_MAX_HORAS', 4),

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

    'peticiones_por_minuto' => (int) env('MCP_PETICIONES_POR_MINUTO', 60),

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
    | pueden ejecutar y no tienen prompt. Son rutas de acceso, de la llave de IA, de
    | documentación, de ajustes globales y las que devuelven o reciben archivos.
    |
    */

    'excluded_routes' => [
        'api/auth/*',
        'api/assistant/key/*',
        'api/documentation',
        'api/oauth2-callback',
        'api/collections/setting/*',
        'api/reports/*/export',
        'api/technical_sheets/pdf/*',
        'api/technical_sheets/process_pdf/*',
        'api/technical_sheets/production_orders/pdf/*',
        'api/typification/supply_types/supplies/export/*',
        'api/typification/supply_types/supplies/import/*',
        'api/workflow/*/qr/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas POST que solo leen
    |--------------------------------------------------------------------------
    |
    | read_data solo ejecuta GET, salvo estas rutas que generan datos sin guardar nada.
    |
    */

    'read_post_routes' => [
        'api/reports/*/generate',
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

    'max_response_chars' => (int) env('ASSISTANT_MAX_RESPONSE_CHARS', 60000),

];
