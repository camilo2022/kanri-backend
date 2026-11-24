<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\EmployeeAllRequest;
use App\Http\Requests\Employee\EmployeeDeleteRequest;
use App\Http\Requests\Employee\EmployeeFindRequest;
use App\Http\Requests\Employee\EmployeeRestoreRequest;
use App\Http\Requests\Employee\EmployeeStoreRequest;
use App\Http\Requests\Employee\EmployeeUpdateRequest;
use App\Http\Resources\Employee\EmployeeCollection;
use App\Http\Resources\Employee\EmployeeResource;
use App\Models\City;
use App\Models\Department;
use App\Models\Employee;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Employees",
 *     description="Endpoints para gestionar empleados"
 * )
 *
 * @OA\Schema(
 *     schema="EmployeeUser",
 *     type="object",
 *     description="Usuario del empleado (incluye usuarios desactivados), con sus roles y permisos directos. `null` si el empleado no tiene usuario.",
 *
 *     @OA\Property(property="id", type="integer", example=15),
 *     @OA\Property(property="employee_id", type="integer", example=42),
 *     @OA\Property(property="email", type="string", example="laura.gomez@kanri.test"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-14T09:50:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-14T09:50:00.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/Role")),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/Permission"))
 * )
 *
 * @OA\Schema(
 *     schema="Employee",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="person_id", type="integer", example=57),
 *     @OA\Property(property="operation_center", type="string", nullable=true, description="Código del centro de operaciones (texto libre).", example="050"),
 *     @OA\Property(property="position_id", type="integer", example=58),
 *     @OA\Property(property="risk_manager_id", type="integer", example=94),
 *     @OA\Property(property="health_entity_id", type="integer", example=101),
 *     @OA\Property(property="pension_fund_id", type="integer", example=152),
 *     @OA\Property(property="compensation_fund_id", type="integer", example=163),
 *     @OA\Property(property="start_date", type="string", description="`Y-m-d` en el detalle; en la lista (`all`) sale en ISO/UTC, por ejemplo `2026-06-02T05:00:00.000000Z`.", example="2026-06-02"),
 *     @OA\Property(property="end_date", type="string", nullable=true, description="Mismo formato que `start_date`. `null` si sigue activo.", example=null),
 *     @OA\Property(property="created_at", type="string", example="2026-06-02 08:15:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-06-02 08:15:00"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *     @OA\Property(property="person", ref="#/components/schemas/Person"),
 *     @OA\Property(property="position", ref="#/components/schemas/Position"),
 *     @OA\Property(property="user", nullable=true, allOf={@OA\Schema(ref="#/components/schemas/EmployeeUser")}),
 *     @OA\Property(property="risk_manager", description="Administradora de riesgos laborales (ARL).", allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(property="health_entity", description="Entidad de salud (EPS).", allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(property="pension_fund", description="Fondo de pensión.", allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(property="compensation_fund", description="Caja de compensación.", allOf={@OA\Schema(ref="#/components/schemas/Subitem")}),
 *     @OA\Property(property="workstation_id", type="integer", nullable=true, description="Puesto de trabajo (id en subitems).", example=602),
 *     @OA\Property(property="workstation", nullable=true, description="Puesto de trabajo tal cual está en subitems: `item_id` dice si es proceso (24), subproceso (25) u operación (26). El frontend arma la ruta con la lista de procesos.", allOf={@OA\Schema(ref="#/components/schemas/Subitem")})
 * )
 *
 * @OA\Schema(
 *     schema="EmployeeResponse",
 *     type="object",
 *
 *     @OA\Property(property="data", type="object", @OA\Property(property="employee", ref="#/components/schemas/Employee")),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="EmployeeListResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="employees", type="array", @OA\Items(ref="#/components/schemas/Employee")),
 *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *     ),
 *     @OA\Property(property="message", type="string", example="Operación completada con éxito."),
 *     @OA\Property(property="error", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="EmployeeRequest",
 *     type="object",
 *     required={"person_id", "position_id", "risk_manager_id", "health_entity_id", "pension_fund_id", "compensation_fund_id", "start_date"},
 *
 *     @OA\Property(property="person_id", type="integer", description="Persona activa. Una persona solo puede tener un empleado.", example=57),
 *     @OA\Property(property="operation_center", type="string", nullable=true, description="Centro de operaciones. Opcional.", example="050"),
 *     @OA\Property(property="position_id", type="integer", description="Cargo activo.", example=58),
 *     @OA\Property(property="risk_manager_id", type="integer", description="Administradora de riesgos (ARL) activa.", example=94),
 *     @OA\Property(property="health_entity_id", type="integer", description="Entidad de salud (EPS) activa.", example=101),
 *     @OA\Property(property="pension_fund_id", type="integer", description="Fondo de pensión activo.", example=152),
 *     @OA\Property(property="compensation_fund_id", type="integer", description="Caja de compensación activa.", example=163),
 *     @OA\Property(property="start_date", type="string", format="date", description="Formato Y-m-d.", example="2026-06-02"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, description="Formato Y-m-d, posterior a `start_date`. Opcional.", example=null),
 *     @OA\Property(property="workstation_id", type="integer", nullable=true, description="Puesto de trabajo (opcional): id en subitems de un proceso sin subprocesos, un subproceso sin operaciones o una operación. Se guarda en `employees.workstation_id`; null lo quita.", example=602)
 * )
 */
class EmployeeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/employees/all",
     *     tags={"Employees"},
     *     summary="Listar los empleados",
     *     description="Cada empleado trae persona (con foto), cargo con área, usuario con roles y permisos, ARL, EPS, fondo de pensión y caja de compensación.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Texto a buscar. Cada palabra se busca en documento, nombres y apellidos de la persona, y en el nombre del cargo, área, ARL y EPS.",
     *
     *         @OA\Schema(type="string", example="GOMEZ")
     *     ),
     *
     *     @OA\Parameter(
     *         name="position_id",
     *         in="query",
     *         required=false,
     *         description="Solo los empleados de este cargo. No se valida.",
     *
     *         @OA\Schema(type="integer", example=58)
     *     ),
     *
     *     @OA\Parameter(
     *         name="with_user",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: solo empleados con usuario. `false`/`0`: solo sin usuario. Si no se envía, todos.",
     *
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         required=false,
     *         description="Columna para ordenar. Solo se aplica junto con `dir`.",
     *
     *         @OA\Schema(type="string", enum={"id", "person_id", "operation_center", "position_id", "risk_manager_id", "health_entity_id", "pension_fund_id", "compensation_fund_id", "start_date", "end_date", "created_at", "updated_at", "deleted_at"}, example="start_date")
     *     ),
     *
     *     @OA\Parameter(ref="#/components/parameters/Dir"),
     *     @OA\Parameter(ref="#/components/parameters/PerPage"),
     *     @OA\Parameter(ref="#/components/parameters/Page"),
     *     @OA\Parameter(ref="#/components/parameters/WithTrashedOptional"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de empleados cargada correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeListResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros de consulta inválidos.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"per_page": "Registros por pagina.", "page": "N° de pagina.", "search": "Filtro de Busqueda.", "column": "Columna a ordenar.", "dir": "Sentido del orden.", "with_trashed": "Registros inactivos", "with_user": "Usuario asociado"},
     *                 "errors": {"with_user": {"Debe ser verdadero o falso."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(EmployeeAllRequest $request)
    {
        try {
            $employees = Employee::with(['person' => ['photo'], 'position' => ['area'], 'risk_manager', 'health_entity', 'pension_fund', 'compensation_fund', 'workstation', 'user' => function ($q) {
                $q->withTrashed()->with(['roles', 'permissions']);
            }])
                ->when($request->filled('position_id'), function ($query) use ($request) {
                    return $query->whereHas('position', function ($query) use ($request) {
                        return $query->where('id', $request->integer('position_id'));
                    });
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->has('with_user'), function ($query) use ($request) {
                    return $request->boolean('with_user')
                        ? $query->whereHas('user')
                        : $query->whereDoesntHave('user');
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                });

            $employees = $employees->paginate($request->integer('per_page', $employees->count()));

            return $this->successResponse(
                new EmployeeCollection($employees),
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
     *     path="/employees/find/{id}",
     *     tags={"Employees"},
     *     summary="Obtener un empleado",
     *     description="Ojo: hoy el `{id}` se valida contra la tabla de personas, no la de empleados (ver pendientes). Un id de empleado sin persona con ese mismo id da 422; uno que no es empleado pero sí persona da 404.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Empleado cargado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="El id existe como persona pero no como empleado.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse",
     *             example={"error": {"message": "No se encontraron resultados para la búsqueda.", "error": "No query results for model [App\Models\Employee] 380."}}
     *         )
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function find(EmployeeFindRequest $request, $id)
    {
        try {
            $employee = Employee::with(['person' => ['photo', 'gender', 'blood_type', 'document_type', 'phone_country'], 'position' => ['area'], 'risk_manager', 'health_entity', 'pension_fund', 'compensation_fund', 'user' => ['roles', 'permissions'], 'workstation'])
                ->findOrFail($id);
            $employee->person?->loadMorph('location', [City::class => ['department' => ['country']], Department::class => ['country']]);

            return $this->successResponse(
                new EmployeeResource($employee),
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
     *     path="/employees/store",
     *     tags={"Employees"},
     *     summary="Crear un empleado",
     *     description="Vincula una persona con su cargo y sus entidades de seguridad social.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Empleado creado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o la persona ya es empleado.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"person_id": "Persona", "operation_center": "Centro de operaciones", "position_id": "Cargo", "risk_manager_id": "Administradora de Riesgos", "health_entity_id": "Entidad de Salud", "pension_fund_id": "Fondo de pensión", "compensation_fund_id": "Caja de compensación", "start_date": "Fecha inicio", "end_date": "Fecha fin"},
     *                 "errors": {"person_id": {"Ya está registrado."}, "start_date": {"Formato inválido. Permitido Y-m-d."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(EmployeeStoreRequest $request)
    {
        try {
            $employee = new Employee;
            $employee->person_id = $request->integer('person_id');
            $employee->operation_center = $request->input('operation_center', null);
            $employee->position_id = $request->integer('position_id');
            $employee->risk_manager_id = $request->integer('risk_manager_id');
            $employee->health_entity_id = $request->integer('health_entity_id');
            $employee->pension_fund_id = $request->integer('pension_fund_id');
            $employee->compensation_fund_id = $request->integer('compensation_fund_id');
            $employee->workstation_id = $request->integer('workstation_id') ?: null;
            $employee->start_date = $request->input('start_date');
            $employee->end_date = $request->input('end_date', null);

            $employee->save();

            return $this->successResponse(
                new EmployeeResource($employee->load('workstation')),
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
     *     path="/employees/update/{id}",
     *     tags={"Employees"},
     *     summary="Editar un empleado",
     *     description="Reemplaza todos los campos. Si no se envía `operation_center` o `end_date`, quedan en `null`.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/EmployeeRequest",
     *             example={"person_id": 57, "operation_center": "051", "position_id": 59, "risk_manager_id": 94, "health_entity_id": 101, "pension_fund_id": 152, "compensation_fund_id": 163, "start_date": "2026-06-02", "end_date": "2026-12-31"}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Empleado editado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o empleado no registrado.",
     *
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={
     *                 "message": "Error de validación.",
     *                 "attributes": {"id": "Identificador del empleado", "person_id": "Persona", "operation_center": "Centro de operaciones", "position_id": "Cargo", "risk_manager_id": "Administradora de Riesgos", "health_entity_id": "Entidad de Salud", "pension_fund_id": "Fondo de pensión", "compensation_fund_id": "Caja de compensación", "start_date": "Fecha inicio", "end_date": "Fecha fin"},
     *                 "errors": {"end_date": {"Debe ser una fecha posterior a Fecha inicio."}}
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(EmployeeUpdateRequest $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->person_id = $request->integer('person_id');
            $employee->operation_center = $request->input('operation_center', null);
            $employee->position_id = $request->integer('position_id');
            $employee->risk_manager_id = $request->integer('risk_manager_id');
            $employee->health_entity_id = $request->integer('health_entity_id');
            $employee->pension_fund_id = $request->integer('pension_fund_id');
            $employee->compensation_fund_id = $request->integer('compensation_fund_id');
            $employee->workstation_id = $request->integer('workstation_id') ?: null;
            $employee->start_date = $request->input('start_date');
            $employee->end_date = $request->input('end_date', null);

            $employee->save();

            return $this->successResponse(
                new EmployeeResource($employee->load('workstation')),
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
     *     path="/employees/delete/{id}",
     *     tags={"Employees"},
     *     summary="Desactivar un empleado",
     *     description="Desactivación lógica (soft delete). Ojo: hoy el `{id}` se valida contra la tabla de personas activas, no la de empleados (ver pendientes).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Empleado desactivado correctamente. `deleted_at` queda con la fecha de desactivación.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeResponse")
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function delete(EmployeeDeleteRequest $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->delete();

            return $this->successResponse(
                new EmployeeResource($employee),
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
     *     path="/employees/restore/{id}",
     *     tags={"Employees"},
     *     summary="Restaurar un empleado desactivado",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/PathId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Empleado restaurado correctamente",
     *
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeResponse")
     *     ),
     *
     *     @OA\Response(response=422, ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function restore(EmployeeRestoreRequest $request, $id)
    {
        try {
            $employee = Employee::withTrashed()->findOrFail($id);
            $employee->restore();

            return $this->successResponse(
                new EmployeeResource($employee),
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
