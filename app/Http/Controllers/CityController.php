<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\City\CityAllRequest;
use App\Http\Resources\Location\City\CityCollection;
use App\Models\City;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="City",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1021),
 *     @OA\Property(property="department_id", type="integer", example=5),
 *     @OA\Property(property="name", type="string", example="MEDELLÍN")
 * )
 *
 * @OA\Schema(
 *     schema="CityListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="cities", type="array", @OA\Items(ref="#/components/schemas/City")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 */
class CityController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/locations/cities/all/{department_id}",
     *     tags={"Locations › Cities"},
     *     summary="Listar las ciudades de un departamento",
     *     description="Lista paginada de las ciudades del departamento. Sin `per_page` devuelve todas en una sola página. Sin `column` y `dir` ordena por nombre. `search` filtra por nombre. La puede llamar quien tenga alguno de los permisos `locations.countries.all`, `people.store` o `people.update`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="department_id",
     *         in="path",
     *         required=true,
     *         description="Departamento del que se listan las ciudades.",
     *         @OA\Schema(type="integer", example=5)
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
     *         description="Lista de ciudades cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/CityListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Departamento inexistente o parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"department_id": "Departamento", "per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden."},
     *                 "errors": {"department_id": {"No está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(CityAllRequest $request, $department_id)
    {
        try {
            $cities = City::where('department_id', $request->integer('department_id'))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderBy('name');
                });

            $cities = $cities->paginate($request->integer('per_page', $cities->count()));

            return $this->successResponse(
                new CityCollection($cities),
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
