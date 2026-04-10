<?php

namespace App\Http\Controllers;

use App\Http\Requests\GarmentType\GarmentTypeAllRequest;
use App\Http\Requests\GarmentType\GarmentTypeDeleteRequest;
use App\Http\Requests\GarmentType\GarmentTypeFindRequest;
use App\Http\Requests\GarmentType\GarmentTypeRestoreRequest;
use App\Http\Requests\GarmentType\GarmentTypeStoreRequest;
use App\Http\Requests\GarmentType\GarmentTypeUpdateRequest;
use App\Http\Resources\GarmentType\GarmentTypeCollection;
use App\Models\GarmentType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class GarmentTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(GarmentTypeAllRequest $request)
    {
        try {
            $garment_types = GarmentType::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $garment_types = $garment_types->paginate($request->integer('per_page', $garment_types->count()));

            return $this->successResponse(
                new GarmentTypeCollection($garment_types),
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

    public function find(GarmentTypeFindRequest $request, $id)
    {
        try {
            $garment_type = GarmentType::findOrFail($id);

            return $this->successResponse(
                [
                    'garment_type' => $garment_type
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

    public function store(GarmentTypeStoreRequest $request)
    {
        try {
            $garment_type = new GarmentType();
            $garment_type->name = $request->input('name');
            $garment_type->description = $request->input('description');
            $garment_type->save();

            return $this->successResponse(
                [
                    'garment_type' => $garment_type
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

    public function update(GarmentTypeUpdateRequest $request, $id)
    {
        try {
            $garment_type = GarmentType::findOrFail($id);
            $garment_type->name = $request->input('name');
            $garment_type->description = $request->input('description');
            $garment_type->save();

            return $this->successResponse(
                [
                    'garment_type' => $garment_type
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

    public function delete(GarmentTypeDeleteRequest $request, $id)
    {
        try {
            $garment_type = GarmentType::findOrFail($id);
            $garment_type->delete();

            return $this->successResponse(
                [
                    'garment_type' => $garment_type
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

    public function restore(GarmentTypeRestoreRequest $request, $id)
    {
        try {
            $garment_type = GarmentType::withTrashed()->findOrFail($id);
            $garment_type->restore();

            return $this->successResponse(
                [
                    'garment_type' => $garment_type
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
