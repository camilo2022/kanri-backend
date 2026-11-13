<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\PersonType\PersonTypeAllRequest;
use App\Http\Requests\Typification\PersonType\PersonTypeDeleteRequest;
use App\Http\Requests\Typification\PersonType\PersonTypeFindRequest;
use App\Http\Requests\Typification\PersonType\PersonTypeRestoreRequest;
use App\Http\Requests\Typification\PersonType\PersonTypeStoreRequest;
use App\Http\Requests\Typification\PersonType\PersonTypeUpdateRequest;
use App\Http\Resources\Typification\PersonType\PersonTypeCollection;
use App\Http\Resources\Typification\PersonType\PersonTypeResource;
use App\Models\PersonType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="PersonTypeDocumentType",
 *     description="Tipo de documento del tipo de persona, con el pivote de `model_has_subitems`. Su `settings` trae las reglas del número de documento.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Subitem"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\Models\DocumentType"),
 *                 @OA\Property(property="subitem_id", type="integer", description="Id del tipo de persona.", example=530),
 *                 @OA\Property(property="model_id", type="integer", description="Id del tipo de documento.", example=531)
 *             )
 *         )
 *     },
 *     example={"id": 531, "item_id": 36, "name": "NÚMERO DE IDENTIFICACIÓN TRIBUTARIA", "description": "NIT DE LA EMPRESA", "settings": null, "created_at": "2026-05-14T14:05:00.000000Z", "updated_at": "2026-05-14T14:05:00.000000Z", "deleted_at": null, "pivot": {"model_type": "App\Models\DocumentType", "subitem_id": 530, "model_id": 531}}
 * )
 *
 * @OA\Schema(
 *     schema="PersonType",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=530),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=35),
 *     @OA\Property(property="name", type="string", example="JURÍDICA"),
 *     @OA\Property(property="description", type="string", example="PERSONA JURÍDICA"),
 *     @OA\Property(property="settings", type="object", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 14:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 14:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=35),
 *         @OA\Property(property="name", type="string", example="Tipos de Personas"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de personas."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(property="document_types", type="array", description="Tipos de documento del tipo de persona.", @OA\Items(ref="#/components/schemas/PersonTypeDocumentType"))
 * )
 *
 * @OA\Schema(
 *     schema="PersonTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="person_type", ref="#/components/schemas/PersonType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PersonTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="person_types", type="array", @OA\Items(ref="#/components/schemas/PersonType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PersonTypeRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de persona.", example="JURÍDICA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="PERSONA JURÍDICA")
 * )
 */
class PersonTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/person_types/all",
     *     tags={"Typification › Person Types"},
     *     summary="Listar los tipos de persona",
     *     description="Cada tipo de persona trae sus tipos de documento. En esta lista no viene `item_id`.",
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
     *         description="Lista de tipos de persona cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonTypeListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos", "in_technical_sheet": "Aplica a Ficha Técnica"},
     *                 "errors": {"dir": {"Valor inválido. Permitidos: asc, desc."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(PersonTypeAllRequest $request)
    {
        try {
            $person_types = PersonType::with(['item', 'document_types'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $person_types = $person_types->paginate($request->integer('per_page', $person_types->count()));

            return $this->successResponse(
                new PersonTypeCollection($person_types),
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
     *     path="/typification/person_types/find/{id}",
     *     tags={"Typification › Person Types"},
     *     summary="Obtener un tipo de persona",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de persona cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(PersonTypeFindRequest $request, $id)
    {
        try {
            $person_type = PersonType::with(['document_types'])
                ->findOrFail($id);

            return $this->successResponse(
                new PersonTypeResource($person_type),
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
     *     path="/typification/person_types/store",
     *     tags={"Typification › Person Types"},
     *     summary="Crear un tipo de persona",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PersonTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de persona creado correctamente. `document_types` llega vacío.",
     *         @OA\JsonContent(ref="#/components/schemas/PersonTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(PersonTypeStoreRequest $request)
    {
        try {
            $person_type = new PersonType;
            $person_type->name = $request->input('name');
            $person_type->description = $request->input('description');

            $person_type->save();

            return $this->successResponse(
                new PersonTypeResource($person_type),
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
     *     path="/typification/person_types/update/{id}",
     *     tags={"Typification › Person Types"},
     *     summary="Editar un tipo de persona",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/PersonTypeRequest",
     *             example={"name": "EXTRANJERA", "description": "PERSONA NATURAL EXTRANJERA"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de persona editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de persona no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de persona", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(PersonTypeUpdateRequest $request, $id)
    {
        try {
            $person_type = PersonType::findOrFail($id);
            $person_type->name = $request->input('name');
            $person_type->description = $request->input('description');
            $person_type->save();

            return $this->successResponse(
                new PersonTypeResource($person_type),
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
     *     path="/typification/person_types/delete/{id}",
     *     tags={"Typification › Person Types"},
     *     summary="Desactivar un tipo de persona",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de persona desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/PersonTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(PersonTypeDeleteRequest $request, $id)
    {
        try {
            $person_type = PersonType::findOrFail($id);
            $person_type->delete();

            return $this->successResponse(
                new PersonTypeResource($person_type),
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
     *     path="/typification/person_types/restore/{id}",
     *     tags={"Typification › Person Types"},
     *     summary="Restaurar un tipo de persona desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de persona restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(PersonTypeRestoreRequest $request, $id)
    {
        try {
            $person_type = PersonType::withTrashed()->findOrFail($id);
            $person_type->restore();

            return $this->successResponse(
                new PersonTypeResource($person_type),
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
