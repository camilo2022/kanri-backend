<?php

namespace App\Http\Controllers;

use App\Http\Requests\Workflow\Operation\OperationAllRequest;
use App\Http\Requests\Workflow\Operation\OperationDeleteRequest;
use App\Http\Requests\Workflow\Operation\OperationFindRequest;
use App\Http\Requests\Workflow\Operation\OperationQrRequest;
use App\Services\QrCodeService;
use App\Http\Requests\Workflow\Operation\OperationRestoreRequest;
use App\Http\Requests\Workflow\Operation\OperationSettingRequest;
use App\Http\Requests\Workflow\Operation\OperationStoreRequest;
use App\Http\Requests\Workflow\Operation\OperationUpdateRequest;
use App\Http\Resources\Workflow\Operation\OperationCollection;
use App\Http\Resources\Workflow\Operation\OperationResource;
use App\Models\Operation;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="WorkflowFormSchema",
 *     type="object",
 *     description="Formulario configurable de un proceso, subproceso u operación. `static` es una tabla fija (encabezado + filas de celdas `th`/`td`); `dinamic` es una lista de campos que se repiten. Cada campo `td` define `type` (number, date, select, selectdinamic, textarea, boolean…), `field`, `rules` de Laravel y, si aplica, `model` u `options`.",
 *     @OA\Property(
 *         property="static",
 *         type="object",
 *         @OA\Property(property="header", type="object", @OA\Property(property="label", type="string", description="En MAYÚSCULAS.", example="PEGAR PRETINA"), @OA\Property(property="colspan", type="integer", example=4), @OA\Property(property="rowspan", type="integer", example=1)),
 *         @OA\Property(property="body", type="array", description="Filas; cada fila es una lista de celdas.", @OA\Items(type="array", @OA\Items(type="object", additionalProperties=true)))
 *     ),
 *     @OA\Property(property="dinamic", type="object", additionalProperties=true),
 *     example={"static": {"header": {"label": "PEGAR PRETINA", "colspan": 4, "rowspan": 1}, "body": {{{"id": 1, "cell": "th", "label": "RESPONSABLE", "colspan": 1, "rowspan": 1}, {"id": 2, "cell": "td", "type": "selectdinamic", "field": "employee_id", "model": "App\Models\Employee", "rules": {"exists:employees,id,deleted_at,NULL", "required"}, "colspan": 1, "rowspan": 1}, {"id": 3, "cell": "th", "label": "UNIDADES", "colspan": 1, "rowspan": 1}, {"id": 4, "cell": "td", "type": "number", "field": "units", "rules": {"numeric", "required", "min:1"}, "colspan": 1, "rowspan": 1}}}}}
 * )
 *
 * @OA\Schema(
 *     schema="OperationSettings",
 *     type="object",
 *     description="Configuración de la operación. `store`/`update` escriben las banderas y `setting` escribe `schema`; cada uno reemplaza el objeto completo (ver pendientes).",
 *     @OA\Property(property="in_technical_sheet", type="boolean", description="La operación aplica a la ficha técnica.", example=true),
 *     @OA\Property(property="in_production_order", type="boolean", description="La operación aplica a la orden de producción.", example=false),
 *     @OA\Property(property="schema", ref="#/components/schemas/WorkflowFormSchema")
 * )
 *
 * @OA\Schema(
 *     schema="Operation",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=360),
 *     @OA\Property(property="item_id", type="integer", example=26),
 *     @OA\Property(property="name", type="string", example="PEGAR PRETINA"),
 *     @OA\Property(property="description", type="string", example="PEGAR LA PRETINA AL CUERPO DEL JEAN"),
 *     @OA\Property(property="settings", nullable=true, description="`{}` en operaciones sin configurar.", allOf={@OA\Schema(ref="#/components/schemas/OperationSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 17:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 17:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=26),
 *         @OA\Property(property="name", type="string", example="Operaciones"),
 *         @OA\Property(property="description", type="string", example="Listado de operaciones."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(
 *         property="subprocess",
 *         type="array",
 *         description="Subproceso al que pertenece (con `pivot`).",
 *         @OA\Items(ref="#/components/schemas/Subitem")
 *     ),
 *     @OA\Property(property="before_operations", type="array", description="Operaciones anteriores del mismo subproceso.", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="after_operations", type="array", description="Operaciones siguientes del mismo subproceso.", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="after_subprocesses", type="array", description="Subprocesos siguientes en la cadena del proceso: la operación sale de su subproceso y abre una línea (RESINA → LAVADO RESINA).", @OA\Items(ref="#/components/schemas/Subitem"))
 * )
 *
 * @OA\Schema(
 *     schema="OperationResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="operation", ref="#/components/schemas/Operation"),
 *         @OA\Property(property="model_types", type="object", additionalProperties=true, description="Catálogo de tipos de dato (`ModelTypes::getModelTypes()`) para armar campos `selectdinamic` del formulario: etiqueta, modelo y URL de cada catálogo.", example={"trademarks": {"label": "MARCAS", "model": "App\Models\Trademark", "url": "/trademarks/all", "value": "id", "option": "name"}})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="OperationListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="operations", type="array", @OA\Items(ref="#/components/schemas/Operation")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="OperationRequest",
 *     type="object",
 *     required={"subprocess_id", "name", "description", "settings"},
 *     @OA\Property(property="subprocess_id", type="integer", description="Subproceso al que pertenece la operación.", example=342),
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre las operaciones.", example="PEGAR PRETINA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="PEGAR LA PRETINA AL CUERPO DEL JEAN"),
 *     @OA\Property(property="after_operations", type="array", description="Operaciones siguientes: solo si el subproceso tiene `settings.operation_flow = sequence`, del mismo subproceso, distintas de ella misma y sin formar ciclos. En `update`, si no se envía, se conservan las guardadas.", @OA\Items(type="integer", example=361)),
 *     @OA\Property(property="before_operations", type="array", description="«Depende de»: solo si el subproceso tiene `settings.operation_flow = parallel`. Operaciones del mismo subproceso que deben estar hechas antes (RELOJERA depende de PARCHE), distintas de ella misma y sin formar ciclos. Es obligatoria aunque la ficha técnica no la traiga. En `update`, si no se envía, se conservan las guardadas.", @OA\Items(type="integer", example=343)),
 *     @OA\Property(property="after_subprocesses", type="array", description="Subprocesos siguientes en la cadena del proceso: solo si el proceso tiene `settings.subprocess_flow = sequence`, del mismo proceso y distintos del subproceso de la operación. La operación deja de esperar lo anterior de su subproceso y abre una línea. En `update`, si no se envía, se conservan los guardados.", @OA\Items(type="integer", example=1301)),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         required={"in_technical_sheet", "in_production_order"},
 *         description="Se guarda tal cual: reemplaza todo `settings`, incluido el `schema` configurado con `setting`.",
 *         @OA\Property(property="in_technical_sheet", type="boolean", example=true),
 *         @OA\Property(property="in_production_order", type="boolean", example=false)
 *     )
 * )
 */
class OperationController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/workflow/processes/subprocesses/operations/all/{subprocess_id}",
     *     tags={"Workflow › Operations"},
     *     summary="Listar las operaciones de un subproceso",
     *     description="Cada operación trae su subproceso. Un subproceso inexistente devuelve una lista vacía.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="subprocess_id",
     *         in="path",
     *         required=true,
     *         description="Identificador del subproceso.",
     *         @OA\Schema(type="integer", example=342)
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "name", "description", "created_at", "updated_at", "deleted_at"}, example="name")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de operaciones cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/OperationListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(OperationAllRequest $request, $subprocess_id)
    {
        try {
            $operations = Operation::with(['item', 'subprocess', 'before_operations', 'after_operations', 'after_subprocesses'])
                ->whereHas('subprocess', fn ($r) => $r->when($request->boolean('with_trashed'), fn ($query) => $query->withTrashed())->where('id', $subprocess_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $operations = $operations->paginate($request->integer('per_page', $operations->count()));

            return $this->successResponse(
                new OperationCollection($operations),
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
     * @OA\Get(
     *     path="/workflow/processes/subprocesses/operations/find/{id}",
     *     tags={"Workflow › Operations"},
     *     summary="Obtener una operación",
     *     description="Incluye el catálogo `model_types` para configurar el formulario.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Operación cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/OperationResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(OperationFindRequest $request, $id)
    {
        try {
            $operation = Operation::with(['subprocess', 'before_operations', 'after_operations', 'after_subprocesses'])
                ->findOrFail($id);

            return $this->successResponse(
                new OperationResource($operation),
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
     * @OA\Post(
     *     path="/workflow/processes/subprocesses/operations/store",
     *     tags={"Workflow › Operations"},
     *     summary="Crear una operación",
     *     description="La vincula al subproceso indicado.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/OperationRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Operación creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/OperationResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"subprocess_id": "Identificador del subproceso", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.in_technical_sheet": "Aplica a Ficha Técnica", "settings.in_production_order": "Aplica a Orden de Producción"},
     *                 "errors": {"settings.in_production_order": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(OperationStoreRequest $request)
    {
        try {
            $operation = new Operation;
            $operation->name = $request->input('name');
            $operation->description = $request->input('description');
            $operation->settings = $request->input('settings');

            $operation->save();

            // Operación nueva: sync no toca cadenas de nadie (ver Operation::subprocess)
            $operation->auditSync('subprocess', $request->integer('subprocess_id'));
            $operation->auditAttach('after_operations', $request->array('after_operations'));
            $operation->auditAttach('after_subprocesses', $request->array('after_subprocesses'));

            // «Depende de» se guarda en la operación de la que depende (su siguiente): por este lado la tabla compartida no
            // toca el subproceso ni las reglas de la operación
            foreach ($request->array('before_operations') as $id) {
                Operation::find($id)?->auditAttach('after_operations', [$operation->id]);
            }

            return $this->successResponse(
                new OperationResource($operation),
                $this->getMessage('Success'),
                201
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
     * @OA\Put(
     *     path="/workflow/processes/subprocesses/operations/update/{id}",
     *     tags={"Workflow › Operations"},
     *     summary="Editar una operación",
     *     description="Cambia nombre, descripción y `settings` (reemplaza el objeto completo, incluido el `schema`). No cambia el subproceso.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/OperationRequest",
     *             example={"subprocess_id": 342, "name": "PEGAR BOLSILLOS", "description": "PEGAR LOS BOLSILLOS TRASEROS", "settings": {"in_technical_sheet": true, "in_production_order": true}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/OperationResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos u operación no registrada.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(OperationUpdateRequest $request, $id)
    {
        try {
            $operation = Operation::findOrFail($id);
            $operation->name = $request->input('name');
            $operation->description = $request->input('description');
            // La edición no maneja el schema (tablas dinámicas y estáticas): se conserva el guardado desde Configuración
            $settings = $request->input('settings');
            $stored = json_decode(json_encode($operation->settings), true) ?? [];
            if (array_key_exists('schema', $stored) && is_array($settings)) {
                $settings['schema'] = $stored['schema'];
            }
            $operation->settings = $settings;

            $operation->save();

            // Solo si se envían: un cliente que no los maneja no borra las cadenas guardadas
            if ($request->has('after_operations')) {
                $operation->auditSync('after_operations', $request->array('after_operations'));
            }

            if ($request->has('after_subprocesses')) {
                $operation->auditSync('after_subprocesses', $request->array('after_subprocesses'));
            }

            // «Depende de»: se agrega o se quita en la operación de la que depende (ver store)
            if ($request->has('before_operations')) {
                $current = $operation->before_operations()->pluck('subitems.id')->map(fn ($id) => (int) $id);
                $wanted = collect($request->array('before_operations'))->map(fn ($id) => (int) $id);

                foreach ($wanted->diff($current) as $id) {
                    Operation::find($id)?->auditAttach('after_operations', [$operation->id]);
                }

                foreach ($current->diff($wanted) as $id) {
                    Operation::find($id)?->auditDetach('after_operations', [$operation->id]);
                }
            }

            return $this->successResponse(
                new OperationResource($operation),
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
     * @OA\Delete(
     *     path="/workflow/processes/subprocesses/operations/delete/{id}",
     *     tags={"Workflow › Operations"},
     *     summary="Desactivar una operación",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Operación desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/OperationResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(OperationDeleteRequest $request, $id)
    {
        try {
            $operation = Operation::findOrFail($id);
            $operation->delete();

            return $this->successResponse(
                new OperationResource($operation),
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
     * @OA\Patch(
     *     path="/workflow/processes/subprocesses/operations/restore/{id}",
     *     tags={"Workflow › Operations"},
     *     summary="Restaurar una operación desactivada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Operación restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/OperationResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(OperationRestoreRequest $request, $id)
    {
        try {
            $operation = Operation::withTrashed()->findOrFail($id);
            $operation->restore();

            return $this->successResponse(
                new OperationResource($operation),
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
     * @OA\Put(
     *     path="/workflow/processes/subprocesses/operations/setting/{id}",
     *     tags={"Workflow › Operations"},
     *     summary="Configurar el formulario de una operación",
     *     description="Guarda el esquema del formulario (`settings.schema`, con sus partes `static` y `dinamic`). Reemplaza `settings` completo, así que se pierden `in_technical_sheet` e `in_production_order` si no se envían (ver pendientes). Requiere el permiso `workflow.processes.subprocesses.operations.settings`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"settings"},
     *             @OA\Property(property="settings", type="object", required={"schema"}, @OA\Property(property="schema", ref="#/components/schemas/WorkflowFormSchema"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Formulario guardado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/OperationResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Esquema inválido: encabezado sin etiqueta en mayúsculas, celdas que no son th/td, colspan/rowspan no numéricos o menores a 1, etc.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function setting(OperationSettingRequest $request, $id)
    {
        try {
            $operation = Operation::findOrFail($id);
            $operation->settings = $request->input('settings');

            $operation->save();

            return $this->successResponse(
                new OperationResource($operation),
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
     * @OA\Get(
     *     path="/workflow/processes/subprocesses/operations/qr/{id}",
     *     tags={"Workflow › Operations"},
     *     summary="Generar el QR del puesto de trabajo (operación)",
     *     description="Devuelve el QR (SVG en base64) que se pega en el puesto de trabajo. Al escanearlo, la tablet abre Productividad (`<FRONTEND_URL>/productivity?qr=<uuid>`) lista para registrar trabajo. El identificador (uuid) se crea la primera vez y se guarda en `settings.uuid`. Solo se permite en el nivel más bajo: un proceso sin subprocesos, un subproceso sin operaciones o una operación, y que esté activo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="QR generado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="qr", type="string", example="data:image/svg+xml;base64,PHN2ZyB4bWxucz0i..."),
     *                 @OA\Property(property="url", type="string", example="https://kanri.example.com/productivity?qr=9b2f6c1e-4a7d-4c3b-8e5f-2d1a0b9c8e7f"),
     *                 @OA\Property(
     *                     property="workstation",
     *                     type="object",
     *                     @OA\Property(property="type", type="string", example="operation"),
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Pegar bolsillo"),
     *                     @OA\Property(property="path", type="string", example="Confección › Delanteros › Pegar bolsillo")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function qr(OperationQrRequest $request, $id)
    {
        try {
            $operation = Operation::with(['subprocess' => ['process']])->findOrFail($id);

            $url = rtrim(config('app.frontend_url'), '/').'/productivity?qr='.$operation->settingsUuid();

            return $this->successResponse(
                [
                    'qr' => app(QrCodeService::class)->generate($url),
                    'url' => $url,
                    'workstation' => [
                        'type' => 'operation',
                        'id' => $operation->id,
                        'name' => $operation->name,
                        'path' => collect([$operation->subprocess->first()?->process->first()?->name, $operation->subprocess->first()?->name, $operation->name])->filter()->implode(' › '),
                    ],
                ],
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

}
