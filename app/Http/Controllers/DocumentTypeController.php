<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\PersonType\DocumentType\DocumentTypeAllRequest;
use App\Http\Requests\Typification\PersonType\DocumentType\DocumentTypeDeleteRequest;
use App\Http\Requests\Typification\PersonType\DocumentType\DocumentTypeFindRequest;
use App\Http\Requests\Typification\PersonType\DocumentType\DocumentTypeRestoreRequest;
use App\Http\Requests\Typification\PersonType\DocumentType\DocumentTypeSettingsRequest;
use App\Http\Requests\Typification\PersonType\DocumentType\DocumentTypeStoreRequest;
use App\Http\Requests\Typification\PersonType\DocumentType\DocumentTypeUpdateRequest;
use App\Http\Resources\Typification\PersonType\DocumentType\DocumentTypeCollection;
use App\Http\Resources\Typification\PersonType\DocumentType\DocumentTypeResource;
use App\Models\DocumentType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="DocumentTypeValidation",
 *     type="object",
 *     required={"id", "regex", "message", "created_at"},
 *     description="Regla que debe cumplir el número de documento.",
 *     @OA\Property(property="id", type="integer", description="Identificador de la regla, único dentro de la lista.", example=1),
 *     @OA\Property(property="regex", type="string", description="Expresión regular de PHP, con delimitadores. Debe ser válida y no repetirse en la lista.", example="/^\d{6,10}$/"),
 *     @OA\Property(property="message", type="string", maxLength=255, description="Mensaje que se muestra cuando no se cumple.", example="Debe tener entre 6 y 10 dígitos"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T13:40:00.000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 * )
 *
 * @OA\Schema(
 *     schema="DocumentTypeSettings",
 *     type="object",
 *     required={"validations"},
 *     @OA\Property(property="validations", type="array", minItems=1, @OA\Items(ref="#/components/schemas/DocumentTypeValidation"))
 * )
 *
 * @OA\Schema(
 *     schema="DocumentType",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=512),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=36),
 *     @OA\Property(property="name", type="string", example="CÉDULA DE EXTRANJERÍA"),
 *     @OA\Property(property="description", type="string", example="DOCUMENTO DE IDENTIDAD PARA EXTRANJEROS RESIDENTES"),
 *     @OA\Property(property="settings", nullable=true, description="Reglas del número de documento. `null` hasta que se configuran con `settings`.", allOf={@OA\Schema(ref="#/components/schemas/DocumentTypeSettings")}),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 13:35:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 13:35:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=36),
 *         @OA\Property(property="name", type="string", example="Tipos de Documentos"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de documentos."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(
 *         property="person_type",
 *         nullable=true,
 *         description="Tipo de persona al que pertenece (con `pivot`). No viene en la respuesta de `all`.",
 *         allOf={@OA\Schema(ref="#/components/schemas/Subitem")},
 *         example={"id": 421, "item_id": 35, "name": "NATURAL", "description": "NATURAL", "settings": {}, "created_at": "2026-03-30T13:17:29.000000Z", "updated_at": "2026-03-30T13:17:29.000000Z", "deleted_at": null}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="DocumentTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="document_type", ref="#/components/schemas/DocumentType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="DocumentTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="document_types", type="array", @OA\Items(ref="#/components/schemas/DocumentType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="DocumentTypeRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de documento.", example="CÉDULA DE EXTRANJERÍA"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="DOCUMENTO DE IDENTIDAD PARA EXTRANJEROS RESIDENTES")
 * )
 */
class DocumentTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/person_types/document_types/all/{person_type_id}",
     *     tags={"Typification › Document Types"},
     *     summary="Listar los tipos de documento de un tipo de persona",
     *     description="En esta lista no vienen `item_id` ni `person_type`. Un tipo de persona inexistente devuelve una lista vacía.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="person_type_id",
     *         in="path",
     *         required=true,
     *         description="Identificador del tipo de persona. (En `routes/api.php` el parámetro se llama `file_type_id`.)",
     *         @OA\Schema(type="integer", example=421)
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
     *         description="Lista de tipos de documento cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/DocumentTypeListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"column": {"Valor inválido. Permitidos: id, name, description, created_at, updated_at, deleted_at."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(DocumentTypeAllRequest $request, $person_type_id)
    {
        try {
            $document_types = DocumentType::with(['person_type'])
                ->whereHas('person_type', fn ($r) => $r->where('id', $person_type_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $document_types = $document_types->paginate($request->integer('per_page', $document_types->count()));

            return $this->successResponse(
                new DocumentTypeCollection($document_types),
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
     *     path="/typification/person_types/document_types/find/{id}",
     *     tags={"Typification › Document Types"},
     *     summary="Obtener un tipo de documento",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de documento cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/DocumentTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(DocumentTypeFindRequest $request, $id)
    {
        try {
            $document_type = DocumentType::with(['person_type'])->findOrFail($id);

            return $this->successResponse(
                new DocumentTypeResource($document_type),
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
     *     path="/typification/person_types/document_types/store",
     *     tags={"Typification › Document Types"},
     *     summary="Crear un tipo de documento",
     *     description="Lo asocia al tipo de persona indicado. Se crea sin reglas (`settings` en `null`); se configuran con `settings`.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"person_type_id", "name", "description"},
     *             allOf={
     *                 @OA\Schema(@OA\Property(property="person_type_id", type="integer", description="Tipo de persona al que pertenece.", example=421)),
     *                 @OA\Schema(ref="#/components/schemas/DocumentTypeRequest")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de documento creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/DocumentTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"person_type_id": "Identificador del tipo de persona", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"person_type_id": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(DocumentTypeStoreRequest $request)
    {
        try {
            $document_type = new DocumentType;
            $document_type->name = $request->input('name');
            $document_type->description = $request->input('description');
            $document_type->save();

            $document_type->auditSync('person_type', $request->integer('person_type_id'));

            return $this->successResponse(
                new DocumentTypeResource($document_type),
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
     *     path="/typification/person_types/document_types/update/{id}",
     *     tags={"Typification › Document Types"},
     *     summary="Editar un tipo de documento",
     *     description="Solo cambia nombre y descripción: no cambia el tipo de persona ni las reglas.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/DocumentTypeRequest",
     *             example={"name": "PASAPORTE", "description": "PASAPORTE VIGENTE EXPEDIDO POR EL PAÍS DE ORIGEN"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de documento editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/DocumentTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de documento no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de documento", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(DocumentTypeUpdateRequest $request, $id)
    {
        try {
            $document_type = DocumentType::findOrFail($id);
            $document_type->name = $request->input('name');
            $document_type->description = $request->input('description');
            $document_type->save();

            return $this->successResponse(
                new DocumentTypeResource($document_type),
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
     *     path="/typification/person_types/document_types/delete/{id}",
     *     tags={"Typification › Document Types"},
     *     summary="Desactivar un tipo de documento",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de documento desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/DocumentTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(DocumentTypeDeleteRequest $request, $id)
    {
        try {
            $document_type = DocumentType::findOrFail($id);
            $document_type->delete();

            return $this->successResponse(
                new DocumentTypeResource($document_type),
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
     *     path="/typification/person_types/document_types/restore/{id}",
     *     tags={"Typification › Document Types"},
     *     summary="Restaurar un tipo de documento desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de documento restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/DocumentTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(DocumentTypeRestoreRequest $request, $id)
    {
        try {
            $document_type = DocumentType::withTrashed()->findOrFail($id);
            $document_type->restore();

            return $this->successResponse(
                new DocumentTypeResource($document_type),
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
     *     path="/typification/person_types/document_types/settings/{id}",
     *     tags={"Typification › Document Types"},
     *     summary="Configurar las reglas del número de documento",
     *     description="Reemplaza `settings` completo con la lista de validaciones (al menos una). Cada `regex` debe ser una expresión regular válida y no repetirse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"settings"},
     *             @OA\Property(property="settings", ref="#/components/schemas/DocumentTypeSettings"),
     *             example={"settings": {"validations": {{"id": 1, "regex": "/^\d{6,10}$/", "message": "Debe tener entre 6 y 10 dígitos", "created_at": "2026-05-14T13:40:00.000Z", "updated_at": null, "deleted_at": null}}}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reglas guardadas correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/DocumentTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El tipo de documento no existe (este endpoint no valida el id: responde 404 en lugar de 422).",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse",
     *             example={"error": {"message": "No se encontraron resultados para la búsqueda.", "error": "No query results for model [App\Models\DocumentType] 999."}}
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Reglas inválidas.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"settings": "Configuración", "settings.validations": "Validaciones", "settings.validations.0.regex": "Expresión regular (validación n° 1)"},
     *                 "errors": {"settings.validations.0.regex": {"Debe ser una expresión regular válida."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function settings(DocumentTypeSettingsRequest $request, $id)
    {
        try {
            $document_type = DocumentType::findOrFail($id);
            $document_type->settings = $request->input('settings');

            $document_type->save();

            return $this->successResponse(
                new DocumentTypeResource($document_type),
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
