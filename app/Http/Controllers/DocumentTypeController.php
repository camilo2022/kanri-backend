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
 * @OA\Tag(
 *     name="Document Types",
 *     description="Endpoints para gestionar tipos de documentos"
 * )
 *
 * @OA\PathItem(
 *     path="/document_types",
 *     description="Rutas de gestión de tipos de documentos"
 * )
 *
 * @OA\Schema(
 *     schema="DocumentType",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="item_id", type="integer", example=10),
 *     @OA\Property(property="name", type="string", example="No Aplica"),
 *     @OA\Property(property="description", type="string", example="No Aplica"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=34),
 *         @OA\Property(property="name", type="string", example="Subtipos de archivos"),
 *         @OA\Property(property="description", type="string", example="Listado de tipos de documentos."),
 *         @OA\Property(property="settings", type="string", example="{}"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 * )
 */
class DocumentTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/document_types/all",
     *     tags={"Document Types"},
     *     summary="Listar Tipos de Documentos",
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
     *         description="Lista de tipos de documentos cargada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document_types",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/DocumentType")
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
     *     path="/document_types/find/{id}",
     *     tags={"Document Types"},
     *     summary="Obtener un tipo de documento específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de documento",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de Documento cargado con éxito",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document_types",
     *                     ref="#/components/schemas/DocumentType"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del tipo de documento no registrado.",
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
     *     path="/document_types/store",
     *     tags={"Document Types"},
     *     summary="Crear un tipo de documento",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Prueba",
     *                 description="Nombre del tipo de documento"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="Prueba",
     *                 description="Descripción del tipo de documento"
     *              )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de Documento creado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document_type",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="NAME_FILE_SUBTYPE"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_FILE_SUBTYPE"),
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
     *                 @OA\Property(property="name", type="string", example="Nombre del tipo de documento"),
     *                 @OA\Property(property="description", type="string", example="Descripción del tipo de documento")
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
     *     path="/document_types/update/{id}",
     *     tags={"Document Types"},
     *     summary="Editar un tipo de documento específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de documento",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Personal",
     *                 description="Nombre del tipo de documento"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="Subtipo Personal",
     *                 description="Descripción del tipo de documento"
     *              )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de Documento editado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document_types",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_FILE_SUBTYPE"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_FILE_SUBTYPE"),
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
     *                 @OA\Property(property="name", type="string", example="Nombre del tipo de documento"),
     *                 @OA\Property(property="description", type="string", example="Descripción del tipo de documento")
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
     *     path="/document_types/delete/{id}",
     *     tags={"Document Types"},
     *     summary="Desactivar un tipo de documento específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de documento",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de Documento desactivado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document_types",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_FILE_SUBTYPE"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_FILE_SUBTYPE"),
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
     *                 @OA\Property(property="id", type="string", example="Identificador del tipo de documento")
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
     *     path="/document_types/restore/{id}",
     *     tags={"Document Types"},
     *     summary="Activar un tipo de documento específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de documento",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tipo de Documento activado correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document_types",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_FILE_SUBTYPE"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_FILE_SUBTYPE"),
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
     *                 @OA\Property(property="id", type="string", example="Identificador del tipo de documento")
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
     *     path="/document_types/settings/{id}",
     *     tags={"DocumentTypes"},
     *     summary="Modificar validaciones de tipo de documento específico",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del tipo de documento",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Estructura completa de settings con sus validaciones",
     *
     *         @OA\JsonContent(
     *             required={"settings"},
     *
     *             @OA\Property(
     *                 property="settings",
     *                 type="object",
     *                 @OA\Property(
     *                     property="validations",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(
     *                             property="regex",
     *                             type="string",
     *                             example="/^5[a-zA-Z0-9]*$/"
     *                         ),
     *                         @OA\Property(
     *                             property="message",
     *                             type="string",
     *                             example="Prueba"
     *                         ),
     *                         @OA\Property(
     *                             property="created_at",
     *                             type="string",
     *                             format="date-time",
     *                             example="2026-04-14T15:48:22.425Z",
     *                             nullable=true
     *                         ),
     *                         @OA\Property(
     *                             property="updated_at",
     *                             type="string",
     *                             format="date-time",
     *                             example=null,
     *                             nullable=true
     *                         ),
     *                         @OA\Property(
     *                             property="deleted_at",
     *                             type="string",
     *                             format="date-time",
     *                             example=null,
     *                             nullable=true
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Validacion agregada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document_type",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="No Aplica"),
     *                     @OA\Property(property="description", type="string", example="No Aplica"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(
     *                         property="settings",
     *                         type="object",
     *                         @OA\Property(
     *                             property="validations",
     *                             type="array",
     *
     *                             @OA\Items(
     *                                 type="object",
     *
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="regex", type="string", example="/^[A-Za-zÁÉÍÓÚáéíóúñÑ ]+$/"),
     *                                 @OA\Property(property="message", type="string", example="El nombre solo puede contener letras"),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-07T08:00:00Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2026-04-08T19:02:45.879Z"),
     *                                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                             )
     *                         )
     *                     )
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
     *                 @OA\Property(property="id", type="string", example="Identificador del tipo de documento")
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
