<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\Department\DepartmentAllRequest;
use App\Http\Resources\Location\Department\DepartmentCollection;
use App\Models\Department;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="Department",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=5),
 *     @OA\Property(property="country_id", type="integer", example=47),
 *     @OA\Property(property="name", type="string", example="ANTIOQUIA"),
 *     @OA\Property(property="type", type="string", nullable=true, example="DEPARTAMENTO", description="Tipo de división administrativa"),
 *     @OA\Property(property="cities_count", type="integer", example=125, description="Cantidad de ciudades del departamento")
 * )
 *
 * @OA\Schema(
 *     schema="DepartmentListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="departments", type="array", @OA\Items(ref="#/components/schemas/Department")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 */
class DepartmentController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/locations/departments/all/{country_id}",
     *     tags={"Locations › Departments"},
     *     summary="Listar los departamentos de un país",
     *     description="Lista paginada de los departamentos del país, con la cantidad de ciudades de cada uno. Sin `per_page` devuelve todos en una sola página. Sin `column` y `dir` ordena por nombre. `search` filtra por nombre. La puede llamar quien tenga alguno de los permisos `locations.countries.all`, `people.store` o `people.update`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="country_id",
     *         in="path",
     *         required=true,
     *         description="País del que se listan los departamentos.",
     *         @OA\Schema(type="integer", example=47)
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Search"),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "name"}, example="name")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de departamentos cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/DepartmentListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="País inexistente o parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"country_id": "País", "per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden."},
     *                 "errors": {"country_id": {"No está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(DepartmentAllRequest $request, $country_id)
    {
        try {
            $departments = Department::withCount(['cities'])->where('country_id', $request->integer('country_id'))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderBy('name');
                });

            $departments = $departments->paginate($request->integer('per_page', $departments->count()));

            return $this->successResponse(
                new DepartmentCollection($departments),
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
