<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\EmployeeAllRequest;
use App\Http\Requests\Employee\EmployeeDeleteRequest;
use App\Http\Requests\Employee\EmployeeFindRequest;
use App\Http\Requests\Employee\EmployeeRestoreRequest;
use App\Http\Requests\Employee\EmployeeStoreRequest;
use App\Http\Requests\Employee\EmployeeUpdateRequest;
use App\Http\Resources\Employee\EmployeeCollection;
use App\Models\Employee;
use App\Models\User;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class EmployeeController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(EmployeeAllRequest $request)
    {
        try {
            logger($request);
            $employees = Employee::with(['person' => ['photo'], 'position' => ['area'], 'risk_manager', 'health_entity', 'pension_fund', 'compensation_fund', 'user' => function ($q) {
                $q->withTrashed()
                    ->with(['roles', 'permissions']);
            }])
                ->when($request->boolean('with_user'), function ($query) use ($request) {
                    return $query;
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->has('with_user'), function ($query) use ($request) {
                    return $request->boolean('with_user')
                        ? $query->whereHas('user', fn($wq) => $wq->withTrashed())
                        : $query->whereDoesntHave('user', fn($wq) => $wq->withTrashed());
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
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
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function find(EmployeeFindRequest $request, $id)
    {
        try {
            $employee = Employee::with(['person' => ['photo'], 'position' => ['area'], 'risk_manager', 'health_entity', 'pension_fund', 'compensation_fund', 'user' => ['roles', 'permissions']])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'employee' => $employee
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function store(EmployeeStoreRequest $request)
    {
        try {
            $employee = new Employee();
            $employee->person_id = $request->integer('person_id');
            $employee->operation_center = $request->input('operation_center', null);
            $employee->position_id = $request->integer('position_id');
            $employee->risk_manager_id = $request->integer('risk_manager_id');
            $employee->health_entity_id = $request->integer('health_entity_id');
            $employee->pension_fund_id = $request->integer('pension_fund_id');
            $employee->compensation_fund_id = $request->integer('compensation_fund_id');
            $employee->start_date = $request->input('start_date');
            $employee->end_date = $request->input('end_date', null);
            $employee->save();

            return $this->successResponse(
                [
                    'employee' => $employee
                ],
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

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
            $employee->start_date = $request->input('start_date');
            $employee->end_date = $request->input('end_date', null);
            $employee->save();

            return $this->successResponse(
                [
                    'employee' => $employee
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function delete(EmployeeDeleteRequest $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->delete();

            return $this->successResponse(
                [
                    'employee' => $employee
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function restore(EmployeeRestoreRequest $request, $id)
    {
        try {
            $employee = Employee::withTrashed()->findOrFail($id);
            $employee->restore();

            return $this->successResponse(
                [
                    'employee' => $employee
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
