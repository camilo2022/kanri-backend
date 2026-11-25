<?php

namespace App\Http\Controllers;

use App\Http\Requests\Workflow\Process\ProcessAllRequest;
use App\Http\Requests\Workflow\Process\ProcessDeleteRequest;
use App\Http\Requests\Workflow\Process\ProcessFindRequest;
use App\Http\Requests\Workflow\Process\ProcessQrRequest;
use App\Http\Requests\Workflow\Process\ProcessRestoreRequest;
use App\Http\Requests\Workflow\Process\ProcessSettingRequest;
use App\Http\Requests\Workflow\Process\ProcessStoreRequest;
use App\Http\Requests\Workflow\Process\ProcessUpdateRequest;
use App\Http\Resources\Workflow\Process\ProcessCollection;
use App\Http\Resources\Workflow\Process\ProcessResource;
use App\Models\Process;
use App\Services\QrCodeService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="ProcessSettings",
 *     type="object",
 *     description="Configuración del proceso. `store`/`update` escriben las banderas y `setting` escribe `schema`; `qr` agrega `uuid`. `update` conserva el `schema` ya guardado.",
 *     @OA\Property(property="subprocesses", type="boolean", description="El proceso tiene subprocesos.", example=true),
 *     @OA\Property(property="subprocess_flow", type="string", nullable=true, enum={"sequence", "parallel"}, description="Cómo se relacionan sus subprocesos: `sequence` los encadena con `after_subprocesses`; `parallel` los deja independientes.", example="sequence"),
 *     @OA\Property(property="in_technical_sheet", type="boolean", description="El proceso aparece en la ficha técnica.", example=true),
 *     @OA\Property(property="in_production_order", type="boolean", description="El proceso aparece en la orden de producción.", example=true),
 *     @OA\Property(property="color", type="string", nullable=true, description="Color #RRGGBB con el que se reconoce en tablas y botones. No se repite entre procesos, subprocesos ni operaciones.", example="#1F77B4"),
 *     @OA\Property(property="paragraph", type="string", nullable=true, description="Solo un proceso de la orden de producción puede tener el valor `production_place` (lugar de producción).", example="production_place"),
 *     @OA\Property(property="productivity_measurement", type="string", nullable=true, description="Cómo se mide la productividad: `lots` o `units`. Obligatorio si `in_production_order` es verdadero.", example="lots"),
 *     @OA\Property(property="any_workstation", type="boolean", nullable=true, description="Cualquier puesto del proceso trabaja la referencia (los módulos se reparten el lote).", example=false),
 *     @OA\Property(property="reprocesses", type="boolean", nullable=true, description="Permite registrar reprocesos entre sus subprocesos y operaciones.", example=false),
 *     @OA\Property(property="uuid", type="string", nullable=true, description="Identificador del QR del puesto de trabajo. Lo crea la ruta `qr` la primera vez.", example="9b2f6c1e-4a7d-4c3b-8e5f-2d1a0b9c8e7f"),
 *     @OA\Property(property="schema", ref="#/components/schemas/WorkflowFormSchema")
 * )
 *
 * @OA\Schema(
 *     schema="Process",
 *     type="object",
 *     description="Proceso de planta. Las fechas salen como `Y-m-d H:i:s` en `all` y `find`, y en ISO en las respuestas que devuelven el modelo directo (`store`, `update`, `delete`, `restore`, `setting`).",
 *     @OA\Property(property="id", type="integer", example=206),
 *     @OA\Property(property="item_id", type="integer", example=24),
 *     @OA\Property(property="name", type="string", example="CORTE"),
 *     @OA\Property(property="description", type="string", example="CORTE DE LA TELA SEGUN EL TRAZO"),
 *     @OA\Property(property="settings", nullable=true, description="`{}` en procesos sin configurar.", allOf={@OA\Schema(ref="#/components/schemas/ProcessSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="has_audits", type="boolean", description="Solo en `all`: el registro tiene historial de auditoría.", example=false),
 *     @OA\Property(property="model_type", type="string", description="Solo en `all`.", example="App\Models\Process"),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         description="No viene en `store`, `delete`, `restore` ni `setting`.",
 *         @OA\Property(property="id", type="integer", example=24),
 *         @OA\Property(property="name", type="string", example="Procesos"),
 *         @OA\Property(property="description", type="string", example="Listado de procesos."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(property="before_processes", type="array", description="Procesos anteriores. Solo en `all`, `find` y `setting`.", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="after_processes", type="array", description="Procesos siguientes. Solo en `all`, `find` y `setting`.", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="subprocesses", type="array", description="Subprocesos del proceso (con `pivot`). Solo en `all` y `find`; en `all` traen además `after_subprocesses` y `operations`, y con `with_trashed_children` incluyen los desactivados.", @OA\Items(ref="#/components/schemas/Subitem")),
 *     @OA\Property(property="typologies", type="array", description="Solo en `all` con `with_typologies`.", @OA\Items(ref="#/components/schemas/Subitem"))
 * )
 *
 * @OA\Schema(
 *     schema="ProcessResponse",
 *     type="object",
 *     description="Respuesta de `find`.",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="process", ref="#/components/schemas/Process"),
 *         @OA\Property(property="model_types", type="object", additionalProperties=true, description="Catálogo de tipos de dato (`ModelTypes::getModelTypes()`) para armar campos `selectdinamic` del formulario: etiqueta, modelo y URL de cada catálogo.", example={"trademarks": {"label": "MARCAS", "model": "App\Models\Trademark", "url": "/trademarks/all", "value": "id", "option": "name"}})
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProcessSavedResponse",
 *     type="object",
 *     description="Respuesta de `store`, `update`, `delete`, `restore` y `setting`: el proceso tal como queda guardado, sin `model_types`.",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="process", ref="#/components/schemas/Process")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProcessListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="processes", type="array", @OA\Items(ref="#/components/schemas/Process")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProcessRequest",
 *     type="object",
 *     required={"name", "description", "settings"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los procesos.", example="LAVANDERIA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="LAVADO Y TRATAMIENTO DE LAS PRENDAS"),
 *     @OA\Property(property="after_processes", type="array", description="Procesos siguientes (ids de procesos). En `update` se reemplazan por completo: si no se envía, se quitan los guardados.", @OA\Items(type="integer", example=207)),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         required={"subprocesses", "in_technical_sheet", "in_production_order"},
 *         description="Se guarda tal cual, salvo el `schema`: en `update` se conserva el que ya tenía el proceso.",
 *         @OA\Property(property="subprocesses", type="boolean", example=true),
 *         @OA\Property(property="subprocess_flow", type="string", nullable=true, enum={"sequence", "parallel"}, description="Obligatorio si `subprocesses` es verdadero.", example="sequence"),
 *         @OA\Property(property="in_technical_sheet", type="boolean", example=true),
 *         @OA\Property(property="in_production_order", type="boolean", example=true),
 *         @OA\Property(property="color", type="string", nullable=true, description="Formato #RRGGBB. No puede repetirse entre procesos, subprocesos ni operaciones.", example="#1F77B4"),
 *         @OA\Property(property="paragraph", type="string", description="Con `in_production_order` verdadero, `production_place` solo puede tenerlo un proceso.", example="production_place"),
 *         @OA\Property(property="productivity_measurement", type="string", description="Obligatorio si `in_production_order` es verdadero. Valores usados: `lots` o `units`.", example="lots"),
 *         @OA\Property(property="any_workstation", type="boolean", nullable=true, example=false),
 *         @OA\Property(property="reprocesses", type="boolean", nullable=true, example=false)
 *     )
 * )
 */
class ProcessController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/workflow/processes/all",
     *     tags={"Workflow › Processes"},
     *     summary="Listar los procesos",
     *     description="Cada proceso trae su ítem, sus procesos anteriores y siguientes y sus subprocesos con operaciones. Se acepta con cualquiera de los permisos `workflow.processes.all`, `users.workflow.assign`, `employees.store` o `employees.update`.",
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
     *     @OA\Parameter(name="with_trashed_children", in="query", required=false, description="Incluir también los subprocesos y operaciones desactivados dentro de cada proceso.", @OA\Schema(type="boolean", example=false)),
     *     @OA\Parameter(name="in_technical_sheet", in="query", required=false, description="Solo los procesos con `settings.in_technical_sheet` verdadero.", @OA\Schema(type="boolean", example=true)),
     *     @OA\Parameter(name="in_production_order", in="query", required=false, description="Solo los procesos con `settings.in_production_order` verdadero.", @OA\Schema(type="boolean", example=true)),
     *     @OA\Parameter(name="with_typologies", in="query", required=false, description="Incluir las tipologías de cada proceso (`typologies`).", @OA\Schema(type="boolean", example=false)),
     *     @OA\Parameter(name="process_id", in="query", required=false, description="Excluye de la lista al proceso con este identificador.", @OA\Schema(type="integer", example=206)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de procesos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por página.", "process_id": "Identificador del proceso."},
     *                 "errors": {"process_id": {"No hay ningún registro."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(ProcessAllRequest $request)
    {
        try {
            $processes = Process::with(['item', 'before_processes', 'after_processes'])
                ->when($request->boolean('with_trashed_children'), function ($query) {
                    return $query->with(['subprocesses_with_trashed' => ['after_subprocesses', 'operations_with_trashed' => ['after_operations', 'after_subprocesses']]]);
                }, function ($query) {
                    return $query->with(['subprocesses' => ['after_subprocesses', 'operations' => ['after_operations', 'after_subprocesses']]]);
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->boolean('in_technical_sheet'), function ($query) {
                    return $query->where('settings->in_technical_sheet', true);
                })
                ->when($request->boolean('in_production_order'), function ($query) {
                    return $query->where('settings->in_production_order', true);
                })
                ->when($request->boolean('with_typologies'), function ($query) {
                    return $query->with(['typologies']);
                })
                ->when($request->filled('process_id'), function ($query) use ($request) {
                    return $query->where('id', '<>', $request->integer('process_id'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $processes = $processes->paginate($request->integer('per_page', $processes->count()));

            return $this->successResponse(
                new ProcessCollection($processes),
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
     *     path="/workflow/processes/find/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Obtener un proceso",
     *     description="Trae el proceso con su ítem, procesos anteriores y siguientes y sus subprocesos con sus operaciones. Un proceso desactivado no se encuentra.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Proceso cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(ProcessFindRequest $request, $id)
    {
        try {
            $process = Process::with(['item', 'before_processes', 'after_processes', 'subprocesses' => ['operations' => ['after_subprocesses']]])
                ->findOrFail($id);

            return $this->successResponse(
                new ProcessResource($process),
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
     *     path="/workflow/processes/store",
     *     tags={"Workflow › Processes"},
     *     summary="Crear un proceso",
     *     description="Guarda el proceso con sus procesos siguientes (`after_processes`). El `schema` del formulario no se envía aquí: se configura con `setting`. Responde con el modelo recién guardado (sin ítem ni relaciones).",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProcessRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proceso creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessSavedResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings.productivity_measurement": "Medición de la productividad"},
     *                 "errors": {"name": {"Debe de ingresar mayúsculas."}, "settings.productivity_measurement": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(ProcessStoreRequest $request)
    {
        try {
            $process = new Process;
            $process->name = $request->input('name');
            $process->description = $request->input('description');
            $process->settings = $request->input('settings');

            $process->save();

            $process->auditAttach('after_processes', $request->array('after_processes'));

            return $this->successResponse(
                [
                    'process' => $process,
                ],
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
     *     path="/workflow/processes/update/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Editar un proceso",
     *     description="Reemplaza nombre, descripción, `settings` y procesos siguientes. El `schema` guardado con `setting` se conserva aunque no se envíe. `after_processes` se sincroniza siempre: si no se envía, se quitan los siguientes guardados. Si el proceso queda sin `subprocess_flow = sequence`, se borran las cadenas entre sus subprocesos. Hoy responde 201 (no 200), tal como está escrito el controlador.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ProcessRequest",
     *             example={"name": "LAVANDERIA", "description": "LAVADO Y TRATAMIENTO DE LAS PRENDAS", "after_processes": {207}, "settings": {"subprocesses": true, "subprocess_flow": "sequence", "in_technical_sheet": true, "in_production_order": true, "productivity_measurement": "lots"}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proceso editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessSavedResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o proceso no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del proceso", "name": "Nombre"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(ProcessUpdateRequest $request, $id)
    {
        try {
            $process = Process::findOrFail($id);
            $process->name = $request->input('name');
            $process->description = $request->input('description');
            // La edición no maneja el schema (tablas dinámicas y estáticas): se conserva el guardado desde Configuración
            $settings = $request->input('settings');
            $stored = json_decode(json_encode($process->settings), true) ?? [];
            if (array_key_exists('schema', $stored) && is_array($settings)) {
                $settings['schema'] = $stored['schema'];
            }
            $process->settings = $settings;

            $process->save();

            $process->auditSync('after_processes', $request->array('after_processes'));

            // Sin subprocesos en secuencia no hay cadenas entre ellos
            if (($process->settings->subprocess_flow ?? Process::PARALLEL) !== Process::SEQUENCE) {
                $process->clearSubprocessChains();
            }

            return $this->successResponse(
                [
                    'process' => $process,
                ],
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
     * @OA\Delete(
     *     path="/workflow/processes/delete/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Desactivar un proceso",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse. Un proceso ya desactivado responde 422.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Proceso desactivado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessSavedResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(ProcessDeleteRequest $request, $id)
    {
        try {
            $process = Process::findOrFail($id);
            $process->delete();

            return $this->successResponse(
                [
                    'process' => $process,
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

    /**
     * @OA\Patch(
     *     path="/workflow/processes/restore/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Restaurar un proceso",
     *     description="Reactiva un proceso desactivado. Un proceso que no está desactivado responde 422.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Proceso restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProcessSavedResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(ProcessRestoreRequest $request, $id)
    {
        try {
            $process = Process::withTrashed()->findOrFail($id);
            $process->restore();

            return $this->successResponse(
                [
                    'process' => $process,
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

    /**
     * @OA\Put(
     *     path="/workflow/processes/setting/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Configurar el formulario de un proceso",
     *     description="Guarda el esquema del formulario (`settings.schema`, con sus partes `static` y `dinamic`) que la ficha técnica copia y llena. Reemplaza `settings` completo, así que se pierden las banderas (`subprocesses`, `in_technical_sheet`, etc.) si no se envían. Responde con el proceso y sus procesos anteriores y siguientes. Requiere el permiso `workflow.processes.settings`.",
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
     *         @OA\JsonContent(ref="#/components/schemas/ProcessSavedResponse")
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
    public function setting(ProcessSettingRequest $request, $id)
    {
        try {
            $process = Process::findOrFail($id);
            $process->settings = $request->input('settings');

            $process->save();

            $process->load(['before_processes', 'after_processes']);

            return $this->successResponse(
                [
                    'process' => $process,
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

    /**
     * @OA\Get(
     *     path="/workflow/processes/qr/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Generar el QR del puesto de trabajo (proceso)",
     *     description="Devuelve el QR (SVG en base64) que se pega en el puesto de trabajo. Al escanearlo, la tablet abre Productividad (`<FRONTEND_URL>/productivity?qr=<uuid>`) lista para registrar trabajo. El identificador (uuid) se crea la primera vez y se guarda en `settings.uuid`. Solo se permite en el nivel más bajo: un proceso sin subprocesos, un subproceso sin operaciones o una operación, y que esté activo.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="QR generado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="qr", type="string", example="data:image/svg+xml;base64,PHN2ZyB4bWxucz0i..."),
     *                 @OA\Property(property="url", type="string", example="https://kanri.example.com/productivity?qr=9b2f6c1e-4a7d-4c3b-8e5f-2d1a0b9c8e7f"),
     *                 @OA\Property(
     *                     property="workstation",
     *                     type="object",
     *                     @OA\Property(property="type", type="string", example="process"),
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Corte"),
     *                     @OA\Property(property="path", type="string", example="Corte")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function qr(ProcessQrRequest $request, $id)
    {
        try {
            $process = Process::findOrFail($id);

            $url = rtrim(config('app.frontend_url'), '/').'/productivity?qr='.$process->settingsUuid();

            return $this->successResponse(
                [
                    'qr' => app(QrCodeService::class)->generate($url),
                    'url' => $url,
                    'workstation' => [
                        'type' => 'process',
                        'id' => $process->id,
                        'name' => $process->name,
                        'path' => $process->name,
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
