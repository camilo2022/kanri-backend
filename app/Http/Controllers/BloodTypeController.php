<?php

namespace App\Http\Controllers;

use App\Http\Requests\BloodType\BloodTypeAllRequest;
use App\Http\Requests\BloodType\BloodTypeDeleteRequest;
use App\Http\Requests\BloodType\BloodTypeFindRequest;
use App\Http\Requests\BloodType\BloodTypeRestoreRequest;
use App\Http\Requests\BloodType\BloodTypeStoreRequest;
use App\Http\Requests\BloodType\BloodTypeUpdateRequest;
use App\Http\Resources\BloodType\BloodTypeCollection;
use App\Models\BloodType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class BloodTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(BloodTypeAllRequest $request)
    {
        try {
            $blood_types = BloodType::when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $blood_types = $blood_types->paginate($request->integer('per_page', $blood_types->count()));

            return $this->successResponse(
                new BloodTypeCollection($blood_types),
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

    public function find(BloodTypeFindRequest $request, $id)
    {
        try {
            $blood_type = BloodType::findOrFail($id);

            return $this->successResponse(
                [
                    'blood_type' => $blood_type
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

    public function store(BloodTypeStoreRequest $request)
    {
        try {
            $blood_type = new BloodType();
            $blood_type->name = $request->input('name');
            $blood_type->description = $request->input('description');
            $blood_type->save();

            return $this->successResponse(
                [
                    'blood_type' => $blood_type
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

    public function update(BloodTypeUpdateRequest $request, $id)
    {
        try {
            $blood_type = BloodType::findOrFail($id);
            $blood_type->name = $request->input('name');
            $blood_type->description = $request->input('description');
            $blood_type->save();

            return $this->successResponse(
                [
                    'blood_type' => $blood_type
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

    public function delete(BloodTypeDeleteRequest $request, $id)
    {
        try {
            $blood_type = BloodType::findOrFail($id);
            $blood_type->delete();

            return $this->successResponse(
                [
                    'blood_type' => $blood_type
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

    public function restore(BloodTypeRestoreRequest $request, $id)
    {
        try {
            $blood_type = BloodType::withTrashed()->findOrFail($id);
            $blood_type->restore();

            return $this->successResponse(
                [
                    'blood_type' => $blood_type
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
