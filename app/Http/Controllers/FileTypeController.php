<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\FileType\FileTypeAllRequest;
use App\Http\Requests\Typification\FileType\FileTypeDeleteRequest;
use App\Http\Requests\Typification\FileType\FileTypeFindRequest;
use App\Http\Requests\Typification\FileType\FileTypeRestoreRequest;
use App\Http\Requests\Typification\FileType\FileTypeStoreRequest;
use App\Http\Requests\Typification\FileType\FileTypeUpdateRequest;
use App\Http\Resources\Typification\FileType\FileTypeCollection;
use App\Http\Resources\Typification\FileType\FileTypeResource;
use App\Models\FileType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="FileTypeFileSubtype",
 *     description="Subtipo del tipo de archivo, con el pivote de `model_has_subitems`.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Subitem"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\Models\FileSubtype"),
 *                 @OA\Property(property="subitem_id", type="integer", description="Id del tipo de archivo.", example=470),
 *                 @OA\Property(property="model_id", type="integer", description="Id del subtipo.", example=471)
 *             )
 *         )
 *     },
 *     example={"id": 471, "item_id": 2, "name": "CÁMARA DE COMERCIO", "description": "CERTIFICADO DE EXISTENCIA Y REPRESENTACIÓN LEGAL", "settings": {}, "created_at": "2026-05-14T14:30:00.000000Z", "updated_at": "2026-05-14T14:30:00.000000Z", "deleted_at": null, "pivot": {"model_type": "App\Models\FileSubtype", "subitem_id": 470, "model_id": 471}}
 * )
 *
 * @OA\Schema(
 *     schema="FileType",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=470),
 *     @OA\Property(property="item_id", type="integer", description="No viene en la respuesta de `all`.", example=1),
 *     @OA\Property(property="name", type="string", example="DOCUMENTOS LEGALES"),
 *     @OA\Property(property="description", type="string", example="DOCUMENTOS LEGALES DE PROVEEDORES Y CLIENTES"),
 *     @OA\Property(property="settings", type="object", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 14:25:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 14:25:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Tipos de archivos"),
 *         @OA\Property(property="description", type="string", example="Clasificación estandarizada de archivos según su naturaleza, uso y propósito dentro de procesos administrativos, legales o documentales."),
 *         @OA\Property(property="settings", type="object", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(property="file_subtypes", type="array", description="Subtipos del tipo de archivo. **Solo vienen en `all`**: el detalle no los devuelve.", @OA\Items(ref="#/components/schemas/FileTypeFileSubtype"))
 * )
 *
 * @OA\Schema(
 *     schema="FileTypeResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="file_type", ref="#/components/schemas/FileType")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="FileTypeListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="file_types", type="array", @OA\Items(ref="#/components/schemas/FileType")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="FileTypeRequest",
 *     type="object",
 *     required={"name", "description"},
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los tipos de archivo.", example="DOCUMENTOS LEGALES"),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="DOCUMENTOS LEGALES DE PROVEEDORES Y CLIENTES")
 * )
 */
class FileTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/file_types/all",
     *     tags={"Typification › File Types"},
     *     summary="Listar los tipos de archivo",
     *     description="Cada tipo de archivo trae sus subtipos. En esta lista no viene `item_id`.",
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
     *         description="Lista de tipos de archivo cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileTypeListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos", "in_technical_sheet": "Aplica a Ficha Técnica"},
     *                 "errors": {"column": {"Valor inválido. Permitidos: id, name, description, created_at, updated_at, deleted_at."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(FileTypeAllRequest $request)
    {
        try {
            $file_types = FileType::with(['file_subtypes'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $file_types = $file_types->paginate($request->integer('per_page', $file_types->count()));

            return $this->successResponse(
                new FileTypeCollection($file_types),
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
     *     path="/typification/file_types/find/{id}",
     *     tags={"Typification › File Types"},
     *     summary="Obtener un tipo de archivo",
     *     description="No incluye `file_subtypes` (el Resource no los devuelve, aunque se cargan).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de archivo cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(FileTypeFindRequest $request, $id)
    {
        try {
            $file_type = FileType::with(['file_subtypes'])
                ->findOrFail($id);

            return $this->successResponse(
                new FileTypeResource($file_type),
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
     *     path="/typification/file_types/store",
     *     tags={"Typification › File Types"},
     *     summary="Crear un tipo de archivo",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/FileTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tipo de archivo creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileTypeResponse")
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
    public function store(FileTypeStoreRequest $request)
    {
        try {
            $file_type = new FileType;
            $file_type->name = $request->input('name');
            $file_type->description = $request->input('description');

            $file_type->save();

            return $this->successResponse(
                new FileTypeResource($file_type),
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
     *     path="/typification/file_types/update/{id}",
     *     tags={"Typification › File Types"},
     *     summary="Editar un tipo de archivo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             ref="#/components/schemas/FileTypeRequest",
     *             example={"name": "CONTRATOS", "description": "CONTRATOS Y ACUERDOS COMERCIALES"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de archivo editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileTypeResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o tipo de archivo no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de tela", "name": "Nombre", "description": "Descripción"},
     *                 "errors": {"description": {"Debe de ingresar mayúsculas."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(FileTypeUpdateRequest $request, $id)
    {
        try {
            $file_type = FileType::findOrFail($id);
            $file_type->name = $request->input('name');
            $file_type->description = $request->input('description');
            $file_type->save();

            return $this->successResponse(
                new FileTypeResource($file_type),
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
     *     path="/typification/file_types/delete/{id}",
     *     tags={"Typification › File Types"},
     *     summary="Desactivar un tipo de archivo",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de archivo desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/FileTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(FileTypeDeleteRequest $request, $id)
    {
        try {
            $file_type = FileType::findOrFail($id);
            $file_type->delete();

            return $this->successResponse(
                new FileTypeResource($file_type),
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
     *     path="/typification/file_types/restore/{id}",
     *     tags={"Typification › File Types"},
     *     summary="Restaurar un tipo de archivo desactivado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de archivo restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/FileTypeResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(FileTypeRestoreRequest $request, $id)
    {
        try {
            $file_type = FileType::withTrashed()->findOrFail($id);
            $file_type->restore();

            return $this->successResponse(
                new FileTypeResource($file_type),
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
