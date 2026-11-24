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
 * @OA\PathItem(
 *     path="/Processes",
 *     description="Rutas de gestión de procesos"
 * )
 *
 * @OA\Schema(
 *     schema="Process",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="item_id", type="integer", example=10),
 *     @OA\Property(property="name", type="string", example="NO APLICA"),
 *     @OA\Property(property="description", type="string", example="NO APLICA"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=22),
 *         @OA\Property(property="name", type="string", example="Procesos"),
 *         @OA\Property(property="description", type="string", example="Listado de procesos."),
 *         @OA\Property(property="settings", type="string", example="{}"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 * )
 */
class ProcessController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/processes/all",
     *     tags={"Workflow › Processes"},
     *     summary="Listar procesos",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filtro de Busqueda.",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="juan")
     *     ),
     *
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         description="Columna a ordenar.",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"id","name","description","created_at", "updated_at"}, example="name")
     *     ),
     *
     *     @OA\Parameter(
     *         name="dir",
     *         in="query",
     *         description="Dirección de ordenamiento.",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"asc","desc"}, example="asc")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Registros por página.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Parameter(
     *         name="with_trashed",
     *         in="query",
     *         description="Registros inactivos.",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de procesos cargada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="processes",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Process")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(
     *                         property="pagination",
     *                         type="object",
     *                         @OA\Property(property="total", type="integer", example=1),
     *                         @OA\Property(property="count", type="integer", example=1),
     *                         @OA\Property(property="per_page", type="integer", example=1),
     *                         @OA\Property(property="current_page", type="integer", example=1),
     *                         @OA\Property(property="total_pages", type="integer", example=1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
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
     *     path="/processes/find/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Obtener un proceso específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del proceso",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Proceso cargado con éxito",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="process",
     *                     ref="#/components/schemas/Process"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del proceso no registrado.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example=1)
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
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
     *     path="/processes/store",
     *     tags={"Workflow › Processes"},
     *     summary="Crear un proceso",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","description"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="MEDIA",
     *                 description="Nombre del proceso"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="MEDIA",
     *                 description="Descripción del proceso"
     *             ),
     *             @OA\Property(property="after_processes", type="array", description="Procesos siguientes.", @OA\Items(type="integer", example=206)),
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 @OA\Property(property="subprocesses", type="boolean", example=true),
     *                 @OA\Property(property="subprocess_flow", type="string", enum={"sequence", "parallel"}, description="Obligatorio si `subprocesses` es verdadero. En `sequence` los subprocesos se encadenan con `after_subprocesses`; al guardar otro valor se borran esas cadenas.", example="sequence")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Proceso creado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="process",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="NAME_PROCESS"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_PROCESS"),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="id", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del proceso"),
     *                 @OA\Property(property="description", type="string", example="Descripción del proceso"),
     *                 @OA\Property(property="code", type="string", example="Código del proceso")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="Formato Inválido. El campo debe estar en mayúsculas.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
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
     *     path="/processes/update/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Editar un proceso específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del proceso",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","description"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="MEDIA",
     *                 description="Nombre del proceso"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="MEDIA",
     *                 description="Descripción del proceso"
     *             ),
     *             @OA\Property(property="after_processes", type="array", description="Procesos siguientes.", @OA\Items(type="integer", example=206)),
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 @OA\Property(property="subprocesses", type="boolean", example=true),
     *                 @OA\Property(property="subprocess_flow", type="string", enum={"sequence", "parallel"}, description="Obligatorio si `subprocesses` es verdadero. En `sequence` los subprocesos se encadenan con `after_subprocesses`; al guardar otro valor se borran esas cadenas.", example="sequence")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Proceso editado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="process",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_PROCESS"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_PROCESS"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del proceso"),
     *                 @OA\Property(property="description", type="string", example="Descripción del proceso"),
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="Formato Inválido. El campo debe estar en mayúsculas.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
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
     *     path="/processes/delete/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Desactivar un proceso específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del proceso",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Proceso desactivado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="process",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_PROCESS"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_PROCESS"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del proceso")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
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
     *     path="/processes/restore/{id}",
     *     tags={"Workflow › Processes"},
     *     summary="Activar un proceso específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del proceso",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Proceso activado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="process",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_PROCESS"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_PROCESS"),
     *                     @OA\Property(property="settings", type="string", example="{}"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del proceso")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
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
