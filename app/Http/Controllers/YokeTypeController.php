<?php

namespace App\Http\Controllers;

use App\Http\Requests\YokeType\YokeTypeAllRequest;
use App\Http\Requests\YokeType\YokeTypeDeleteRequest;
use App\Http\Requests\YokeType\YokeTypeFindRequest;
use App\Http\Requests\YokeType\YokeTypeRestoreRequest;
use App\Http\Requests\YokeType\YokeTypeStoreRequest;
use App\Http\Requests\YokeType\YokeTypeUpdateRequest;
use App\Http\Resources\YokeType\YokeTypeCollection;
use App\Models\YokeType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class YokeTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(YokeTypeAllRequest $request)
    {
        try {
            $yoke_types = YokeType::with(['item'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $yoke_types = $yoke_types->paginate($request->integer('per_page', $yoke_types->count()));

            return $this->successResponse(
                new YokeTypeCollection($yoke_types),
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

    public function find(YokeTypeFindRequest $request, $id)
    {
        try {
            $yoke_type = YokeType::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'yoke_type' => $yoke_type
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

    public function store(YokeTypeStoreRequest $request)
    {
        try {
            $yoke_type = new YokeType();
            $yoke_type->name = $request->input('name');
            $yoke_type->description = $request->input('description');

            $yoke_type->save();

            return $this->successResponse(
                [
                    'yoke_type' => $yoke_type
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

    public function update(YokeTypeUpdateRequest $request, $id)
    {
        try {
            $yoke_type = YokeType::findOrFail($id);
            $yoke_type->name = $request->input('name');
            $yoke_type->description = $request->input('description');

            $yoke_type->save();

            return $this->successResponse(
                [
                    'yoke_type' => $yoke_type
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

    public function delete(YokeTypeDeleteRequest $request, $id)
    {
        try {
            $yoke_type = YokeType::findOrFail($id);
            $yoke_type->delete();

            return $this->successResponse(
                [
                    'yoke_type' => $yoke_type
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

    public function restore(YokeTypeRestoreRequest $request, $id)
    {
        try {
            $yoke_type = YokeType::withTrashed()->findOrFail($id);
            $yoke_type->restore();

            return $this->successResponse(
                [
                    'yoke_type' => $yoke_type
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
