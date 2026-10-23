<?php

namespace App\Http\Controllers;

use App\Http\Requests\Typification\SupplierType\Supplier\SupplierAllRequest;
use App\Http\Requests\Typification\SupplierType\Supplier\SupplierDeleteRequest;
use App\Http\Requests\Typification\SupplierType\Supplier\SupplierFindRequest;
use App\Http\Requests\Typification\SupplierType\Supplier\SupplierRestoreRequest;
use App\Http\Requests\Typification\SupplierType\Supplier\SupplierStoreRequest;
use App\Http\Requests\Typification\SupplierType\Supplier\SupplierUpdateRequest;
use App\Http\Resources\Typification\SupplierType\Supplier\SupplierCollection;
use App\Http\Resources\Typification\SupplierType\Supplier\SupplierResource;
use App\Models\FileSubtype;
use App\Models\Person;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Schema(
 *     schema="SupplierSettings",
 *     type="object",
 *     required={"code"},
 *     description="Configuración del proveedor. Se guarda tal cual llega (reemplaza la anterior).",
 *     @OA\Property(property="code", type="string", pattern="^[0-9]{2,5}$", description="Código numérico de 2 a 5 dígitos.", example="1045")
 * )
 *
 * @OA\Schema(
 *     schema="SupplierBankAccount",
 *     type="object",
 *     description="Cuenta bancaria del proveedor (tabla `bank_accounts`, polimórfica).",
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="model_type", type="string", example="App\Models\Supplier"),
 *     @OA\Property(property="model_id", type="integer", example=486),
 *     @OA\Property(property="bank_id", type="integer", example=452),
 *     @OA\Property(property="account_type_id", type="integer", example=3),
 *     @OA\Property(property="account_number", type="string", example="4508123967"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T20:10:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T20:10:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="bank_certificate", nullable=true, description="Certificado bancario (PDF). Hoy se guarda con tipo 454 ARCHIVOS PROVEEDORES y subtipo 455 CERTIFICADO BANCARIO.", allOf={@OA\Schema(ref="#/components/schemas/File")})
 * )
 *
 * @OA\Schema(
 *     schema="SupplierPerson",
 *     description="Persona de contacto del proveedor (sin `photo`), con el pivote de `model_has_subitems` y, en `find`, su tipo de documento.",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Person"),
 *         @OA\Schema(
 *             @OA\Property(property="pivot", type="object", @OA\Property(property="model_type", type="string", example="App\Models\Person"), @OA\Property(property="subitem_id", type="integer", example=486), @OA\Property(property="model_id", type="integer", example=57)),
 *             @OA\Property(property="document_type", ref="#/components/schemas/Subitem")
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="Supplier",
 *     type="object",
 *     description="Proveedor como lo entrega `SupplierCollection` (all): con sus tipos de proveedor en `supplier_type` (lista).",
 *     @OA\Property(property="id", type="integer", example=486),
 *     @OA\Property(property="item_id", type="integer", example=37),
 *     @OA\Property(property="name", type="string", example="CONFECCIONES LA ESTRELLA S.A.S."),
 *     @OA\Property(property="description", type="string", example="TALLER SATÉLITE DE CONFECCIÓN DE JEAN"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 15:10:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 15:10:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="settings", ref="#/components/schemas/SupplierSettings"),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=37),
 *         @OA\Property(property="name", type="string", example="Proveedores"),
 *         @OA\Property(property="description", type="string", example="Listado de proveedores.")
 *     ),
 *     @OA\Property(property="supplier_type", type="array", @OA\Items(ref="#/components/schemas/Subitem"))
 * )
 *
 * @OA\Schema(
 *     schema="SupplierDetail",
 *     type="object",
 *     description="Proveedor como lo entrega `SupplierResource` (find, store, update, delete, restore). `person` y `bank_account` son listas (vacías si el tipo de proveedor no las usa). En `find` vienen con tipo de documento y certificado bancario.",
 *     @OA\Property(property="id", type="integer", example=486),
 *     @OA\Property(property="item_id", type="integer", example=37),
 *     @OA\Property(property="name", type="string", example="CONFECCIONES LA ESTRELLA S.A.S."),
 *     @OA\Property(property="description", type="string", example="TALLER SATÉLITE DE CONFECCIÓN DE JEAN"),
 *     @OA\Property(property="settings", ref="#/components/schemas/SupplierSettings"),
 *     @OA\Property(property="created_at", type="string", example="2026-05-14 15:10:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-05-14 15:10:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="item", type="object", additionalProperties=true),
 *     @OA\Property(property="supplier_type", nullable=true, description="Su tipo de proveedor (el primero).", allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(property="person", type="array", @OA\Items(ref="#/components/schemas/SupplierPerson")),
 *     @OA\Property(property="bank_account", type="array", @OA\Items(ref="#/components/schemas/SupplierBankAccount"))
 * )
 *
 * @OA\Schema(
 *     schema="SupplierResponse",
 *     type="object",
 *     @OA\Property(property="data", type="object", @OA\Property(property="supplier", ref="#/components/schemas/SupplierDetail")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SupplierListResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="suppliers", type="array", @OA\Items(ref="#/components/schemas/Supplier")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="SupplierRequest",
 *     type="object",
 *     required={"supplier_type_id", "name", "description", "settings"},
 *     description="Se envía como `multipart/form-data` con claves anidadas (`settings[code]`, `person[names]`, `account_bank[bank_certificate][file]`…). `person` se exige si el tipo de proveedor tiene `has_person`; `account_bank`, si tiene `has_account_bank`.",
 *     @OA\Property(property="supplier_type_id", type="integer", description="Tipo de proveedor. Decide qué bloques se piden. Al editar no cambia el tipo asociado.", example=423),
 *     @OA\Property(property="name", type="string", minLength=4, maxLength=50, description="Nombre en MAYÚSCULAS. Único entre los proveedores.", example="CONFECCIONES LA ESTRELLA S.A.S."),
 *     @OA\Property(property="description", type="string", maxLength=255, description="Descripción en MAYÚSCULAS.", example="TALLER SATÉLITE DE CONFECCIÓN DE JEAN"),
 *     @OA\Property(property="settings", ref="#/components/schemas/SupplierSettings"),
 *     @OA\Property(
 *         property="person",
 *         type="object",
 *         description="Persona de contacto. Mismas reglas que al crear una persona: documento numérico de 5 a 14 dígitos y único, nombres solo letras, fecha Y-m-d anterior a hoy, teléfono de 10 dígitos. Al editar, `person.id` permite conservar el mismo documento.",
 *         @OA\Property(property="id", type="integer", description="Solo al editar.", example=57),
 *         @OA\Property(property="document_type_id", type="integer", example=422),
 *         @OA\Property(property="document", type="string", example="1017245896"),
 *         @OA\Property(property="names", type="string", example="ANDRÉS FELIPE"),
 *         @OA\Property(property="last_names", type="string", example="CARDONA MEJÍA"),
 *         @OA\Property(property="gender_id", type="integer", example=249),
 *         @OA\Property(property="birth_date", type="string", format="date", example="1988-03-22"),
 *         @OA\Property(property="blood_type_id", type="integer", example=318),
 *         @OA\Property(property="address", type="string", maxLength=255, example="CARRERA 52 # 30-15, ITAGÜÍ"),
 *         @OA\Property(property="phone", type="string", example="3127894561")
 *     ),
 *     @OA\Property(
 *         property="account_bank",
 *         type="object",
 *         description="Cuenta bancaria.",
 *         @OA\Property(property="bank_id", type="integer", example=452),
 *         @OA\Property(property="account_type_id", type="integer", example=3),
 *         @OA\Property(property="account_number", type="string", description="10 dígitos.", example="4508123967"),
 *         @OA\Property(
 *             property="bank_certificate",
 *             type="object",
 *             description="Certificado bancario. Obligatorio al crear.",
 *             @OA\Property(property="file", type="string", format="binary", description="PDF de máximo 10 MB."),
 *             @OA\Property(property="file_subtype_id", type="integer", description="Subtipo del archivo; el tipo se toma de él. No se valida. El frontend envía 455 (CERTIFICADO BANCARIO).", example=455)
 *         ),
 *         @OA\Property(
 *             property="new_bank_certificate",
 *             type="object",
 *             description="Solo al editar: certificado nuevo que se agrega a la cuenta. No se valida (ver pendientes).",
 *             @OA\Property(property="file", type="string", format="binary"),
 *             @OA\Property(property="file_subtype_id", type="integer", example=455)
 *         )
 *     )
 * )
 */
class SupplierController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/typification/supplier_types/suppliers/all/{supplier_type_id}",
     *     tags={"Typification › Suppliers"},
     *     summary="Listar los proveedores de un tipo de proveedor",
     *     description="Cada proveedor trae `item` y sus tipos de proveedor; no trae persona ni cuenta bancaria.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier_type_id",
     *         in="path",
     *         required=true,
     *         description="Tipo de proveedor. No se valida: un id que no existe devuelve la lista vacía.",
     *         @OA\Schema(type="integer", example=423)
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
     *         description="Lista de proveedores cargada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierListResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos"}, "errors": {"dir": {"Valor inválido. Permitidos: asc, desc."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(SupplierAllRequest $request, $supplier_type_id)
    {
        try {
            $suppliers = Supplier::with(['supplier_type'])
                ->whereHas('supplier_type', fn ($r) => $r->where('id', $supplier_type_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $suppliers = $suppliers->paginate($request->integer('per_page', $suppliers->count()));

            return $this->successResponse(
                new SupplierCollection($suppliers),
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
     *     path="/typification/supplier_types/suppliers/find/{id}",
     *     tags={"Typification › Suppliers"},
     *     summary="Obtener un proveedor",
     *     description="Trae el proveedor con su tipo, su persona de contacto (con tipo de documento y tipo de persona) y su cuenta bancaria (con el certificado).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor cargado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(SupplierFindRequest $request, $id)
    {
        try {
            $supplier = Supplier::with(['supplier_type', 'person' => ['document_type' => ['person_type']], 'bank_account' => ['bank_certificate']])
                ->findOrFail($id);

            return $this->successResponse(
                new SupplierResource($supplier),
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
     *     path="/typification/supplier_types/suppliers/store",
     *     tags={"Typification › Suppliers"},
     *     summary="Crear un proveedor",
     *     description="Crea el proveedor, lo asocia a su tipo y, según el tipo, crea su persona de contacto y su cuenta bancaria con el certificado (se guarda en `storage/bank_account`). No usa transacción: si falla un paso intermedio, lo anterior queda guardado.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(ref="#/components/schemas/SupplierRequest"))
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"settings.code": {"Debe tener entre 2 y 5 dígitos."}, "account_bank.bank_certificate.file": {"Es obligatorio."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(SupplierStoreRequest $request)
    {
        try {
            $supplier_type = SupplierType::find(
                $request->input('supplier_type_id')
            );

            $supplier = new Supplier;
            $supplier->name = $request->input('name');
            $supplier->description = $request->input('description');
            $supplier->settings = $request->input('settings');

            $supplier->save();

            $supplier->auditSync('supplier_type', $request->integer('supplier_type_id'));

            if ($supplier_type->settings?->has_person) {

                $person_data = $request->input('person');

                $person = new Person;
                $person->document_type_id = $person_data['document_type_id'];
                $person->document = $person_data['document'];
                $person->names = $person_data['names'];
                $person->last_names = $person_data['last_names'];
                $person->gender_id = $person_data['gender_id'];
                $person->birth_date = $person_data['birth_date'];
                $person->blood_type_id = $person_data['blood_type_id'];
                $person->address = $person_data['address'];
                $person->phone = $person_data['phone'];

                $supplier->person()->save($person);
            }

            if ($supplier_type->settings?->has_account_bank) {

                $account_bank_data = $request->input('account_bank');
                $file_subtype = FileSubtype::with('file_type')
                    ->find($request->integer(
                        'account_bank.bank_certificate.file_subtype_id'
                    ));

                $bank_account = $supplier->bank_account()->create([
                    'bank_id' => $account_bank_data['bank_id'],
                    'account_type_id' => $account_bank_data['account_type_id'],
                    'account_number' => $account_bank_data['account_number'],
                ]);

                if ($request->hasFile('account_bank.bank_certificate.file')) {

                    app(FileService::class)->save(
                        $bank_account,
                        $request->file('account_bank.bank_certificate.file'),
                        $file_subtype->file_type->first()->id,
                        $request->integer(
                            'account_bank.bank_certificate.file_subtype_id'
                        ),
                        'public',
                        'bank_account'
                    );
                }
            }

            return $this->successResponse(
                new SupplierResource($supplier),
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
     *     path="/typification/supplier_types/suppliers/update/{id}",
     *     tags={"Typification › Suppliers"},
     *     summary="Editar un proveedor",
     *     description="Reemplaza nombre, descripción y `settings`; actualiza (o crea, si no tenía) la persona de contacto y la cuenta bancaria según el tipo de proveedor. Un certificado nuevo va en `account_bank[new_bank_certificate]` y se agrega a la cuenta. Para enviar archivos, el frontend usa **POST** a esta misma URL con `_method=PUT`.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/SupplierRequest"),
     *                     @OA\Schema(@OA\Property(property="_method", type="string", enum={"PUT"}, description="Solo si se envía como POST."))
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor editado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o proveedor no registrado.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del tipo de proveedor", "name": "Nombre", "description": "Descripción", "settings": "Configuraciones", "settings.code": "Código"},
     *                 "errors": {"person.phone": {"The person.phone field must be 10 digits."}}
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(SupplierUpdateRequest $request, $id)
    {
        try {
            $supplier_type = SupplierType::findOrFail(
                $request->input('supplier_type_id')
            );

            $supplier = Supplier::findOrFail($id);

            $supplier->name = $request->input('name');
            $supplier->description = $request->input('description');
            $supplier->settings = $request->input('settings');

            $supplier->save();

            if ($supplier_type->settings?->has_person) {

                $person_data = $request->input('person');
                $person = $supplier->person()->first();

                if ($person) {
                    $person->update([
                        'document_type_id' => $person_data['document_type_id'],
                        'document' => $person_data['document'],
                        'names' => $person_data['names'],
                        'last_names' => $person_data['last_names'],
                        'gender_id' => $person_data['gender_id'],
                        'birth_date' => $person_data['birth_date'],
                        'blood_type_id' => $person_data['blood_type_id'],
                        'address' => $person_data['address'],
                        'phone' => $person_data['phone'],
                    ]);

                } else {
                    $supplier->person()->create([
                        'document_type_id' => $person_data['document_type_id'],
                        'document' => $person_data['document'],
                        'names' => $person_data['names'],
                        'last_names' => $person_data['last_names'],
                        'gender_id' => $person_data['gender_id'],
                        'birth_date' => $person_data['birth_date'],
                        'blood_type_id' => $person_data['blood_type_id'],
                        'address' => $person_data['address'],
                        'phone' => $person_data['phone'],
                    ]);
                }
            }

            if ($supplier_type->settings?->has_account_bank) {

                $account_bank_data = $request->input('account_bank');

                $bank_account = $supplier->bank_account()->first();

                if ($bank_account) {

                    $bank_account->update([
                        'bank_id' => $account_bank_data['bank_id'],
                        'account_type_id' => $account_bank_data['account_type_id'],
                        'account_number' => $account_bank_data['account_number'],
                    ]);

                } else {

                    $bank_account = $supplier->bank_account()->create([
                        'bank_id' => $account_bank_data['bank_id'],
                        'account_type_id' => $account_bank_data['account_type_id'],
                        'account_number' => $account_bank_data['account_number'],
                    ]);
                }

                if ($request->hasFile('account_bank.new_bank_certificate.file')) {

                    $file = $request->file(
                        'account_bank.new_bank_certificate.file'
                    );

                    $file_subtype_id = $request->integer(
                        'account_bank.new_bank_certificate.file_subtype_id'
                    );

                    $file_subtype = FileSubtype::with('file_type')
                        ->findOrFail($file_subtype_id);

                    app(FileService::class)->save(
                        $bank_account,
                        $file,
                        $file_subtype->file_type->first()->id,
                        $file_subtype_id,
                        'public',
                        'bank_account'
                    );
                }
            }

            return $this->successResponse(
                new SupplierResource($supplier->fresh()),
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
     *     path="/typification/supplier_types/suppliers/delete/{id}",
     *     tags={"Typification › Suppliers"},
     *     summary="Desactivar un proveedor",
     *     description="Desactivación lógica (soft delete): el registro se conserva y puede restaurarse. No toca su persona ni su cuenta bancaria.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(SupplierDeleteRequest $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            return $this->successResponse(
                new SupplierResource($supplier),
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
     *     path="/typification/supplier_types/suppliers/restore/{id}",
     *     tags={"Typification › Suppliers"},
     *     summary="Restaurar un proveedor desactivado",
     *     description="Solo acepta proveedores desactivados.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor restaurado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierResponse")
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(SupplierRestoreRequest $request, $id)
    {
        try {
            $supplier = Supplier::withTrashed()->findOrFail($id);
            $supplier->restore();

            return $this->successResponse(
                new SupplierResource($supplier),
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
