<?php

namespace App\Http\Controllers;

use App\Http\Requests\Audit\AuditAllRequest;
use App\Http\Requests\Audit\AuditFindRequest;
use App\Http\Resources\Audit\AuditCollection;
use OwenIt\Auditing\Models\Audit;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Audit",
 *     description="Endpoints para gestionar auditorias"
 * )
 *
 * @OA\Schema(
 *     schema="AuditEvent",
 *     type="string",
 *     description="Tipo de cambio registrado.",
 *     enum={"created", "updated", "deleted", "restored", "attach", "detach", "sync"},
 *     example="updated"
 * )
 *
 * @OA\Schema(
 *     schema="AuditValues",
 *     type="object",
 *     description="Valores de los campos auditados antes (`old_values`) o después (`new_values`) del cambio. Las claves dependen del modelo; en attach/detach/sync traen la relación afectada.",
 *     additionalProperties=true,
 *     example={"name": "CALIDAD", "description": "ÁREA DE CALIDAD"}
 * )
 *
 * @OA\Schema(
 *     schema="AuditListItem",
 *     type="object",
 *     description="Registro como lo entrega `AuditCollection` (all). No incluye `user_id`, `auditable_id` ni `user_agent`.",
 *     @OA\Property(property="id", type="integer", example=1842),
 *     @OA\Property(property="user_type", type="string", nullable=true, example="App\Models\User"),
 *     @OA\Property(property="event", ref="#/components/schemas/AuditEvent"),
 *     @OA\Property(property="auditable_type", type="string", description="Clase del modelo modificado.", example="App\Models\Area"),
 *     @OA\Property(property="auditable_name", type="string", nullable=true, description="Nombre en español del modelo; `null` si no está en `model_types`.", example="Área"),
 *     @OA\Property(property="url", type="string", example="http://kanri-backend.test/api/organizational_structure/areas/update/12"),
 *     @OA\Property(property="ip_address", type="string", example="192.168.1.25"),
 *     @OA\Property(property="tags", type="string", nullable=true, description="Etiqueta de la relación afectada en attach/detach/sync.", example=null),
 *     @OA\Property(property="audit_tag", type="string", nullable=true, description="Nombre en español de `tags`; `null` si no tiene traducción.", example=null),
 *     @OA\Property(property="old_values", ref="#/components/schemas/AuditValues"),
 *     @OA\Property(property="new_values", allOf={@OA\Schema(ref="#/components/schemas/AuditValues")}, example={"name": "CONTROL DE CALIDAD", "description": "ÁREA ENCARGADA DE INSPECCIONAR LAS PRENDAS TERMINADAS"}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 10:02:45"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 10:02:45"),
 *     @OA\Property(property="user", nullable=true, description="Usuario que hizo el cambio, con empleado, persona, cargo y área. `null` si lo hizo el sistema (seeders, comandos).", allOf={@OA\Schema(ref="#/components/schemas/User")}),
 *     @OA\Property(property="auditable", type="object", nullable=true, additionalProperties=true, description="Registro modificado tal como está hoy (serializado por Eloquent). `null` si ya no existe.", example={"id": 12, "item_id": 3, "name": "CONTROL DE CALIDAD", "description": "ÁREA ENCARGADA DE INSPECCIONAR LAS PRENDAS TERMINADAS", "settings": {}, "created_at": "2026-05-14T09:32:10.000000Z", "updated_at": "2026-05-14T10:02:45.000000Z", "deleted_at": null})
 * )
 *
 * @OA\Schema(
 *     schema="Audit",
 *     type="object",
 *     description="Registro como lo entrega `find`: el modelo tal cual, con fechas ISO. No incluye `auditable_name` ni `audit_tag`.",
 *     @OA\Property(property="id", type="integer", example=1842),
 *     @OA\Property(property="user_type", type="string", nullable=true, example="App\Models\User"),
 *     @OA\Property(property="user_id", type="integer", nullable=true, example=15),
 *     @OA\Property(property="event", ref="#/components/schemas/AuditEvent"),
 *     @OA\Property(property="auditable_type", type="string", example="App\Models\Area"),
 *     @OA\Property(property="auditable_id", type="integer", example=12),
 *     @OA\Property(property="old_values", ref="#/components/schemas/AuditValues"),
 *     @OA\Property(property="new_values", allOf={@OA\Schema(ref="#/components/schemas/AuditValues")}, example={"name": "CONTROL DE CALIDAD", "description": "ÁREA ENCARGADA DE INSPECCIONAR LAS PRENDAS TERMINADAS"}),
 *     @OA\Property(property="url", type="string", example="http://kanri-backend.test/api/organizational_structure/areas/update/12"),
 *     @OA\Property(property="ip_address", type="string", example="192.168.1.25"),
 *     @OA\Property(property="user_agent", type="string", nullable=true, example="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36"),
 *     @OA\Property(property="tags", type="string", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T15:02:45.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T15:02:45.000000Z"),
 *     @OA\Property(property="user", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/User")}),
 *     @OA\Property(property="auditable", type="object", nullable=true, additionalProperties=true, example={"id": 12, "item_id": 3, "name": "CONTROL DE CALIDAD", "description": "ÁREA ENCARGADA DE INSPECCIONAR LAS PRENDAS TERMINADAS", "settings": {}, "created_at": "2026-05-14T09:32:10.000000Z", "updated_at": "2026-05-14T10:02:45.000000Z", "deleted_at": null})
 * )
 *
 * @OA\Schema(
 *     schema="AuditListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/AuditListItem")),
 *         @OA\Property(
 *             property="model_types",
 *             type="object",
 *             description="Catálogo fijo: nombre corto de la clase → nombre en español. Sirve para armar el filtro `auditable_name`.",
 *             additionalProperties=@OA\AdditionalProperties(type="string"),
 *             example={"Area": "Área", "Position": "Cargo", "Trademark": "Marca", "User": "Usuario"}
 *         ),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="AuditResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="audit", ref="#/components/schemas/Audit")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 */
class AuditController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/audits/all",
     *     tags={"Audit"},
     *     summary="Listar registros de auditoría",
     *     description="Todos los filtros son opcionales y se combinan. No hay búsqueda por texto ni registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=false,
     *         description="Solo los cambios hechos por este usuario.",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="event",
     *         in="query",
     *         required=false,
     *         description="Tipo de cambio.",
     *         @OA\Schema(ref="#/components/schemas/AuditEvent")
     *     ),
     *     @OA\Parameter(
     *         name="auditable_name",
     *         in="query",
     *         required=false,
     *         description="Clase completa del modelo modificado (se compara con `auditable_type`, no con el nombre en español). No se valida.",
     *         @OA\Schema(type="string", example="App\Models\Area")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         description="Desde este día (00:00:00). Si no se envía `end_date`, el rango llega hasta hoy.",
     *         @OA\Schema(type="string", format="date", example="2026-05-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         description="Hasta este día (23:59:59). Envíalo junto con `start_date`: si va solo, el rango empieza hoy y una fecha pasada no devuelve nada.",
     *         @OA\Schema(type="string", format="date", example="2026-05-31")
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"created_at", "event"}, example="created_at")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de auditoría cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AuditListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "event": "Tipo de evento.", "start_date": "Fecha inicio de rango", "end_date": "Fecha fin de rango"},
     *                 "errors": {"event": {"Valor inválido. Permitidos: created, updated, deleted, restored, attach, detach, sync."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(AuditAllRequest $request)
    {
        try {
            $audits = Audit::with(['user' => ['employee' => ['person', 'position' => ['area']]], 'auditable'])
                ->when($request->filled('user_id'), function ($query) use ($request) {
                    return $query->where('user_id', $request->integer('user_id'));
                })
                ->when($request->filled('event'), function ($query) use ($request) {
                    return $query->where('event', $request->input('event'));
                })
                ->when($request->filled('auditable_name'), function ($query) use ($request) {
                    return $query->where('auditable_type', $request->input('auditable_name'));
                })
                ->when($request->filled('start_date') || $request->filled('end_date'), function ($query) use ($request) {
                    return $query->whereBetween('created_at', [
                        Carbon::parse($request->input('start_date'))->startOfDay(),
                        Carbon::parse($request->input('end_date'))->endOfDay()
                    ]);
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $audits = $audits->paginate($request->integer('per_page', $audits->count()));

            return $this->successResponse(
                new AuditCollection($audits),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/audits/find/{id}",
     *     tags={"Audit"},
     *     summary="Obtener un registro de auditoría",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Registro de auditoría cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/AuditResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(AuditFindRequest $request, $id)
    {
        try {
            $audit = Audit::with(['user' => ['employee' => ['person', 'position' => ['area']]], 'auditable'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'audit' => $audit
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
