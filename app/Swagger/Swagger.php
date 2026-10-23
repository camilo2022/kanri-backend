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
 *     name="Authorization › Permissions",
 *     description="Endpoints para gestionar permisos"
 * )
 *
 * @OA\Tag(
 *     name="Authorization › Roles",
 *     description="Endpoints para gestionar roles y sus permisos"
 * )
 *
 * @OA\Tag(
 *     name="Navegation › Modules",
 *     description="Endpoints para gestionar módulos del menú"
 * )
 *
 * @OA\Tag(
 *     name="Navegation › Submodules",
 *     description="Endpoints para gestionar submódulos de cada módulo"
 * )
 *
 * @OA\Tag(
 *     name="Organizational Structure › Areas",
 *     description="Endpoints para gestionar áreas"
 * )
 *
 * @OA\Tag(
 *     name="Organizational Structure › Positions",
 *     description="Endpoints para gestionar cargos de cada área y sus permisos"
 * )
 *
 * @OA\Tag(
 *     name="Categorization › Categories",
 *     description="Endpoints para gestionar categorías"
 * )
 *
 * @OA\Tag(
 *     name="Categorization › Subcategories",
 *     description="Endpoints para gestionar subcategorías de cada categoría"
 * )
 *
 * @OA\Tag(
 *     name="Classification › Groups",
 *     description="Endpoints para gestionar grupos"
 * )
 *
 * @OA\Tag(
 *     name="Classification › Subgroups",
 *     description="Endpoints para gestionar subgrupos de cada grupo"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Account Types",
 *     description="Endpoints para gestionar tipos de cuenta"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Back Types",
 *     description="Endpoints para gestionar tipos de trasero"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Blood Types",
 *     description="Endpoints para gestionar tipos de sangre"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Boot Types",
 *     description="Endpoints para gestionar tipos de bota"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Document Types",
 *     description="Endpoints para gestionar tipos de documento de cada tipo de persona"
 * )
 *
 * @OA\Tag(
 *     name="Typification › File Subtypes",
 *     description="Endpoints para gestionar subtipos de archivo de cada tipo de archivo"
 * )
 *
 * @OA\Tag(
 *     name="Typification › File Types",
 *     description="Endpoints para gestionar tipos de archivo"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Garment Types",
 *     description="Endpoints para gestionar tipos de prenda"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Person Types",
 *     description="Endpoints para gestionar tipos de persona"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Stop Types",
 *     description="Endpoints para gestionar tipos de parada"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Stops",
 *     description="Endpoints para gestionar paradas de cada tipo de parada"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Supplier Types",
 *     description="Endpoints para gestionar tipos de proveedor"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Suppliers",
 *     description="Endpoints para gestionar proveedores de cada tipo de proveedor"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Supplies",
 *     description="Endpoints para gestionar insumos de cada tipo de insumo"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Supply Types",
 *     description="Endpoints para gestionar tipos de insumo"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Typologies",
 *     description="Endpoints para gestionar tipologías"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Waistband Types",
 *     description="Endpoints para gestionar tipos de pretina"
 * )
 *
 * @OA\Tag(
 *     name="Typification › Yoke Types",
 *     description="Endpoints para gestionar tipos de cotilla"
 * )
 *
 * @OA\Tag(
 *     name="Workflow › Operations",
 *     description="Endpoints para gestionar operaciones de cada subproceso"
 * )
 *
 * @OA\Tag(
 *     name="Workflow › Processes",
 *     description="Endpoints para gestionar procesos"
 * )
 *
 * @OA\Tag(
 *     name="Workflow › Subprocesses",
 *     description="Endpoints para gestionar subprocesos de cada proceso"
 * )
 *
 * @OA\Tag(
 *     name="Management › Collections",
 *     description="Endpoints para gestionar las fichas técnicas de las colecciones"
 * )
 *
 * @OA\Tag(
 *     name="Management › Production",
 *     description="Endpoints para gestionar las órdenes de producción de las colecciones"
 * )
 *
 * @OA\Tag(
 *     name="Movements › Movements",
 *     description="Endpoints para gestionar los movimientos y traslados de unidades entre procesos"
 * )
 *
 * @OA\Tag(
 *     name="Movements › Production Order Details",
 *     description="Endpoints para gestionar las curvas de los lotes que se usan en los traslados"
 * )
 *
 * @OA\Tag(
 *     name="Cache › Builders",
 *     description="Endpoints para gestionar constructores de curvas en caché"
 * )
 *
 * @OA\Schema(
 *   schema="Role",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="users"),
 *   @OA\Property(property="guard_name", type="string", example="api"),
 *   @OA\Property(property="title", type="string", example="Usuarios."),
 *   @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z")
 * )
 *
 * @OA\Schema(
 *   schema="Permission",
 *   type="object",
 *   description="Permiso de Spatie (tabla `permissions`), serializado por Eloquent. El nombre es `<prefijo.de.la.ruta>.<acción>`.",
 *   @OA\Property(property="id", type="integer", example=371),
 *   @OA\Property(property="name", type="string", example="dispatches.all"),
 *   @OA\Property(property="guard_name", type="string", example="api"),
 *   @OA\Property(property="title", type="string", example="Listar despachos."),
 *   @OA\Property(property="description", type="string", example="Permite listar despachos."),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z")
 * )
 *
 * @OA\Schema(
 *   schema="UnauthorizedResponse",
 *   type="object",
 *   @OA\Property(property="message", type="string", example="No autenticado."),
 *   @OA\Property(property="error", type="string", example="Unauthenticated.")
 *)
 *
 * @OA\Schema(
 *    schema="ServerErrorResponse",
 *    type="object",
 *    @OA\Property(
 *        property="error",
 *        type="object",
 *        @OA\Property(property="message", type="string", example="Error en la base de datos, por favor, inténtelo de nuevo más tarde."),
 *        @OA\Property(property="error", type="string", example="SQLSTATE[HY000] [2002] No se pudo establecer la conexión.")
 *    )
 *)
 *
 * ---------------------------------------------------------------------------
 * Componentes compartidos. Cada operación los referencia con `ref` en lugar
 * de repetir los bloques de paginación, 401, 403, 422 y 500.
 * ---------------------------------------------------------------------------
 *
 * @OA\Schema(
 *    schema="ForbiddenResponse",
 *    type="object",
 *    @OA\Property(
 *        property="error",
 *        type="object",
 *        @OA\Property(
 *            property="message",
 *            type="string",
 *            example="No está autorizado para realizar esta acción. No cuentas con ninguno de los permisos: Listar tipos de cuenta. Contacte al administrador para obtener asistencia o solicitar autorización."
 *        )
 *    )
 *)
 *
 * @OA\Schema(
 *    schema="ValidationErrorResponse",
 *    type="object",
 *    @OA\Property(property="message", type="string", example="Error de validación."),
 *    @OA\Property(
 *        property="attributes",
 *        type="object",
 *        description="Nombre legible de cada campo validado.",
 *        additionalProperties=@OA\AdditionalProperties(type="string"),
 *        example={"id": "Identificador del registro"}
 *    ),
 *    @OA\Property(
 *        property="errors",
 *        type="object",
 *        description="Mensajes de error por campo.",
 *        additionalProperties=@OA\AdditionalProperties(type="array", @OA\Items(type="string")),
 *        example={"id": {"No hay ningún registro."}}
 *    )
 *)
 *
 * @OA\Schema(
 *    schema="PaginationMeta",
 *    type="object",
 *    @OA\Property(
 *        property="pagination",
 *        type="object",
 *        @OA\Property(property="total", type="integer", example=24),
 *        @OA\Property(property="count", type="integer", example=10),
 *        @OA\Property(property="per_page", type="integer", example=10),
 *        @OA\Property(property="current_page", type="integer", example=1),
 *        @OA\Property(property="total_pages", type="integer", example=3)
 *    )
 *)
 *
 * @OA\Schema(
 *    schema="Subitem",
 *    type="object",
 *    description="Fila de catálogo (tabla `subitems`) serializada tal cual por Eloquent: marcas, categorías, tallas, etc.",
 *    @OA\Property(property="id", type="integer", example=101),
 *    @OA\Property(property="item_id", type="integer", example=16),
 *    @OA\Property(property="name", type="string", example="6"),
 *    @OA\Property(property="description", type="string", example="TALLA 6"),
 *    @OA\Property(property="settings", type="object", nullable=true),
 *    @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-02T10:30:00.000000Z"),
 *    @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-02T10:30:00.000000Z"),
 *    @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *)
 *
 * @OA\Schema(
 *    schema="File",
 *    type="object",
 *    description="Archivo guardado (foto o documento) asociado a un registro por `model_type` y `model_id`.",
 *    @OA\Property(property="id", type="integer", example=3120),
 *    @OA\Property(property="model_type", type="string", example="App\Models\TechnicalSheet"),
 *    @OA\Property(property="model_id", type="integer", example=905),
 *    @OA\Property(property="name", type="string", description="Nombre original sin extensión.", example="DC-000245-D"),
 *    @OA\Property(property="file_type_id", type="integer", example=3),
 *    @OA\Property(property="file_subtype_id", type="integer", example=9),
 *    @OA\Property(property="path", type="string", description="URL pública del archivo.", example="http://kanri-backend.test/storage/technical_sheet/G2JV2HU7KG2uQiuzVhuvBvUXzay5A08P.jpg"),
 *    @OA\Property(property="mime", type="string", example="image/jpeg"),
 *    @OA\Property(property="extension", type="string", example="jpg"),
 *    @OA\Property(property="size", type="integer", description="Tamaño en bytes.", example=22829),
 *    @OA\Property(property="metadata", type="object", additionalProperties=true, description="Datos técnicos: tamaño, dimensiones, hashes, nombre original, fecha de carga.", example={"mime": "image/jpeg", "size": 22829, "image": {"width": 450, "height": 514}, "original_name": "DC-000245-D.jpg", "uploaded_at": "2026-05-14 16:00:00"}),
 *    @OA\Property(property="settings", type="object", nullable=true),
 *    @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T21:00:00.000000Z"),
 *    @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T21:00:00.000000Z")
 *)
 *
 * @OA\Response(
 *    response="Unauthorized",
 *    description="No autenticado. Falta el token Bearer o no es válido.",
 *    @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
 *)
 *
 * @OA\Response(
 *    response="Forbidden",
 *    description="El usuario no tiene el permiso que exige la ruta.",
 *    @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
 *)
 *
 * @OA\Response(
 *    response="RecordNotFound",
 *    description="El identificador no corresponde a un registro válido para esta operación.",
 *    @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
 *)
 *
 * @OA\Response(
 *    response="ServerError",
 *    description="Error del servidor.",
 *    @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse")
 *)
 *
 * @OA\Parameter(
 *    parameter="PathId",
 *    name="id",
 *    in="path",
 *    required=true,
 *    description="Identificador del registro.",
 *    @OA\Schema(type="integer", example=1)
 *)
 *
 * @OA\Parameter(
 *    parameter="Search",
 *    name="search",
 *    in="query",
 *    required=false,
 *    description="Texto a buscar. Cada palabra se busca en el nombre y la descripción.",
 *    @OA\Schema(type="string")
 *)
 *
 * @OA\Parameter(
 *    parameter="Dir",
 *    name="dir",
 *    in="query",
 *    required=false,
 *    description="Dirección de ordenamiento. Solo se aplica junto con `column`.",
 *    @OA\Schema(type="string", enum={"asc", "desc"}, example="asc")
 *)
 *
 * @OA\Parameter(
 *    parameter="PerPage",
 *    name="per_page",
 *    in="query",
 *    required=false,
 *    description="Registros por página. Si no se envía, devuelve todos en una sola página.",
 *    @OA\Schema(type="integer", example=10)
 *)
 *
 * @OA\Parameter(
 *    parameter="Page",
 *    name="page",
 *    in="query",
 *    required=false,
 *    description="Número de página.",
 *    @OA\Schema(type="integer", example=1)
 *)
 *
 * @OA\Parameter(
 *    parameter="WithTrashed",
 *    name="with_trashed",
 *    in="query",
 *    required=true,
 *    description="Incluir los registros desactivados: 1 = sí, 0 = no.",
 *    @OA\Schema(type="integer", enum={0, 1}, example=0)
 *)
 *
 * @OA\Parameter(
 *    parameter="WithTrashedOptional",
 *    name="with_trashed",
 *    in="query",
 *    required=false,
 *    description="Incluir los registros desactivados. Opcional (si no se envía, no se incluyen); acepta `true`/`false` o `1`/`0`. Para Requests que convierten el valor en `prepareForValidation()`.",
 *    @OA\Schema(type="boolean", example=false)
 *)
 */
class Swagger
{
    // Solo contiene anotaciones
}
