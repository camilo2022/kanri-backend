<?php

namespace App\Http\Controllers;

use App\Http\Requests\Navegation\Module\ModuleAllRequest;
use App\Http\Requests\Navegation\Module\ModuleDeleteRequest;
use App\Http\Requests\Navegation\Module\ModuleFindRequest;
use App\Http\Requests\Navegation\Module\ModuleRestoreRequest;
use App\Http\Requests\Navegation\Module\ModuleStoreRequest;
use App\Http\Requests\Navegation\Module\ModuleUpdateRequest;
use App\Http\Resources\Navegation\Module\ModuleCollection;
use App\Http\Resources\Navegation\Module\ModuleResource;
use App\Models\Module;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="ModuleSubmodule",
 *     type="object",
 *     description="Submódulo (opción del menú). `module_id` y `permission_id` están ocultos en el modelo.",
 *     @OA\Property(property="id", type="integer", example=41),
 *     @OA\Property(property="name", type="string", example="Despachos"),
 *     @OA\Property(property="url", type="string", description="Ruta del frontend.", example="/dispatches"),
 *     @OA\Property(property="icon", type="string", description="Icono de react-icons (Font Awesome).", example="FaTruck"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T21:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T21:30:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 * )
 *
 * @OA\Schema(
 *     schema="Module",
 *     type="object",
 *     description="Módulo del menú (tabla `modules`) serializado por Eloquent, con sus submódulos. Es el que viene en el menú del usuario (`GET /auth/user`).",
 *     @OA\Property(property="id", type="integer", example=6),
 *     @OA\Property(property="name", type="string", example="Bodega"),
 *     @OA\Property(property="icon", type="string", description="Icono de react-icons (Font Awesome).", example="FaWarehouse"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T21:25:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T21:25:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="submodules", type="array", @OA\Items(ref="#/components/schemas/ModuleSubmodule"))
 * )
 *
 * @OA\Schema(
 *     schema="ModuleListItem",
 *     type="object",
 *     description="Módulo como lo entrega `ModuleCollection` (all).",
 *     @OA\Property(property="id", type="integer", example=6),
 *     @OA\Property(property="name", type="string", example="Bodega"),
 *     @OA\Property(property="icon", type="string", example="FaWarehouse"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 16:25:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 16:25:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="submodules", type="array", @OA\Items(ref="#/components/schemas/ModuleSubmodule"))
 * )
 *
 * @OA\Schema(
 *     schema="ModuleDetail",
 *     type="object",
 *     description="Módulo como lo entrega `ModuleResource` (find, store, update, delete, restore). Hoy **no incluye `icon`** y trae `item_id`, `description`, `settings` e `item` siempre en `null`, porque el Resource está copiado de los catálogos (ver pendientes).",
 *     @OA\Property(property="id", type="integer", example=6),
 *     @OA\Property(property="item_id", nullable=true, example=null),
 *     @OA\Property(property="name", type="string", example="Bodega"),
 *     @OA\Property(property="description", nullable=true, example=null),
 *     @OA\Property(property="settings", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 16:25:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 16:25:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="item", nullable=true, example=null),
 *     @OA\Property(property="submodules", type="array", @OA\Items(ref="#/components/schemas/ModuleSubmodule"))
 * )
 *
 * @OA\Schema(
 *     schema="ModuleResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="module", ref="#/components/schemas/ModuleDetail")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ModuleListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="modules", type="array", @OA\Items(ref="#/components/schemas/ModuleListItem")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ModuleRequest",
 *     type="object",
 *     required={"name", "icon"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         maxLength=18,
 *         pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\.?\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$",
 *         description="Solo letras (con tildes y Ñ); palabras separadas por un espacio (se admite un punto antes del espacio). Sin números. Único.",
 *         example="Bodega"
 *     ),
 *     @OA\Property(
 *         property="icon",
 *         type="string",
 *         maxLength=40,
 *         pattern="^Fa([A-Z][a-zA-Z0-9]*|[0-9][a-zA-Z0-9]*)$",
 *         description="Nombre de un icono de react-icons Font Awesome (prefijo `Fa`).",
 *         example="FaWarehouse"
 *     )
 * )
 */
class ModuleController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/navegation/modules/all",
     *     tags={"Navegation › Modules"},
     *     summary="Listar los módulos del menú",
     *     description="Cada módulo trae sus submódulos. La búsqueda mira el nombre y el icono.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "name", "icon", "created_at", "updated_at"}, example="name")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de módulos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ModuleListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"with_trashed": "Registros inactivos"},
     *                 "errors": {"column": {"El valor de la columna no es válido. "}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(ModuleAllRequest $request)
    {
        try {
            $modules = Module::with(['submodules'])
                ->when($request->input('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) use ($request) {
                    return $query->withTrashed();
                })
                ->when($request->input('column') && $request->input('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $modules = $modules->paginate($request->input('per_page', $modules->count()));

            return $this->successResponse(
                new ModuleCollection($modules),
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
     *     path="/navegation/modules/find/{id}",
     *     tags={"Navegation › Modules"},
     *     summary="Obtener un módulo",
     *     description="Hoy la respuesta no incluye `icon` (ver `ModuleDetail`).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ModuleResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(ModuleFindRequest $request, $id)
    {
        try {
            $module = Module::findOrFail($id);

            $module->load(['submodules']);

            return $this->successResponse(
                new ModuleResource($module),
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
     *     path="/navegation/modules/store",
     *     tags={"Navegation › Modules"},
     *     summary="Crear un módulo",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ModuleRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo creado correctamente (responde 200, no 201). `submodules` llega vacío.",
     *         @OA\JsonContent(ref="#/components/schemas/ModuleResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre del módulo", "icon": "Icono del módulo"},
     *                 "errors": {"icon": {"Formato inválido. "}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(ModuleStoreRequest $request)
    {
        try {
            $module = new Module();

            $module->name = $request->input('name');
            $module->icon = $request->input('icon');

            $module->save();

            return $this->successResponse(
                new ModuleResource($module),
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
     *     path="/navegation/modules/update/{id}",
     *     tags={"Navegation › Modules"},
     *     summary="Editar un módulo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ModuleRequest",
     *             example={"name": "Inventarios", "icon": "FaBoxes"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ModuleResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o módulo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre del módulo", "icon": "Icono del módulo"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(ModuleUpdateRequest $request, $id)
    {
        try {
            $module = Module::findOrFail($id);

            $module->name = $request->input('name');
            $module->icon =  $request->input('icon');

            $module->save();

            return $this->successResponse(
                new ModuleResource($module),
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
     * @OA\Delete(
     *     path="/navegation/modules/delete/{id}",
     *     tags={"Navegation › Modules"},
     *     summary="Desactivar un módulo",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse. No desactiva sus submódulos.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/ModuleResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(ModuleDeleteRequest $request)
    {
        try {
            $module = Module::findOrFail($request->id);
            $module->delete();

            return $this->successResponse(
                new ModuleResource($module),
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
     *     path="/navegation/modules/restore/{id}",
     *     tags={"Navegation › Modules"},
     *     summary="Restaurar un módulo desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ModuleResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(ModuleRestoreRequest $request)
    {
        try {
            $module = Module::withTrashed()->findOrFail($request->id);
            $module->restore();

            return $this->successResponse(
                new ModuleResource($module),
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
