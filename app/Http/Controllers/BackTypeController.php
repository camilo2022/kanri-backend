<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackType\BackTypeAllRequest;
use App\Http\Requests\BackType\BackTypeDeleteRequest;
use App\Http\Requests\BackType\BackTypeFindRequest;
use App\Http\Requests\BackType\BackTypeRestoreRequest;
use App\Http\Requests\BackType\BackTypeStoreRequest;
use App\Http\Requests\BackType\BackTypeUpdateRequest;
use App\Http\Resources\BackType\BackTypeCollection;
use App\Models\BackType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class BackTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(BackTypeAllRequest $request)
    {
        try {
            $back_types = BackType::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $back_types = $back_types->paginate($request->integer('per_page', $back_types->count()));

            return $this->successResponse(
                new BackTypeCollection($back_types),
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

    public function find(BackTypeFindRequest $request, $id)
    {
        try {
            $back_type = BackType::findOrFail($id);

            return $this->successResponse(
                [
                    'back_type' => $back_type
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

    public function store(BackTypeStoreRequest $request)
    {
        try {
            $back_type = new BackType();
            $back_type->name = $request->input('name');
            $back_type->description = $request->input('description');
            $back_type->save();

            return $this->successResponse(
                [
                    'back_type' => $back_type
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

    public function update(BackTypeUpdateRequest $request, $id)
    {
        try {
            $back_type = BackType::findOrFail($id);
            $back_type->name = $request->input('name');
            $back_type->description = $request->input('description');
            $back_type->save();

            return $this->successResponse(
                [
                    'back_type' => $back_type
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

    public function delete(BackTypeDeleteRequest $request, $id)
    {
        try {
            $back_type = BackType::findOrFail($id);
            $back_type->delete();

            return $this->successResponse(
                [
                    'back_type' => $back_type
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

    public function restore(BackTypeRestoreRequest $request, $id)
    {
        try {
            $back_type = BackType::withTrashed()->findOrFail($id);
            $back_type->restore();

            return $this->successResponse(
                [
                    'back_type' => $back_type
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
