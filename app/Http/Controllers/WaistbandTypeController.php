<?php

namespace App\Http\Controllers;

use App\Http\Requests\WaistbandType\WaistbandTypeFindRequest;
use App\Http\Requests\WaistbandType\WaistbandTypeAllRequest;
use App\Http\Requests\WaistbandType\WaistbandTypeDeleteRequest;
use App\Http\Requests\WaistbandType\WaistbandTypeRestoreRequest;
use App\Http\Requests\WaistbandType\WaistbandTypeStoreRequest;
use App\Http\Requests\WaistbandType\WaistbandTypeUpdateRequest;
use App\Http\Resources\WaistbandType\WaistbandTypeCollection;
use App\Models\WaistbandType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class WaistbandTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(WaistbandTypeAllRequest $request)
    {
        try {
            $waistband_types = WaistbandType::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $waistband_types = $waistband_types->paginate($request->integer('per_page', $waistband_types->count()));

            return $this->successResponse(
                new WaistbandTypeCollection($waistband_types),
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

    public function find(WaistbandTypeFindRequest $request, $id)
    {
        try {
            $waistband_type = WaistbandType::findOrFail($id);

            return $this->successResponse(
                [
                    'waistband_type' => $waistband_type
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

    public function store(WaistbandTypeStoreRequest $request)
    {
        try {
            $waistband_type = new WaistbandType();
            $waistband_type->name = $request->input('name');
            $waistband_type->description = $request->input('description');
            $waistband_type->save();

            return $this->successResponse(
                [
                    'waistband_type' => $waistband_type
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

    public function update(WaistbandTypeUpdateRequest $request, $id)
    {
        try {
            $waistband_type = WaistbandType::findOrFail($id);
            $waistband_type->name = $request->input('name');
            $waistband_type->description = $request->input('description');
            $waistband_type->save();

            return $this->successResponse(
                [
                    'waistband_type' => $waistband_type
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

    public function delete(WaistbandTypeDeleteRequest $request, $id)
    {
        try {
            $waistband_type = WaistbandType::findOrFail($id);
            $waistband_type->delete();

            return $this->successResponse(
                [
                    'waistband_type' => $waistband_type
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

    public function restore(WaistbandTypeRestoreRequest $request, $id)
    {
        try {
            $waistband_type = WaistbandType::withTrashed()->findOrFail($id);
            $waistband_type->restore();

            return $this->successResponse(
                [
                    'waistband_type' => $waistband_type
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
