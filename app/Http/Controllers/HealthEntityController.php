<?php

namespace App\Http\Controllers;

use App\Http\Requests\HealthEntity\HealthEntityAllRequest;
use App\Http\Requests\HealthEntity\HealthEntityDeleteRequest;
use App\Http\Requests\HealthEntity\HealthEntityFindRequest;
use App\Http\Requests\HealthEntity\HealthEntityRestoreRequest;
use App\Http\Requests\HealthEntity\HealthEntityStoreRequest;
use App\Http\Requests\HealthEntity\HealthEntityUpdateRequest;
use App\Http\Resources\HealthEntity\HealthEntityCollection;
use App\Models\HealthEntity;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class HealthEntityController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(HealthEntityAllRequest $request)
    {
        try {
            $health_entitys = HealthEntity::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $health_entitys = $health_entitys->paginate($request->integer('per_page', $health_entitys->count()));

            return $this->successResponse(
                new HealthEntityCollection($health_entitys),
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

    public function find(HealthEntityFindRequest $request, $id)
    {
        try {
            $health_entity = HealthEntity::findOrFail($id);

            return $this->successResponse(
                [
                    'health_entity' => $health_entity
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

    public function store(HealthEntityStoreRequest $request)
    {
        try {
            $health_entity = new HealthEntity();
            $health_entity->name = $request->input('name');
            $health_entity->description = $request->input('description');
            $health_entity->save();

            return $this->successResponse(
                [
                    'health_entity' => $health_entity
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

    public function update(HealthEntityUpdateRequest $request, $id)
    {
        try {
            $health_entity = HealthEntity::findOrFail($id);
            $health_entity->name = $request->input('name');
            $health_entity->description = $request->input('description');
            $health_entity->save();

            return $this->successResponse(
                [
                    'health_entity' => $health_entity
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

    public function delete(HealthEntityDeleteRequest $request, $id)
    {
        try {
            $health_entity = HealthEntity::findOrFail($id);
            $health_entity->delete();

            return $this->successResponse(
                [
                    'health_entity' => $health_entity
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

    public function restore(HealthEntityRestoreRequest $request, $id)
    {
        try {
            $health_entity = HealthEntity::withTrashed()->findOrFail($id);
            $health_entity->restore();

            return $this->successResponse(
                [
                    'health_entity' => $health_entity
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
