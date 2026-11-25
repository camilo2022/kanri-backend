<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conector de Claude (Passport)
    |--------------------------------------------------------------------------
    |
    | Horas que dura el token de acceso del conector. El token de refresco no
    | puede durar más que el de acceso: a esa hora hay que volver a conectar.
    |
    */

    'mcp_token_horas' => (int) env('MCP_TOKEN_HORAS', 12),

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
    | Techo de permisos
    |--------------------------------------------------------------------------
    |
    | Únicos permisos de Kanri que el MCP puede usar, aunque el actor tenga más.
    | Se llena herramienta por herramienta.
    |
    */

    'permisos_permitidos' => [],

];
