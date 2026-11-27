<?php

namespace App\Http\Controllers;

use App\Http\Requests\Audit\AuditTimelineRequest;
use App\Http\Resources\Audit\AuditTimelineCollection;
use App\Models\Productivity;
use App\Models\ProductivityDetail;
use App\Models\Stop;
use App\Support\AuditResolver;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

/**
 * @OA\Tag(
 *     name="Audit",
 *     description="Endpoints para gestionar el historial de cambios de cada registro"
 * )
 *
 * @OA\Schema(
 *     schema="AuditTimelineResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="audits",
 *             type="array",
 *
 *             @OA\Items(
 *                 type="object",
 *
 *                 @OA\Property(property="id", type="integer", example=1842),
 *                 @OA\Property(property="event", type="string", description="Tipo de cambio, o `units` para un bloque de +1 y −1.", enum={"created", "updated", "deleted", "restored", "attach", "detach", "sync", "units"}, example="updated"),
 *                 @OA\Property(property="clicks", type="object", nullable=true, description="Solo en `units`: clics del bloque.", @OA\Property(property="plus", type="integer", example=84), @OA\Property(property="minus", type="integer", example=2)),
 *                 @OA\Property(property="started_at", type="string", nullable=true, description="Solo en `units`: hora del primer clic.", example="2026-10-28 17:46:57"),
 * *                 @OA\Property(property="record", type="string", nullable=true, description="Nombre del registro modificado.", example="BO100000"),
 *                 @OA\Property(property="user", type="string", description="Persona que hizo el cambio; `Sistema` si no hay usuario.", example="Ana Gómez"),
 *                 @OA\Property(property="url", type="string", example="http://kanri-backend.test/api/products/update/12"),
 *                 @OA\Property(property="ip_address", type="string", example="192.168.1.25"),
 *                 @OA\Property(property="user_agent", type="string", nullable=true, example="Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0"),
 *                 @OA\Property(property="origin", type="string", nullable=true, enum={"web", "mobile", "mcp", "api", "system"}, description="Desde dónde se hizo el cambio: la web, la aplicación móvil, el asistente de IA (MCP), la API sin cabecera de cliente o el sistema (consola). Las auditorías anteriores al 2026-11-25 no lo traen.", example="web"),
 *                 @OA\Property(property="old_values", type="object", description="Valores anteriores, con el nombre legible de cada campo.", additionalProperties=true, example={"Marca": "ADIDAS (#7)"}),
 *                 @OA\Property(property="new_values", type="object", description="Valores nuevos, con el nombre legible de cada campo.", additionalProperties=true, example={"Marca": "STARA DENIM (#91)"}),
 *                 @OA\Property(property="created_at", type="string", example="2026-11-05 10:02:45")
 *             )
 *         ),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 */
class AuditController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/products/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un producto",
     *     description="Cada grupo de rutas con listado tiene su `/audit/{id}` (por ejemplo `/typification/supply_types/supplies/audit/{id}`): todas devuelven solo los cambios de ese registro, del más reciente al más antiguo, con nombres legibles. Los eventos sin datos no se incluyen, y los +1 y −1 seguidos de un registro de trabajo llegan como un solo evento `units`.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/technical_sheets/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una ficha técnica",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/technical_sheets/production_orders/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una orden de producción",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/authorization/permissions/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un permiso",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/authorization/roles/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un rol",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/banks/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un banco",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/categorization/categories/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una categoría",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/categorization/categories/subcategories/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una subcategoría",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/classification/groups/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un grupo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/classification/subgroups/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un subgrupo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/collections/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una colección",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/colors/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un color",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/compensation_funds/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una caja de compensación",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/employees/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un empleado",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/genders/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un género",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/health_entities/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una entidad de salud",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/locations/countries/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un país",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/movements/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un movimiento",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/navegation/modules/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un módulo del menú",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/navegation/modules/submodules/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un submódulo del menú",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/organizational_structure/areas/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un área",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/organizational_structure/areas/positions/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un cargo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/pension_funds/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un fondo de pensión",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/people/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una persona",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/pieces/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una pieza",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/risk_managers/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una administradora de riesgos laborales (ARL)",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/rules/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una regla",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/sizes/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una talla",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/trademarks/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una marca",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/account_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de cuenta",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/back_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de trasero",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/blood_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de sangre",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/boot_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de bota",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/file_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de archivo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/file_types/file_subtypes/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un subtipo de archivo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/garment_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de prenda",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/person_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de persona",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/person_types/document_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de documento",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/stop_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de parada",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/stop_types/stops/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una parada",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/supplier_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de proveedor",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/supplier_types/suppliers/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un proveedor",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/supply_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de insumo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/supply_types/supplies/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un insumo",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/typologies/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una tipología",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/waistband_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de pretina",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/typification/yoke_types/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tipo de cotilla",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/users/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un usuario",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/wash_tones/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un tono de lavado",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/workflow/processes/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un proceso",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/workflow/processes/subprocesses/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de un subproceso",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/workflow/processes/subprocesses/operations/audit/{id}",
     *     tags={"Audit"},
     *     summary="Historial de cambios de una operación",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(ref="#/components/schemas/AuditTimelineResponse")),
     *     @OA\Response(response=404, description="El registro no existe."),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function timeline(AuditTimelineRequest $request, $id)
    {
        try {
            $model = $request->route('model');
            $record = AuditResolver::queryOf($model)->findOrFail($id);
            // El turno muestra también sus registros de trabajo (excepción a «solo el registro», 2026-11-27)
            $blocks = $this->timeline_block($record instanceof Productivity ? $record->auditTrail() : [$model => [$record->id]]);

            $page = DB::query()->fromSub($blocks, 'blocks')
                ->selectRaw('MIN(id) as first_id, MAX(id) as last_id, MAX(is_click) as is_click, SUM(is_plus) as plus, SUM(is_minus) as minus, MAX(created_at) as last_at')
                ->groupBy('auditable_type', 'auditable_id', 'block')
                ->orderByDesc('last_at')->orderByDesc('last_id')
                ->paginate($request->integer('per_page', 20));

            $audits = Audit::with(['user' => ['employee' => ['person']]])
                ->whereIn('id', collect($page->items())->flatMap(fn ($block) => [$block->first_id, $block->last_id]))
                ->get()->keyBy('id');

            $page->through(function ($block) use ($audits) {
                $audit = $audits[$block->last_id];
                if ($block->is_click) {
                    $audit->setRelation('first_click', $audits[$block->first_id]);
                    $audit->setAttribute('clicks', ['plus' => (int) $block->plus, 'minus' => (int) $block->minus]);
                }

                return $audit;
            });

            return $this->successResponse(
                new AuditTimelineCollection($page),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * Eventos del historial, cada uno con su bloque: los clics seguidos de +1 y −1 de un registro de trabajo comparten
     * bloque si los hizo el mismo usuario y no hubo otro evento ni una parada en medio; cualquier otro evento va solo.
     */
    private function timeline_block(array $trail)
    {
        $in_trail = function ($query) use ($trail) {
            foreach ($trail as $type => $ids) {
                $query->orWhere(fn ($part) => $part->where('auditable_type', $type)->whereIn('auditable_id', $ids));
            }
        };
        $is_click = "(auditable_type = ? AND event = 'updated' AND url LIKE ?)";

        $events = AuditResolver::withChanges(Audit::query()->where($in_trail))
            ->selectRaw(
                "id, auditable_type, auditable_id, user_id, created_at, {$is_click} as is_plus, {$is_click} as is_minus",
                [ProductivityDetail::class, '%/workstation/%/plus', ProductivityDetail::class, '%/workstation/%/minus']
            );

        $previous = 'OVER (PARTITION BY auditable_type, auditable_id ORDER BY id)';
        $with_previous = DB::query()->fromSub($events, 'events')
            ->selectRaw("events.*, LAG(is_plus + is_minus) {$previous} as previous_click, LAG(user_id) {$previous} as previous_user, LAG(created_at) {$previous} as previous_at");

        $with_start = DB::query()->fromSub($with_previous, 'events')
            ->selectRaw(
                "events.*, is_plus + is_minus as is_click, CASE WHEN is_plus + is_minus = 1 AND previous_click = 1 AND user_id <=> previous_user AND NOT EXISTS (
                    SELECT 1 FROM productivity_detail_models AS stops
                    WHERE stops.productivity_detail_id = events.auditable_id AND stops.model_type = ?
                    AND stops.created_at BETWEEN events.previous_at AND events.created_at
                ) THEN 0 ELSE 1 END as starts_block",
                [Stop::class]
            );

        return DB::query()->fromSub($with_start, 'events')
            ->selectRaw("events.*, SUM(starts_block) {$previous} as block");
    }
}
