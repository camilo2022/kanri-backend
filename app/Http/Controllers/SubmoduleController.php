<?php

namespace App\Http\Controllers;

use App\Http\Requests\Navegation\Submodule\SubmoduleAllRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleDeleteRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleFindRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleRestoreRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleStoreRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleUpdateRequest;
use App\Http\Resources\Navegation\Submodule\SubmoduleCollection;
use App\Http\Resources\Navegation\Submodule\SubmoduleResource;
use App\Models\Submodule;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="SubmoduleModule",
 *     type="object",
 *     description="Módulo al que pertenece el submódulo (sin sus submódulos).",
 *     @OA\Property(property="id", type="integer", example=6),
 *     @OA\Property(property="name", type="string", example="Bodega"),
 *     @OA\Property(property="icon", type="string", example="FaWarehouse"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T21:25:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T21:25:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 * )
 *
 * @OA\Schema(
 *     schema="Submodule",
 *     type="object",
 *     description="Submódulo serializado por Eloquent (store, update, delete, restore): `module_id` y `permission_id` están ocultos; vienen `module` y `permission`. Fechas en ISO/UTC.",
 *     @OA\Property(property="id", type="integer", example=45),
 *     @OA\Property(property="name", type="string", example="Despachos"),
 *     @OA\Property(property="url", type="string", example="/dispatches"),
 *     @OA\Property(property="icon", type="string", example="FaTruck"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T21:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T21:30:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="module", ref="#/components/schemas/SubmoduleModule"),
 *     @OA\Property(property="permission", ref="#/components/schemas/Permission")
 * )
 *
 * @OA\Schema(
 *     schema="SubmoduleListItem",
 *     type="object",
 *     description="Submódulo como lo entrega `SubmoduleCollection` (all).",
 *     @OA\Property(property="id", type="integer", example=45),
 *     @OA\Property(property="name", type="string", example="Despachos"),
 *     @OA\Property(property="url", type="string", example="/dispatches"),
 *     @OA\Property(property="icon", type="string", example="FaTruck"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 16:30:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 16:30:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="module", ref="#/components/schemas/SubmoduleModule"),
 *     @OA\Property(property="permission", ref="#/components/schemas/Permission")
 * )
 *
 * @OA\Schema(
 *     schema="SubmoduleDetail",
 *     type="object",
 *     description="Submódulo como lo entrega `SubmoduleResource` (find). Trae `item_id`, `settings` e `item` siempre en `null`, porque el Resource está copiado de los catálogos (ver pendientes).",
 *     @OA\Property(property="id", type="integer", example=45),
 *     @OA\Property(property="item_id", nullable=true, example=null),
 *     @OA\Property(property="name", type="string", example="Despachos"),
 *     @OA\Property(property="icon", type="string", example="FaTruck"),
 *     @OA\Property(property="url", type="string", example="/dispatches"),
 *     @OA\Property(property="settings", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 16:30:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 16:30:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="item", nullable=true, example=null),
 *     @OA\Property(property="permission", ref="#/components/schemas/Permission"),
 *     @OA\Property(property="module", ref="#/components/schemas/SubmoduleModule")
 * )
 *
 * @OA\Schema(
 *     schema="SubmoduleResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="submodule", ref="#/components/schemas/SubmoduleDetail")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubmoduleSavedResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="submodule", ref="#/components/schemas/Submodule")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubmoduleListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="submodules", type="array", @OA\Items(ref="#/components/schemas/SubmoduleListItem")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SubmoduleRequest",
 *     type="object",
 *     required={"module_id", "permission_id", "name", "url", "icon"},
 *     @OA\Property(property="module_id", type="integer", description="Módulo del menú donde aparece.", example=6),
 *     @OA\Property(property="permission_id", type="integer", description="Permiso que debe tener el usuario para ver esta opción en el menú. Único: cada permiso abre un solo submódulo.", example=371),
 *     @OA\Property(property="name", type="string", maxLength=18, pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\.?\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$", description="Texto del menú: palabras separadas por un espacio (se permite abreviar con punto, por ejemplo `C. de Producción`). Único.", example="Despachos"),
 *     @OA\Property(property="url", type="string", maxLength=40, pattern="^\/[a-z_]+(\/[a-z_]+)*\/?$", description="Ruta del frontend, en minúsculas y guiones bajos. Única.", example="/dispatches"),
 *     @OA\Property(property="icon", type="string", maxLength=40, pattern="^Fa([A-Z][a-zA-Z0-9]*|[0-9][a-zA-Z0-9]*)$", description="Nombre de un icono Font Awesome de react-icons.", example="FaTruck")
 * )
 */
class SubmoduleController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/navegation/modules/submodules/all/{module_id}",
     *     tags={"Navegation › Submodules"},
     *     summary="Listar los submódulos de un módulo",
     *     description="Cada submódulo trae su módulo y su permiso.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="module_id",
     *         in="path",
     *         required=true,
     *         description="Módulo.",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Cada palabra se busca en nombre, URL y nombre o título del permiso.",
     *         @OA\Schema(type="string", example="despachos")
     *     ),
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
     *         description="Lista de submódulos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubmoduleListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Módulo no registrado o parámetros inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"module_id": "Identificador del módulo. ", "per_page": "Registros por pagina. ", "search": "Filtro de Busqueda. ", "column": "Columna a ordenar. ", "dir": "Sentido del orden. ", "with_trashed": "Registros inactivos"}, "errors": {"module_id": {"No hay ningún registro. "}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(SubmoduleAllRequest $request, $module_id)
    {
        try {
            $submodules = Submodule::where('module_id', $module_id)
                ->when($request->input('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) use ($request) {
                    return $query->withTrashed();
                })
                ->when($request->input('column') && $request->input('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $submodules = $submodules->paginate($request->input('per_page', $submodules->count()));

            return $this->successResponse(
                new SubmoduleCollection($submodules),
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
     *     path="/navegation/modules/submodules/find/{id}",
     *     tags={"Navegation › Submodules"},
     *     summary="Obtener un submódulo",
     *     description="También la puede usar quien tiene permiso de editar submódulos.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubmoduleResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SubmoduleFindRequest $request)
    {
        try {
            $submodule = Submodule::with(['permission', 'module'])
                ->findOrFail($request->id);

            return $this->successResponse(
                new SubmoduleResource($submodule),
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
     *     path="/navegation/modules/submodules/store",
     *     tags={"Navegation › Submodules"},
     *     summary="Crear un submódulo",
     *     description="Agrega una opción al menú. Responde **200** (no 201) con el submódulo tal cual lo serializa Eloquent.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SubmoduleRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubmoduleSavedResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"module_id": "Identificador del módulo", "permission_id": "Identificador del permiso", "name": "Nombre del submódulo", "url": "Ruta del sobmódulo", "icon": "Icono del módulo"},
     *                 "errors": {"permission_id": {"Ya está registrado."}, "icon": {"Formato inválido. "}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SubmoduleStoreRequest $request)
    {
        try {
            $submodule = new Submodule();

            $submodule->name = $request->input('name');
            $submodule->url = $request->input('url');
            $submodule->icon = $request->input('icon');
            $submodule->module_id = $request->integer('module_id');
            $submodule->permission_id = $request->integer('permission_id');

            $submodule->save();

            $submodule->load(['module', 'permission']);

            return $this->successResponse(
                new SubmoduleResource($submodule),
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
     *     path="/navegation/modules/submodules/update/{id}",
     *     tags={"Navegation › Submodules"},
     *     summary="Editar un submódulo",
     *     description="Reemplaza módulo, permiso, nombre, URL e icono.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/SubmoduleRequest",
     *             example={"module_id": 6, "permission_id": 371, "name": "Despachos", "url": "/warehouse/dispatches", "icon": "FaTruckLoading"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubmoduleSavedResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o submódulo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"module_id": "Identificador del módulo", "permission_id": "Identificador del permiso", "name": "Nombre del submódulo", "url": "Ruta del sobmódulo", "icon": "Icono del módulo"},
     *                 "errors": {"url": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SubmoduleUpdateRequest $request, $id)
    {
        try {
            $submodule = Submodule::findOrFail($id);

            $submodule->name = $request->input('name');
            $submodule->url = $request->input('url');
            $submodule->icon = $request->input('icon');
            $submodule->module_id = $request->integer('module_id');
            $submodule->permission_id = $request->integer('permission_id');

            $submodule->save();

            $submodule->load(['module', 'permission']);

            return $this->successResponse(
                new SubmoduleResource($submodule),
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
     *     path="/navegation/modules/submodules/delete/{id}",
     *     tags={"Navegation › Submodules"},
     *     summary="Desactivar un submódulo",
     *     description="Desactivación lógica (soft delete): sale del menú, pero se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/SubmoduleSavedResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SubmoduleDeleteRequest $request)
    {
        try {
            $submodule = Submodule::findOrFail($request->id);
            $submodule->delete();

            $submodule->load(['module', 'permission']);

            return $this->successResponse(
                new SubmoduleResource($submodule),
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
     *     path="/navegation/modules/submodules/restore/{id}",
     *     tags={"Navegation › Submodules"},
     *     summary="Restaurar un submódulo desactivado",
     *     description="Solo acepta submódulos desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SubmoduleSavedResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SubmoduleRestoreRequest $request)
    {
        try {
            $submodule = Submodule::withTrashed()->findOrFail($request->id);
            $submodule->restore();

            $submodule->load(['module', 'permission']);

            return $this->successResponse(
                new SubmoduleResource($submodule),
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
