<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="API Laravel Sanctum",
 *         version="1.0.0",
 *         description="Documentación de endpoints de autenticación"
 *     ),
 *     @OA\Server(
 *         url=L5_SWAGGER_CONST_HOST,
 *         description="Servidor API"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Laravel Sanctum authentication"
 * )
 *
 * @OA\Tag(
 *     name="Authorization",
 *     description="Endpoints para gestionar roles y permisos del sistema"
 * )
 *
 * @OA\Tag(
 *     name="Navegation",
 *     description="Endpoints para gestionar módulos y submódulos del sistema"
 * )
 */
class Swagger
{
    // Solo contiene anotaciones
}
