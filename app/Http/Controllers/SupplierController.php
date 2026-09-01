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
 * @OA\Tag(
 *     name="Suppliers",
 *     description="Endpoints para gestionar proveedores"
 * )
 *
 * @OA\PathItem(
 *     path="/Suppliers",
 *     description="Rutas de gestión de proveedores"
 * )
 *
 * @OA\Schema(
 *     schema="Supplier",
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
 *         @OA\Property(property="id", type="integer", example=11),
 *         @OA\Property(property="name", type="string", example="Ubicaciones"),
 *         @OA\Property(property="description", type="string", example="Listado de proveedores."),
 *         @OA\Property(property="settings", type="string", example="{}"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *     ),
 *     @OA\Property(
 *         property="process",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="item_id", type="integer", example=10),
 *             @OA\Property(property="name", type="string", example="No Aplica"),
 *             @OA\Property(property="description", type="string", example="No Aplica"),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\\Models\\Supplier"),
 *                 @OA\Property(property="model_id", type="integer", example=1),
 *                 @OA\Property(property="subitem_id", type="integer", example=1)
 *             )
 *         )
 *     )
 * )
 */
class SupplierController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/suppliers/all",
     *     tags={"Suppliers"},
     *     summary="Listar proveedores",
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
     *         description="Lista de proveedores cargada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="suppliers",
     *                     type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Supplier")
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
     *     path="/suppliers/find/{id}",
     *     tags={"Suppliers"},
     *     summary="Obtener una ubicación específica",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la ubicación",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ubicación cargada con éxito",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     ref="#/components/schemas/Supplier"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de la ubicación no registrado.",
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
     *     path="/suppliers/store",
     *     tags={"Suppliers"},
     *     summary="Crear una ubicación",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","description","process_id"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Example",
     *                 description="Nombre de la ubicación"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="Example",
     *                 description="Descripción de la ubicación"
     *             ),
     *             @OA\Property(
     *                 property="process_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del proceso a la que pertenece la ubicación"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ubicación creada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="NAME_LOCATION"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_LOCATION"),
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
     *                 @OA\Property(property="name", type="string", example="Nombre de la ubicación"),
     *                 @OA\Property(property="description", type="string", example="Descripción de la ubicación")
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

                logger()->debug('Cuenta bancaria creada', [
                    'id' => $bank_account->id,
                    'model_id' => $bank_account->model_id,
                    'model_type' => $bank_account->model_type,
                    'bank_id' => $bank_account->bank_id,
                    'account_type_id' => $bank_account->account_type_id,
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
     *     path="/suppliers/update/{id}",
     *     tags={"Suppliers"},
     *     summary="Editar una ubicación específica",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la ubicación",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","description","process_id"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Example",
     *                 description="Nombre de la ubicación"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 example="Example",
     *                 description="Descripción de la ubicación"
     *             ),
     *             @OA\Property(
     *               property="process_id",
     *               type="integer",
     *               example=1,
     *               description="Identificador del proceso a la que pertenece la ubicación"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ubicación editada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_LOCATION"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_LOCATION"),
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
     *                 @OA\Property(property="name", type="string", example="Nombre de la ubicación"),
     *                 @OA\Property(property="description", type="string", example="Descripción de la ubicación")
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
     *     path="/suppliers/delete/{id}",
     *     tags={"Suppliers"},
     *     summary="Desactivar una ubicación específica",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la ubicación",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ubicación desactivada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_LOCATION"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_LOCATION"),
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
     *                 @OA\Property(property="id", type="string", example="Identificador de la proveedores")
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
     *     path="/suppliers/restore/{id}",
     *     tags={"Suppliers"},
     *     summary="Activar una ubicación específica",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la ubicación",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ubicación activada correctamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="item_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="NAME_LOCATION"),
     *                     @OA\Property(property="description", type="string", example="DESCRIPTION_LOCATION"),
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
     *                 @OA\Property(property="id", type="string", example="Identificador de la proveedores")
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
