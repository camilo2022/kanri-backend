<?php

namespace App\Http\Controllers;

use App\Http\Requests\Group\GroupAllRequest;
use App\Http\Requests\Group\GroupDeleteRequest;
use App\Http\Requests\Group\GroupFindRequest;
use App\Http\Requests\Group\GroupRestoreRequest;
use App\Http\Requests\Group\GroupStoreRequest;
use App\Http\Requests\Group\GroupUpdateRequest;
use App\Http\Resources\Group\GroupCollection;
use App\Models\Group;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class GroupController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(GroupAllRequest $request)
    {
        try {
            $groups = Group::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $groups = $groups->paginate($request->integer('per_page', $groups->count()));

            return $this->successResponse(
                new GroupCollection($groups),
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

    public function find(GroupFindRequest $request, $id)
    {
        try {
            $group = Group::findOrFail($id);

            return $this->successResponse(
                [
                    'group' => $group
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

    public function store(GroupStoreRequest $request)
    {
        try {
            $group = new Group();
            $group->name = $request->input('name');
            $group->description = $request->input('description');
            $group->save();

            return $this->successResponse(
                [
                    'group' => $group
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

    public function update(GroupUpdateRequest $request, $id)
    {
        try {
            $group = Group::findOrFail($id);
            $group->name = $request->input('name');
            $group->description = $request->input('description');
            $group->save();

            return $this->successResponse(
                [
                    'group' => $group
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

    public function delete(GroupDeleteRequest $request, $id)
    {
        try {
            $group = Group::findOrFail($id);
            $group->delete();

            return $this->successResponse(
                [
                    'group' => $group
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

    public function restore(GroupRestoreRequest $request, $id)
    {
        try {
            $group = Group::withTrashed()->findOrFail($id);
            $group->restore();

            return $this->successResponse(
                [
                    'group' => $group
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
