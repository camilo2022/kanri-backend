<?php

namespace App\Http\Controllers;

use App\Http\Requests\BootType\BootTypeAllRequest;
use App\Http\Requests\BootType\BootTypeDeleteRequest;
use App\Http\Requests\BootType\BootTypeFindRequest;
use App\Http\Requests\BootType\BootTypeRestoreRequest;
use App\Http\Requests\BootType\BootTypeStoreRequest;
use App\Http\Requests\BootType\BootTypeUpdateRequest;
use App\Http\Resources\BootType\BootTypeCollection;
use App\Models\BootType;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class BootTypeController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(BootTypeAllRequest $request)
    {
        try {
            $boot_types = BootType::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $boot_types = $boot_types->paginate($request->integer('per_page', $boot_types->count()));

            return $this->successResponse(
                new BootTypeCollection($boot_types),
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

    public function find(BootTypeFindRequest $request, $id)
    {
        try {
            $boot_type = BootType::findOrFail($id);

            return $this->successResponse(
                [
                    'boot_type' => $boot_type
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

    public function store(BootTypeStoreRequest $request)
    {
        try {
            $boot_type = new BootType();
            $boot_type->name = $request->input('name');
            $boot_type->description = $request->input('description');
            $boot_type->save();

            return $this->successResponse(
                [
                    'boot_type' => $boot_type
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

    public function update(BootTypeUpdateRequest $request, $id)
    {
        try {
            $boot_type = BootType::findOrFail($id);
            $boot_type->name = $request->input('name');
            $boot_type->description = $request->input('description');
            $boot_type->save();

            return $this->successResponse(
                [
                    'boot_type' => $boot_type
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

    public function delete(BootTypeDeleteRequest $request, $id)
    {
        try {
            $boot_type = BootType::findOrFail($id);
            $boot_type->delete();

            return $this->successResponse(
                [
                    'boot_type' => $boot_type
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

    public function restore(BootTypeRestoreRequest $request, $id)
    {
        try {
            $boot_type = BootType::withTrashed()->findOrFail($id);
            $boot_type->restore();

            return $this->successResponse(
                [
                    'boot_type' => $boot_type
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
