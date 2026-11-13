<?php

namespace App\Http\Controllers;

use App\Http\Requests\Person\PersonAllRequest;
use App\Http\Requests\Person\PersonDeleteRequest;
use App\Http\Requests\Person\PersonFindRequest;
use App\Http\Requests\Person\PersonRestoreRequest;
use App\Http\Requests\Person\PersonStoreRequest;
use App\Http\Requests\Person\PersonUpdateRequest;
use App\Http\Resources\Person\PersonCollection;
use App\Http\Resources\Person\PersonResource;
use App\Models\City;
use App\Models\Department;
use App\Models\Person;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="People",
 *     description="Endpoints para gestionar personas"
 * )
 *
 * @OA\Schema(
 *     schema="Person",
 *     type="object",
 *     description="Persona serializada por Eloquent (con su foto). Es la que viene anidada en empleados y usuarios.",
 *     @OA\Property(property="id", type="integer", example=57),
 *     @OA\Property(property="document_type_id", type="integer", example=422),
 *     @OA\Property(property="document", type="string", example="1037612458"),
 *     @OA\Property(property="names", type="string", example="VALENTINA"),
 *     @OA\Property(property="last_names", type="string", example="RESTREPO OSORIO"),
 *     @OA\Property(property="gender_id", type="integer", example=249),
 *     @OA\Property(property="birth_date", type="string", format="date", example="1995-08-14"),
 *     @OA\Property(property="blood_type_id", type="integer", example=318),
 *     @OA\Property(property="address", type="string", example="CALLE 45 SUR # 39-120, ENVIGADO"),
 *     @OA\Property(property="phone", type="string", example="3104567812"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T22:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T22:00:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="photo", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/File")})
 * )
 *
 * @OA\Schema(
 *     schema="PersonListItem",
 *     type="object",
 *     description="Persona como la entrega `PersonCollection` (all). No trae los ids de catálogos (`gender_id`, etc.) ni `document_type`. `birth_date` sale en ISO/UTC (por ejemplo `1995-08-14T05:00:00.000000Z`).",
 *     @OA\Property(property="id", type="integer", example=57),
 *     @OA\Property(property="document", type="string", example="1037612458"),
 *     @OA\Property(property="names", type="string", example="VALENTINA"),
 *     @OA\Property(property="last_names", type="string", example="RESTREPO OSORIO"),
 *     @OA\Property(property="birth_date", type="string", example="1995-08-14T05:00:00.000000Z"),
 *     @OA\Property(property="address", type="string", example="CALLE 45 SUR # 39-120, ENVIGADO"),
 *     @OA\Property(property="phone", type="string", example="3104567812"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 17:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 17:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="gender", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="photo", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/File")}),
 *     @OA\Property(property="blood_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="employee", nullable=true, type="object", additionalProperties=true, description="Empleado de la persona (`null` si no lo es).")
 * )
 *
 * @OA\Schema(
 *     schema="PersonDetail",
 *     type="object",
 *     description="Persona como la entrega `PersonResource` (find, store, update, delete, restore): `birth_date` en `Y-m-d`, con catálogos y tipo de documento (en `find`, con su tipo de persona y el empleado con su persona). No trae los ids de catálogos.",
 *     @OA\Property(property="id", type="integer", example=57),
 *     @OA\Property(property="document", type="string", example="1037612458"),
 *     @OA\Property(property="names", type="string", example="VALENTINA"),
 *     @OA\Property(property="last_names", type="string", example="RESTREPO OSORIO"),
 *     @OA\Property(property="birth_date", type="string", format="date", example="1995-08-14"),
 *     @OA\Property(property="address", type="string", example="CALLE 45 SUR # 39-120, ENVIGADO"),
 *     @OA\Property(property="phone", type="string", example="3104567812"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 17:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 17:00:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="gender", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="photo", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/File")}),
 *     @OA\Property(property="blood_type", ref="#/components/schemas/Subitem"),
 *     @OA\Property(property="document_type", description="Tipo de documento (en `find`, con `person_type`).", allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(property="employee", nullable=true, type="object", additionalProperties=true)
 * )
 *
 * @OA\Schema(
 *     schema="PersonResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="person", ref="#/components/schemas/PersonDetail")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PersonListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="people", type="array", @OA\Items(ref="#/components/schemas/PersonListItem")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="PersonRequest",
 *     type="object",
 *     required={"document_type_id", "document", "names", "last_names", "gender_id", "birth_date", "blood_type_id", "address", "phone"},
 *     description="Se envía como `multipart/form-data` para poder adjuntar la foto. Las reglas de `document` y `phone` difieren entre crear y editar (ver cada operación).",
 *     @OA\Property(property="document_type_id", type="integer", description="Tipo de documento activo.", example=422),
 *     @OA\Property(property="document", type="string", description="Único. Además debe cumplir alguna de las reglas (`settings.validations`) del tipo de documento; si no cumple ninguna, el error lista los mensajes de esas reglas.", example="1037612458"),
 *     @OA\Property(property="names", type="string", pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$", description="Solo letras y espacios.", example="VALENTINA"),
 *     @OA\Property(property="last_names", type="string", pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$", description="Solo letras y espacios.", example="RESTREPO OSORIO"),
 *     @OA\Property(property="gender_id", type="integer", description="Género activo.", example=249),
 *     @OA\Property(property="birth_date", type="string", format="date", description="Formato Y-m-d, anterior a hoy.", example="1995-08-14"),
 *     @OA\Property(property="blood_type_id", type="integer", description="Tipo de sangre activo.", example=318),
 *     @OA\Property(property="address", type="string", maxLength=255, example="CALLE 45 SUR # 39-120, ENVIGADO"),
 *     @OA\Property(property="phone", type="string", example="3104567812"),
 *     @OA\Property(property="photo", type="string", format="binary", description="Opcional. JPG o PNG de máximo 2 MB."),
 *     @OA\Property(property="photo_type_id", type="integer", description="Tipo de archivo de la foto (no se valida). Hoy: 1 = Archivos personales.", example=1),
 *     @OA\Property(property="photo_subtype_id", type="integer", description="Subtipo de archivo de la foto (no se valida). Hoy: 425 = FOTO.", example=425)
 * )
 */
class PersonController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/people/all",
     *     tags={"People"},
     *     summary="Listar las personas",
     *     description="Cada persona trae género, tipo de sangre, foto y su empleado (si lo es).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Cada palabra se busca en documento, nombres, apellidos, fecha de nacimiento, teléfono, género y tipo de sangre.",
     *         @OA\Schema(type="string", example="RESTREPO")
     *     ),
     *     @OA\Parameter(
     *         name="with_employee",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: solo personas que son empleados (incluye empleados desactivados). `false`/`0`: solo las que no lo son. Si no se envía, todas.",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *         @OA\Schema(type="string", enum={"id", "document", "names", "last_names", "gender_id", "birth_date", "blood_type_id", "address", "phone", "created_at", "updated_at", "deleted_at"}, example="last_names")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de personas cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(PersonAllRequest $request)
    {
        try {
            $people = Person::with(['gender', 'blood_type', 'employee', 'photo', 'phone_country'])
                ->when($request->boolean('with_employee'), function ($query) {
                    return $query;
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->has('with_employee'), function ($query) use ($request) {
                    return $request->boolean('with_employee')
                        ? $query->whereHas('employee', fn ($q) => $q->withTrashed())
                        : $query->whereDoesntHave('employee', fn ($q) => $q->withTrashed());
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $people = $people->paginate($request->integer('per_page', $people->count()));

            return $this->successResponse(
                new PersonCollection($people),
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
     *     path="/people/find/{id}",
     *     tags={"People"},
     *     summary="Obtener una persona",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Persona cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(PersonFindRequest $request, $id)
    {
        try {
            $person = Person::with(['gender', 'blood_type', 'employee' => ['person'], 'photo', 'document_type' => ['person_type'], 'phone_country'])
                ->findOrFail($id);
            $person->loadMorph('location', [City::class => ['department' => ['country']], Department::class => ['country']]);

            return $this->successResponse(
                new PersonResource($person),
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
     *     path="/people/store",
     *     tags={"People"},
     *     summary="Crear una persona",
     *     description="Al crear: `document` numérico de 5 a 14 dígitos y `phone` de exactamente 10 dígitos. Si se adjunta `photo`, se guarda en `storage/person`.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/PersonRequest"))
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Persona creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"document": "Número de documento", "names": "Nombres", "last_names": "Apellidos", "gender_id": "Genero", "birth_date": "Fecha de nacimiento", "document_type_id": "Tipo de Documento", "blood_type_id": "Tipo de sangre", "address": "Dirección", "phone": "Número de teléfono", "photo": "Foto"},
     *                 "errors": {"document": {"Debe de tener 10 digitos unicamente"}, "phone": {"Debe tener 10 números"}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(PersonStoreRequest $request)
    {
        try {
            $person = new Person;
            $person->document_type_id = $request->integer('document_type_id');
            $person->document = $request->input('document');
            $person->names = $request->input('names');
            $person->last_names = $request->input('last_names');
            $person->gender_id = $request->integer('gender_id');
            $person->birth_date = $request->input('birth_date');
            $person->blood_type_id = $request->integer('blood_type_id');
            $person->email = $request->input('email');
            $person->location_type = $request->input('location_type');
            $person->location_id = $request->integer('location_id');
            $person->address = $request->input('address');
            $person->neighborhood = $request->input('neighborhood');
            $person->phone_country_id = $request->integer('phone_country_id');
            $person->phone = $request->input('phone');
            $person->save();

            if ($request->hasFile('photo')) {
                app(FileService::class)->save(
                    $person,
                    $request->file('photo'),
                    $request->integer('photo_type_id'),
                    $request->integer('photo_subtype_id'),
                    'public',
                    'person'
                );
            }

            return $this->successResponse(
                new PersonResource($person),
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
     *     path="/people/update/{id}",
     *     tags={"People"},
     *     summary="Editar una persona",
     *     description="Para adjuntar foto (multipart) el frontend envía **POST** a esta misma URL con el campo `_method=PUT`, porque PHP no lee archivos en un PUT multipart. Al editar, las reglas son más laxas que al crear: `document` es texto de 4 a 14 caracteres y `phone` no tiene formato (ver pendientes).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/PersonRequest"),
     *                     @OA\Schema(@OA\Property(property="_method", type="string", enum={"PUT"}, description="Solo si se envía como POST."))
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Persona editada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o persona no registrada.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador de la persona", "document": "Número de documento", "names": "Nombres", "last_names": "Apellidos", "gender_id": "Genero", "birth_date": "Fecha de nacimiento", "blood_type_id": "Tipo de sangre", "address": "Dirección", "phone": "Número de teléfono", "photo": "Foto"},
     *                 "errors": {"names": {"Formato inválido:\n- Solo letras."}, "photo": {"Extensión inválida. Permitidas jpg, jpeg, png."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(PersonUpdateRequest $request, $id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->document_type_id = $request->input('document_type_id');
            $person->document = $request->input('document');
            $person->names = $request->input('names');
            $person->last_names = $request->input('last_names');
            $person->gender_id = $request->integer('gender_id');
            $person->birth_date = $request->input('birth_date');
            $person->blood_type_id = $request->integer('blood_type_id');
            $person->email = $request->input('email');
            $person->location_type = $request->input('location_type');
            $person->location_id = $request->integer('location_id');
            $person->address = $request->input('address');
            $person->neighborhood = $request->input('neighborhood');
            $person->phone_country_id = $request->integer('phone_country_id');
            $person->phone = $request->input('phone');
            $person->save();

            if ($request->hasFile('photo')) {
                app(FileService::class)->save(
                    $person,
                    $request->file('photo'),
                    $request->integer('photo_type_id'),
                    $request->integer('photo_subtype_id'),
                    'public',
                    'person'
                );
            }

            return $this->successResponse(
                new PersonResource($person),
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
     *     path="/people/delete/{id}",
     *     tags={"People"},
     *     summary="Desactivar una persona",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse. No desactiva su empleado.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Persona desactivada correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/PersonResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(PersonDeleteRequest $request, $id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->delete();

            return $this->successResponse(
                new PersonResource($person),
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
     *     path="/people/restore/{id}",
     *     tags={"People"},
     *     summary="Restaurar una persona desactivada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Persona restaurada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/PersonResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(PersonRestoreRequest $request, $id)
    {
        try {
            $person = Person::withTrashed()->findOrFail($id);
            $person->restore();

            return $this->successResponse(
                new PersonResource($person),
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
