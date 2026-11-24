<?php

namespace App\Http\Controllers;

use App\Http\Requests\RiskManager\RiskManagerAllRequest;
use App\Http\Requests\RiskManager\RiskManagerDeleteRequest;
use App\Http\Requests\RiskManager\RiskManagerFindRequest;
use App\Http\Requests\RiskManager\RiskManagerRestoreRequest;
use App\Http\Requests\RiskManager\RiskManagerStoreRequest;
use App\Http\Requests\RiskManager\RiskManagerUpdateRequest;
use App\Http\Resources\RiskManager\RiskManagerCollection;
use App\Http\Resources\RiskManager\RiskManagerResource;
use App\Models\RiskManager;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Risk Managers",
 *     description="Endpoints para gestionar administradoras de riesgos laborales (ARL)"
 * )
 *
 * @OA\Schema(
 *     schema="RiskManager",
 *     type="object",
 *     description="Administradora de riesgos laborales (ARL) a la que se afilia un empleado.",
 *     @OA\Property(property="id", type="integer", example=241),
 *     @OA\Property(property="item_id", type="integer", example=7),
 *     @OA\Property(property="name", type="string", example="POSITIVA"),
 *     @OA\Property(property="description", type="string", example="POSITIVA COMPAÑÍA DE SEGUROS S.A."),
 *     @OA\Property(property="settings", type="object", nullable=true, description="No viene en la respuesta de `all`.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 09:15:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 09:15:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=7),
 *         @OA\Property(property="name", type="string", example="Administradora de Riesgos"),
 *         @OA\Property(property="description", type="string", example="Listado de administradoras de riesgos laborales."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RiskManagerResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="risk_manager", ref="#/components/schemas/RiskManager")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="RiskManagerListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="risk_managers", type="array", @OA\Items(ref="#/components/schemas/RiskManager")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="RiskManagerRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=100, description="Nombre en MAYÚSCULAS. Único entre las administradoras de riesgos.", example="POSITIVA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="POSITIVA COMPAÑÍA DE SEGUROS S.A.")
 * )
 */
class RiskManagerController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/risk_managers/all",
     *     tags={"Risk Managers"},
     *     summary="Listar las administradoras de riesgos",
     *     description="También la pueden consultar quienes crean o editan empleados (`employees.store`, `employees.update`). En esta lista no viene `settings`.",
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
     *         description="Lista de administradoras de riesgos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RiskManagerListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"dir": {"Valor inválido. Permitidos: asc, desc."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(RiskManagerAllRequest $request)
    {
        try {
            $risk_managers = RiskManager::with(['item'])->when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $risk_managers = $risk_managers->paginate($request->integer('per_page', $risk_managers->count()));

            return $this->successResponse(
                new RiskManagerCollection($risk_managers),
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
     *     path="/risk_managers/find/{id}",
     *     tags={"Risk Managers"},
     *     summary="Obtener una administradora de riesgos",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Administradora de riesgos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RiskManagerResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(RiskManagerFindRequest $request, $id)
    {
        try {
            $risk_manager = RiskManager::findOrFail($id);

            return $this->successResponse(
                new RiskManagerResource($risk_manager),
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
     *     path="/risk_managers/store",
     *     tags={"Risk Managers"},
     *     summary="Crear una administradora de riesgos",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RiskManagerRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Administradora de riesgos creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RiskManagerResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(RiskManagerStoreRequest $request)
    {
        try {
            $risk_manager = new RiskManager();
            $risk_manager->name = $request->input('name');
            $risk_manager->description = $request->input('description');
            $risk_manager->save();

            return $this->successResponse(
                new RiskManagerResource($risk_manager),
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
     *     path="/risk_managers/update/{id}",
     *     tags={"Risk Managers"},
     *     summary="Editar una administradora de riesgos",
     *     description="Reemplaza nombre y descripción.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/RiskManagerRequest",
     *             example={"name": "COLMENA", "description": "COLMENA SEGUROS RIESGOS LABORALES S.A."}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Administradora de riesgos editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RiskManagerResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o administradora de riesgos no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la empresa administradora de riesgos", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(RiskManagerUpdateRequest $request, $id)
    {
        try {
            $risk_manager = RiskManager::findOrFail($id);
            $risk_manager->name = $request->input('name');
            $risk_manager->description = $request->input('description');
            $risk_manager->save();

            return $this->successResponse(
                new RiskManagerResource($risk_manager),
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
     *     path="/risk_managers/delete/{id}",
     *     tags={"Risk Managers"},
     *     summary="Desactivar una administradora de riesgos",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Administradora de riesgos desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/RiskManagerResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(RiskManagerDeleteRequest $request, $id)
    {
        try {
            $risk_manager = RiskManager::findOrFail($id);
            $risk_manager->delete();

            return $this->successResponse(
                new RiskManagerResource($risk_manager),
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
     *     path="/risk_managers/restore/{id}",
     *     tags={"Risk Managers"},
     *     summary="Restaurar una administradora de riesgos desactivada",
     *     description="Solo acepta registros desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Administradora de riesgos restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RiskManagerResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(RiskManagerRestoreRequest $request, $id)
    {
        try {
            $risk_manager = RiskManager::withTrashed()->findOrFail($id);
            $risk_manager->restore();

            return $this->successResponse(
                new RiskManagerResource($risk_manager),
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
