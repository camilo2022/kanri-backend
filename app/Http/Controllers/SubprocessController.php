<?php

namespace App\Http\Controllers;

use App\Http\Requests\Workflow\Subprocess\SubprocessAllRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessDeleteRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessFindRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessQrRequest;
use App\Services\QrCodeService;
use App\Http\Requests\Workflow\Subprocess\SubprocessRestoreRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessSettingRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessStoreRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessUpdateRequest;
use App\Http\Resources\Workflow\Subprocess\SubprocessCollection;
use App\Http\Resources\Workflow\Subprocess\SubprocessResource;
use App\Models\Subprocess;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="SubprocessSettings",
 *     type="object",
 *     description="Configuración del subproceso. `store`/`update` escriben las banderas y `setting` escribe `schema`; `qr` agrega `uuid`. `update` conserva el `schema` ya guardado. Las opciones de medición (`productivity_measurement`, `time_multiplier`, `units_entry`, `schedule_units`) son opcionales: sin elegir, se hereda la del proceso o la de la operación.",
 *     @OA\Property(property="operations", type="boolean", description="El subproceso tiene operaciones.", example=true),
 *     @OA\Property(property="operation_flow", type="string", nullable=true, enum={"sequence", "parallel"}, description="Cómo se relacionan sus operaciones: `sequence` las encadena con `after_operations`; `parallel` usa `before_operations` («depende de»).", example="sequence"),
 *     @OA\Property(property="in_technical_sheet", type="boolean", description="El subproceso aparece en la ficha técnica.", example=true),
 *     @OA\Property(property="in_production_order", type="boolean", description="El subproceso aparece en la orden de producción.", example=true),
 *     @OA\Property(property="color", type="string", nullable=true, description="Color #RRGGBB con el que se reconoce en tablas y botones. No se repite entre procesos, subprocesos ni operaciones.", example="#2CA02C"),
 *     @OA\Property(property="productivity_measurement", type="string", nullable=true, enum={"lots", "units"}, description="Cómo se mide la productividad.", example="lots"),
 *     @OA\Property(property="time_multiplier", type="string", nullable=true, enum={"none", "buttons", "manual"}, description="Por qué se multiplica el tiempo de la regla: nada, botones de la ficha o una cantidad que se digita al programar.", example="none"),
 *     @OA\Property(property="time_multiplier_label", type="string", nullable=true, maxLength=40, description="Nombre de la cantidad que se digita. Obligatorio si `time_multiplier` es `manual`.", example="NUMERO DE BOLSILLOS"),
 *     @OA\Property(property="units_entry", type="string", nullable=true, enum={"plus", "finish"}, description="Medición por unidades: con un +1 por prenda (`plus`) o digitando el total al finalizar (`finish`).", example="plus"),
 *     @OA\Property(property="schedule_units", type="string", nullable=true, enum={"auto", "typed"}, description="Unidades al programar: reparto automático del lote (`auto`) o las digita quien programa por tandas (`typed`).", example="auto"),
 *     @OA\Property(property="only_after_previous", type="boolean", nullable=true, description="Solo se trabaja si el lote hizo alguno de los subprocesos anteriores de la cadena.", example=false),
 *     @OA\Property(property="next_conditions", type="object", nullable=true, additionalProperties=true, description="Condición de cada subproceso siguiente, con el id del siguiente como clave. Cada valor trae `wash_tone_ids` (ids de tonos de lavado), `except` (todos los tonos menos esos) y `returns` (el lote regresa a ese paso).", example={"1301": {"wash_tone_ids": {101, 102}, "except": false, "returns": false}}),
 *     @OA\Property(property="uuid", type="string", nullable=true, description="Identificador del QR del puesto de trabajo. Lo crea la ruta `qr` la primera vez.", example="9b2f6c1e-4a7d-4c3b-8e5f-2d1a0b9c8e7f"),
 *     @OA\Property(property="schema", ref="#/components/schemas/WorkflowFormSchema")
 * )
 *
 * @OA\Schema(
 *     schema="Subprocess",
 *     type="object",
 *     description="Subproceso: etapa dentro de un proceso que agrupa operaciones. Fechas `Y-m-d H:i:s`.",
 *     @OA\Property(property="id", type="integer", example=342),
 *     @OA\Property(property="item_id", type="integer", example=25),
 *     @OA\Property(property="name", type="string", example="LAVADO 1"),
 *     @OA\Property(property="description", type="string", example="PRIMER LAVADO DE LA PRENDA"),
 *     @OA\Property(property="settings", nullable=true, description="`{}` en subprocesos sin configurar.", allOf={@OA\Schema(ref="#/components/schemas/SubprocessSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="has_audits", type="boolean", description="Solo en `all`: el registro tiene historial de auditoría.", example=false),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         description="Solo en `all`; en las demás rutas depende de si se cargó.",
 *         @OA\Property(property="id", type="integer", example=25),
 *         @OA\Property(property="name", type="string", example="Subprocesos"),
 *         @OA\Property(property="description", type="string", example="Listado de subprocesos."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(property="process", type="array", description="Proceso al que pertenece (con `pivot`).", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="before_subprocesses", type="array", description="Subprocesos anteriores en la cadena del proceso.", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="after_subprocesses", type="array", description="Subprocesos siguientes en la cadena del proceso.", @OA\Items(ref="#/components/schemas/Subitem"))
 * )
 *
 * @OA\Schema(
 *     schema="SubprocessResponse",
 *     type="object",
 *     description="Respuesta de `find`, `store`, `update`, `delete`, `restore` y `setting`.",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="subprocess", ref="#/components/schemas/Subprocess"),
 *         @OA\Property(property="model_types", type="object", additionalProperties=true, description="Catálogo de tipos de dato (`ModelTypes::getModelTypes()`) para armar campos `selectdinamic` del formulario: etiqueta, modelo y URL de cada catálogo.", example={"trademarks": {"label": "MARCAS", "model": "App\Models\Trademark", "url": "/trademarks/all", "value": "id", "option": "name"}})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubprocessListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="subprocesses", type="array", @OA\Items(ref="#/components/schemas/Subprocess")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubprocessRequest",
 *     type="object",
 *     required={"process_id", "name", "description", "settings"},
 *     @OA\Property(property="process_id", type="integer", description="Proceso al que pertenece el subproceso. En `update` es obligatorio pero no cambia de proceso.", example=211),
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los subprocesos.", example="LAVADO 1"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="PRIMER LAVADO DE LA PRENDA"),
 *     @OA\Property(property="after_subprocesses", type="array", description="Subprocesos siguientes en la cadena: solo si el proceso tiene `settings.subprocess_flow = sequence`, del mismo proceso y sin formar ciclos. En `update`, si no se envía, se conservan los guardados.", @OA\Items(type="integer", example=1301)),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         required={"operations", "in_technical_sheet", "in_production_order"},
 *         description="Se guarda tal cual, salvo el `schema`: en `update` se conserva el que ya tenía el subproceso. Cada clave de `next_conditions` debe estar en `after_subprocesses` y tener tonos o ser un regreso.",
 *         @OA\Property(property="operations", type="boolean", example=true),
 *         @OA\Property(property="operation_flow", type="string", nullable=true, enum={"sequence", "parallel"}, description="Obligatorio si `operations` es verdadero.", example="sequence"),
 *         @OA\Property(property="in_technical_sheet", type="boolean", example=true),
 *         @OA\Property(property="in_production_order", type="boolean", example=true),
 *         @OA\Property(property="color", type="string", nullable=true, description="Formato #RRGGBB. No puede repetirse entre procesos, subprocesos ni operaciones.", example="#2CA02C"),
 *         @OA\Property(property="productivity_measurement", type="string", nullable=true, enum={"lots", "units"}, example="lots"),
 *         @OA\Property(property="time_multiplier", type="string", nullable=true, enum={"none", "buttons", "manual"}, example="none"),
 *         @OA\Property(property="time_multiplier_label", type="string", nullable=true, maxLength=40, description="Obligatorio si `time_multiplier` es `manual`.", example="NUMERO DE BOLSILLOS"),
 *         @OA\Property(property="units_entry", type="string", nullable=true, enum={"plus", "finish"}, example="plus"),
 *         @OA\Property(property="schedule_units", type="string", nullable=true, enum={"auto", "typed"}, example="auto"),
 *         @OA\Property(property="only_after_previous", type="boolean", nullable=true, example=false),
 *         @OA\Property(property="next_conditions", type="object", nullable=true, additionalProperties=true, description="Condición por subproceso siguiente (clave = id): `wash_tone_ids` (ids de tonos de lavado), `except` y `returns`.", example={"1301": {"wash_tone_ids": {101, 102}, "except": false, "returns": false}})
 *     )
 * )
 */
class SubprocessController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/workflow/processes/subprocesses/all",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Listar los subprocesos",
     *     description="Cada subproceso trae su ítem, su proceso y sus subprocesos anteriores y siguientes. Con el identificador de un proceso en la ruta (`/all/{process_id}`) solo se listan los subprocesos de ese proceso; un proceso inexistente devuelve una lista vacía.",
     *     security={{"sanctum":{}}},
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
     *         description="Lista de subprocesos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por página.", "column": "Columna a ordenar."},
     *                 "errors": {"column": {"El valor seleccionado no es válido."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     *
     * @OA\Get(
     *     path="/workflow/processes/subprocesses/all/{process_id}",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Listar los subprocesos de un proceso",
     *     description="Igual que `/all`, pero solo con los subprocesos del proceso indicado.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="process_id", in="path", required=true, description="Identificador del proceso.", @OA\Schema(type="integer", example=211)),
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
     *         description="Lista de subprocesos del proceso cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessListResponse")
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
    public function all(SubprocessAllRequest $request, $process_id = null)
    {
        try {
            $subprocesses = Subprocess::with(['item', 'process', 'before_subprocesses', 'after_subprocesses'])
                ->when($process_id, fn ($query) => $query->whereHas('process', fn ($r) => $r->where('id', $process_id)))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $subprocesses = $subprocesses->paginate($request->integer('per_page', $subprocesses->count()));

            return $this->successResponse(
                new SubprocessCollection($subprocesses),
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
     *     path="/workflow/processes/subprocesses/find/{id}",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Obtener un subproceso",
     *     description="Trae el subproceso con su proceso y sus subprocesos anteriores y siguientes. Un subproceso desactivado no se encuentra.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subproceso cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SubprocessFindRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::with(['process', 'before_subprocesses', 'after_subprocesses'])
                ->findOrFail($id);

            return $this->successResponse(
                new SubprocessResource($subprocess),
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
     * @OA\Post(
     *     path="/workflow/processes/subprocesses/store",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Crear un subproceso",
     *     description="Crea el subproceso dentro del proceso `process_id` y guarda sus subprocesos siguientes (`after_subprocesses`), que solo se aceptan si el proceso tiene `settings.subprocess_flow = sequence`. El `schema` del formulario no se envía aquí: se configura con `setting`.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subproceso creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"process_id": "Identificador del proceso", "name": "Nombre", "after_subprocesses": "Subprocesos siguientes"},
     *                 "errors": {"name": {"Debe de ingresar mayúsculas."}, "after_subprocesses": {"LAVANDERIA no tiene sus subprocesos en secuencia: no se pueden definir siguientes."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SubprocessStoreRequest $request)
    {
        try {
            $subprocess = new Subprocess();
            $subprocess->name = $request->input('name');
            $subprocess->description = $request->input('description');
            $subprocess->settings = $request->input('settings');

            $subprocess->save();

            // Subproceso nuevo: sync no toca cadenas de nadie (ver Subprocess::process)
            $subprocess->auditSync('process', $request->integer('process_id'));
            $subprocess->auditAttach('after_subprocesses', $request->array('after_subprocesses'));

            return $this->successResponse(
                new SubprocessResource($subprocess),
                $this->getMessage('Success'),
                201
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
     * @OA\Put(
     *     path="/workflow/processes/subprocesses/update/{id}",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Editar un subproceso",
     *     description="Reemplaza nombre, descripción y `settings`. El `schema` guardado con `setting` se conserva aunque no se envíe. `after_subprocesses` solo se sincroniza si se envía. `process_id` es obligatorio pero no cambia el proceso del subproceso. Las cadenas se validan: no se puede ser su propio siguiente, ni formar ciclos (salvo un regreso a un paso anterior con `returns`). Hoy responde 201 (no 200), tal como está escrito el controlador.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SubprocessRequest",
     *             example={"process_id": 211, "name": "LAVADO 1", "description": "PRIMER LAVADO DE LA PRENDA", "after_subprocesses": {1301}, "settings": {"operations": true, "operation_flow": "sequence", "in_technical_sheet": true, "in_production_order": true, "productivity_measurement": "lots"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subproceso editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o subproceso no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del subproceso", "name": "Nombre"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SubprocessUpdateRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::findOrFail($id);
            $subprocess->name = $request->input('name');
            $subprocess->description = $request->input('description');
            // La edición no maneja el schema (tablas dinámicas y estáticas): se conserva el guardado desde Configuración
            $settings = $request->input('settings');
            $stored = json_decode(json_encode($subprocess->settings), true) ?? [];
            if (array_key_exists('schema', $stored) && is_array($settings)) {
                $settings['schema'] = $stored['schema'];
            }
            $subprocess->settings = $settings;

            $subprocess->save();

            // Solo si se envían: un cliente que no los maneja no borra las cadenas guardadas
            if ($request->has('after_subprocesses')) {
                $subprocess->auditSync('after_subprocesses', $request->array('after_subprocesses'));
            }

            return $this->successResponse(
                new SubprocessResource($subprocess),
                $this->getMessage('Success'),
                201
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
     * @OA\Delete(
     *     path="/workflow/processes/subprocesses/delete/{id}",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Desactivar un subproceso",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse. Un subproceso ya desactivado responde 422.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subproceso desactivado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SubprocessDeleteRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::findOrFail($id);
            $subprocess->delete();

            return $this->successResponse(
                new SubprocessResource($subprocess),
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
     * @OA\Patch(
     *     path="/workflow/processes/subprocesses/restore/{id}",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Restaurar un subproceso",
     *     description="Reactiva un subproceso desactivado. Un subproceso que no está desactivado responde 422.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subproceso restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SubprocessRestoreRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::withTrashed()->findOrFail($id);
            $subprocess->restore();

            return $this->successResponse(
                new SubprocessResource($subprocess),
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
     * @OA\Put(
     *     path="/workflow/processes/subprocesses/setting/{id}",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Configurar el formulario de un subproceso",
     *     description="Guarda el esquema del formulario (`settings.schema`, con sus partes `static` y `dinamic`). Reemplaza `settings` completo, así que se pierden las banderas (`operations`, `in_technical_sheet`, etc.) si no se envían. Requiere el permiso `workflow.processes.subprocesses.settings`.",
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
     *         @OA\JsonContent(ref="#/components/schemas/SubprocessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Esquema inválido: encabezado sin etiqueta en mayúsculas, celdas que no son th/td, colspan/rowspan no numéricos, campo dinámico con `field` fuera de snake_case, etc.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function setting(SubprocessSettingRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::findOrFail($id);
            $subprocess->settings = $request->input('settings');

            $subprocess->save();

            return $this->successResponse(
                new SubprocessResource($subprocess),
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
     *     path="/workflow/processes/subprocesses/qr/{id}",
     *     tags={"Workflow › Subprocesses"},
     *     summary="Generar el QR del puesto de trabajo (subproceso)",
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
     *                     @OA\Property(property="type", type="string", example="subprocess"),
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Tendido"),
     *                     @OA\Property(property="path", type="string", example="Corte › Tendido")
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
    public function qr(SubprocessQrRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::with(['process'])->findOrFail($id);

            $url = rtrim(config('app.frontend_url'), '/').'/productivity?qr='.$subprocess->settingsUuid();

            return $this->successResponse(
                [
                    'qr' => app(QrCodeService::class)->generate($url),
                    'url' => $url,
                    'workstation' => [
                        'type' => 'subprocess',
                        'id' => $subprocess->id,
                        'name' => $subprocess->name,
                        'path' => collect([$subprocess->process->first()?->name, $subprocess->name])->filter()->implode(' › '),
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
