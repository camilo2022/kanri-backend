<?php

namespace App\Http\Controllers;

use App\Http\Requests\Position\PositionAllRequest;
use App\Http\Requests\Position\PositionAuthorizationAssignRequest;
use App\Http\Requests\Position\PositionAuthorizationRemoveRequest;
use App\Http\Requests\Position\PositionDeleteRequest;
use App\Http\Requests\Position\PositionFindRequest;
use App\Http\Requests\Position\PositionRestoreRequest;
use App\Http\Requests\Position\PositionStoreRequest;
use App\Http\Requests\Position\PositionUpdateRequest;
use App\Http\Resources\Position\PositionCollection;
use App\Models\Permission;
use App\Models\Position;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class PositionController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(PositionAllRequest $request)
    {
        try {
            $positions = Position::with(['area', 'roles', 'permissions'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $positions = $positions->paginate($request->integer('per_page', $positions->count()));

            return $this->successResponse(
                new PositionCollection($positions),
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

    public function find(PositionFindRequest $request, $id)
    {
        try {
            $position = Position::with(['area', 'roles', 'permissions'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'position' => $position
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

    public function store(PositionStoreRequest $request)
    {
        try {
            $position = new Position();
            $position->name = $request->input('name');
            $position->description = $request->input('description');
            $position->save();

            return $this->successResponse(
                [
                    'position' => $position
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

    public function update(PositionUpdateRequest $request, $id)
    {
        try {
            $position = Position::findOrFail($id);
            $position->name = $request->input('name');
            $position->description = $request->input('description');
            $position->save();

            return $this->successResponse(
                [
                    'position' => $position
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

    public function delete(PositionDeleteRequest $request, $id)
    {
        try {
            $position = Position::findOrFail($id);
            $position->delete();

            return $this->successResponse(
                [
                    'position' => $position
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

    public function restore(PositionRestoreRequest $request, $id)
    {
        try {
            $position = Position::withTrashed()->findOrFail($id);
            $position->restore();

            return $this->successResponse(
                [
                    'position' => $position
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

    public function assign(PositionAuthorizationAssignRequest $request, $id)
    {
        try {
            $permission = Permission::findOrFail($request->input('permission_id'));
            $position = Position::findOrFail($id);

            $position->givePermissionTo($permission);

            $position->assignRole($permission->roles);

            $position->load(['roles', 'permissions']);

            return $this->successResponse(
                [
                    'position' => $position
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

    public function remove(PositionAuthorizationRemoveRequest $request, $id)
    {
        try {
            $permission = Permission::findOrFail($request->input('permission_id'));
            $position = Position::findOrFail($id);

            $position->revokePermissionTo($permission);

            $position->roles->each(function ($role) use ($position) {
                if ($position->permissions->pluck('name')->intersect($role->permissions->pluck('name'))->isEmpty()) {
                    $position->removeRole($role);
                }
            });

            $position->load(['roles', 'permissions']);

            return $this->successResponse(
                [
                    'position' => $position
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
