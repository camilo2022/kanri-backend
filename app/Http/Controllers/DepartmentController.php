<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\Department\DepartmentAllRequest;
use App\Http\Resources\Location\Department\DepartmentCollection;
use App\Models\Department;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class DepartmentController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(DepartmentAllRequest $request, $country_id)
    {
        try {
            $departments = Department::withCount(['cities'])->where('country_id', $request->integer('country_id'))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderBy('name');
                });

            $departments = $departments->paginate($request->integer('per_page', $departments->count()));

            return $this->successResponse(
                new DepartmentCollection($departments),
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
