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
    | Llave de IA (Sanctum, name = ia_token)
    |--------------------------------------------------------------------------
    */

    'ia_token_inactividad_min' => (int) env('IA_TOKEN_INACTIVIDAD_MIN', 30),

    'ia_token_duracion_max_horas' => (int) env('IA_TOKEN_DURACION_MAX_HORAS', 4),

    /*
    |--------------------------------------------------------------------------
    | Código de confirmación de las escrituras
    |--------------------------------------------------------------------------
    */

    'codigo_minutos' => (int) env('ASISTENTE_CODIGO_MINUTOS', 10),

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
    | Si está activo, las herramientas que escriben responden «solo_lectura».
    |
    */

    'solo_lectura' => (bool) env('KANRI_SOLO_LECTURA', true),

];
