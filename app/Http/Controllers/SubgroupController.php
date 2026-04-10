<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subgroup\SubgroupAllRequest;
use App\Http\Requests\Subgroup\SubgroupDeleteRequest;
use App\Http\Requests\Subgroup\SubgroupFindRequest;
use App\Http\Requests\Subgroup\SubgroupRestoreRequest;
use App\Http\Requests\Subgroup\SubgroupStoreRequest;
use App\Http\Requests\Subgroup\SubgroupUpdateRequest;
use App\Http\Resources\Subgroup\SubgroupCollection;
use App\Models\Subgroup;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class SubgroupController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(SubgroupAllRequest $request)
    {
        try {
            $subgroups = Subgroup::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $subgroups = $subgroups->paginate($request->integer('per_page', $subgroups->count()));

            return $this->successResponse(
                new SubgroupCollection($subgroups),
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

    public function find(SubgroupFindRequest $request, $id)
    {
        try {
            $subgroup = Subgroup::findOrFail($id);

            return $this->successResponse(
                [
                    'subgroup' => $subgroup
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

    public function store(SubgroupStoreRequest $request)
    {
        try {
            $subgroup = new Subgroup();
            $subgroup->name = $request->input('name');
            $subgroup->description = $request->input('description');
            $subgroup->save();

            return $this->successResponse(
                [
                    'subgroup' => $subgroup
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

    public function update(SubgroupUpdateRequest $request, $id)
    {
        try {
            $subgroup = Subgroup::findOrFail($id);
            $subgroup->name = $request->input('name');
            $subgroup->description = $request->input('description');
            $subgroup->save();

            return $this->successResponse(
                [
                    'subgroup' => $subgroup
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

    public function delete(SubgroupDeleteRequest $request, $id)
    {
        try {
            $subgroup = Subgroup::findOrFail($id);
            $subgroup->delete();

            return $this->successResponse(
                [
                    'subgroup' => $subgroup
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

    public function restore(SubgroupRestoreRequest $request, $id)
    {
        try {
            $subgroup = Subgroup::withTrashed()->findOrFail($id);
            $subgroup->restore();

            return $this->successResponse(
                [
                    'subgroup' => $subgroup
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
