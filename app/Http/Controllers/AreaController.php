<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationalStructure\Area\AreaAllRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaDeleteRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaFindRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaRestoreRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaStoreRequest;
use App\Http\Requests\OrganizationalStructure\Area\AreaUpdateRequest;
use App\Http\Resources\OrganizationalStructure\Area\AreaCollection;
use App\Models\Area;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class AreaController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(AreaAllRequest $request)
    {
        try {
            $areas = Area::with(['positions'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $areas = $areas->paginate($request->integer('per_page', $areas->count()));

            return $this->successResponse(
                new AreaCollection($areas),
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

    public function find(AreaFindRequest $request, $id)
    {
        try {
            $area = Area::with(['positions'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'area' => $area
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

    public function store(AreaStoreRequest $request)
    {
        try {
            $area = new Area();
            $area->name = $request->input('name');
            $area->description = $request->input('description');
            $area->save();

            return $this->successResponse(
                [
                    'area' => $area
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

    public function update(AreaUpdateRequest $request, $id)
    {
        try {
            $area = Area::findOrFail($id);
            $area->name = $request->input('name');
            $area->description = $request->input('description');
            $area->save();

            return $this->successResponse(
                [
                    'area' => $area
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

    public function delete(AreaDeleteRequest $request, $id)
    {
        try {
            $area = Area::findOrFail($id);
            $area->delete();

            return $this->successResponse(
                [
                    'area' => $area
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

    public function restore(AreaRestoreRequest $request, $id)
    {
        try {
            $area = Area::withTrashed()->findOrFail($id);
            $area->restore();

            return $this->successResponse(
                [
                    'area' => $area
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
