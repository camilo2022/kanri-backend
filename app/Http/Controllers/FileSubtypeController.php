<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\FileType\FileSubtype\FileSubtypeAllRequest;
use App\Http\Requests\Typification\FileType\FileSubtype\FileSubtypeDeleteRequest;
use App\Http\Requests\Typification\FileType\FileSubtype\FileSubtypeFindRequest;
use App\Http\Requests\Typification\FileType\FileSubtype\FileSubtypeRestoreRequest;
use App\Http\Requests\Typification\FileType\FileSubtype\FileSubtypeStoreRequest;
use App\Http\Requests\Typification\FileType\FileSubtype\FileSubtypeUpdateRequest;
use App\Http\Resources\Typification\FileType\FileSubtype\FileSubtypeCollection;
use App\Http\Resources\Typification\FileType\FileSubtype\FileSubtypeResource;
use App\Models\FileSubtype;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="FileSubtype",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=471),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=2),
 *     @OA\Property(property="name", type="string", example="CÁMARA DE COMERCIO"),
 *     @OA\Property(property="description", type="string", example="CERTIFICADO DE EXISTENCIA Y REPRESENTACIÓN LEGAL"),
 *     @OA\Property(property="settings", type="object", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 14:30:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 14:30:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="name", type="string", example="Subtipos de archivos"),
 *         @OA\Property(property="description", type="string", example="Clasificación específica de archivos según su contenido y función."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(
 *         property="file_type",
 *         nullable=true,
 *         description="Tipo de archivo al que pertenece (con `pivot`). No viene en la respuesta de `all`.",
 *         allOf={@OA\Schema(ref="#/components/schemas/Subitem")},
 *         example={"id": 470, "item_id": 1, "name": "DOCUMENTOS LEGALES", "description": "DOCUMENTOS LEGALES DE PROVEEDORES Y CLIENTES", "settings": {}, "created_at": "2026-05-14T14:25:00.000000Z", "updated_at": "2026-05-14T14:25:00.000000Z", "deleted_at": null, "pivot": {"model_type": "App\Models\FileSubtype", "model_id": 471, "subitem_id": 470}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="FileSubtypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="file_subtype", ref="#/components/schemas/FileSubtype")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="FileSubtypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="file_subtypes", type="array", @OA\Items(ref="#/components/schemas/FileSubtype")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="FileSubtypeRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los subtipos de archivo.", example="CÁMARA DE COMERCIO"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="CERTIFICADO DE EXISTENCIA Y REPRESENTACIÓN LEGAL")
 * )
 */
class FileSubtypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/file_types/file_subtypes/all/{file_type_id}",
     *     tags={"Typification › File Subtypes"},
     *     summary="Listar los subtipos de un tipo de archivo",
     *     description="Solo devuelve subtipos vinculados a ese tipo de archivo. En esta lista no vienen `item_id` ni `file_type`. Un tipo de archivo inexistente devuelve una lista vacía.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="file_type_id",
     *         in="path",
     *         required=true,
     *         description="Identificador del tipo de archivo.",
     *         @OA\Schema(type="integer", example=470)
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
     *         description="Lista de subtipos de archivo cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileSubtypeListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"},
     *                 "errors": {"per_page": {"Debe ser un valor numérico."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(FileSubtypeAllRequest $request, $file_type_id)
    {
        try {
            $file_subtypes = FileSubtype::with(['item', 'file_type'])
                ->whereHas('file_type', fn ($r) => $r->where('id', $file_type_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $file_subtypes = $file_subtypes->paginate($request->integer('per_page', $file_subtypes->count()));

            return $this->successResponse(
                new FileSubtypeCollection($file_subtypes),
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
     *     path="/typification/file_types/file_subtypes/find/{id}",
     *     tags={"Typification › File Subtypes"},
     *     summary="Obtener un subtipo de archivo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subtipo de archivo cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileSubtypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(FileSubtypeFindRequest $request, $id)
    {
        try {
            $file_subtype = FileSubtype::with(['file_type'])->findOrFail($id);

            return $this->successResponse(
                new FileSubtypeResource($file_subtype),
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
     *     path="/typification/file_types/file_subtypes/store",
     *     tags={"Typification › File Subtypes"},
     *     summary="Crear un subtipo de archivo",
     *     description="Lo vincula al tipo de archivo indicado.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"file_type_id", "name", "description"},
     *             allOf={
     *                 @OA\Schema(@OA\Property(property="file_type_id", type="integer", description="Tipo de archivo al que pertenece.", example=470)),
     *                 @OA\Schema(ref="#/components/schemas/FileSubtypeRequest")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subtipo de archivo creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileSubtypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"file_type_id": "Identificador del tipo de archivo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"file_type_id": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(FileSubtypeStoreRequest $request)
    {
        try {
            $file_subtype = new FileSubtype;
            $file_subtype->name = $request->input('name');
            $file_subtype->description = $request->input('description');
            $file_subtype->save();

            $file_subtype->auditSync('file_type', $request->integer('file_type_id'));

            return $this->successResponse(
                new FileSubtypeResource($file_subtype),
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
     *     path="/typification/file_types/file_subtypes/update/{id}",
     *     tags={"Typification › File Subtypes"},
     *     summary="Editar un subtipo de archivo",
     *     description="Solo cambia nombre y descripción: no cambia el tipo de archivo.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/FileSubtypeRequest",
     *             example={"name": "PÓLIZA DE CUMPLIMIENTO", "description": "PÓLIZA QUE GARANTIZA EL CUMPLIMIENTO DEL CONTRATO"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subtipo de archivo editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileSubtypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o subtipo de archivo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del subtipo de arhivo", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"name": {"Ya está registrado."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(FileSubtypeUpdateRequest $request, $id)
    {
        try {
            $file_subtype = FileSubtype::findOrFail($id);
            $file_subtype->name = $request->input('name');
            $file_subtype->description = $request->input('description');
            $file_subtype->save();

            return $this->successResponse(
                new FileSubtypeResource($file_subtype),
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
     *     path="/typification/file_types/file_subtypes/delete/{id}",
     *     tags={"Typification › File Subtypes"},
     *     summary="Desactivar un subtipo de archivo",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subtipo de archivo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/FileSubtypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(FileSubtypeDeleteRequest $request, $id)
    {
        try {
            $file_subtype = FileSubtype::findOrFail($id);
            $file_subtype->delete();

            return $this->successResponse(
                new FileSubtypeResource($file_subtype),
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
     *     path="/typification/file_types/file_subtypes/restore/{id}",
     *     tags={"Typification › File Subtypes"},
     *     summary="Restaurar un subtipo de archivo desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Subtipo de archivo restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileSubtypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(FileSubtypeRestoreRequest $request, $id)
    {
        try {
            $file_subtype = FileSubtype::withTrashed()->findOrFail($id);
            $file_subtype->restore();

            return $this->successResponse(
                new FileSubtypeResource($file_subtype),
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
