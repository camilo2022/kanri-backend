<?php

namespace App\Http\Controllers;

use App\Http\Requests\Size\SizeAllRequest;
use App\Http\Requests\Size\SizeDeleteRequest;
use App\Http\Requests\Size\SizeFindRequest;
use App\Http\Requests\Size\SizeRestoreRequest;
use App\Http\Requests\Size\SizeStoreRequest;
use App\Http\Requests\Size\SizeUpdateRequest;
use App\Http\Resources\Size\SizeCollection;
use App\Models\Size;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class SizeController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(SizeAllRequest $request)
    {
        try {
            $sizes = Size::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $sizes = $sizes->paginate($request->integer('per_page', $sizes->count()));

            return $this->successResponse(
                new SizeCollection($sizes),
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

    public function find(SizeFindRequest $request, $id)
    {
        try {
            $size = Size::findOrFail($id);

            return $this->successResponse(
                [
                    'size' => $size
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

    public function store(SizeStoreRequest $request)
    {
        try {
            $size = new Size();
            $size->name = $request->input('name');
            $size->description = $request->input('description');
            $size->save();

            return $this->successResponse(
                [
                    'size' => $size
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

    public function update(SizeUpdateRequest $request, $id)
    {
        try {
            $size = Size::findOrFail($id);
            $size->name = $request->input('name');
            $size->description = $request->input('description');
            $size->save();

            return $this->successResponse(
                [
                    'size' => $size
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

    public function delete(SizeDeleteRequest $request, $id)
    {
        try {
            $size = Size::findOrFail($id);
            $size->delete();

            return $this->successResponse(
                [
                    'size' => $size
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

    public function restore(SizeRestoreRequest $request, $id)
    {
        try {
            $size = Size::withTrashed()->findOrFail($id);
            $size->restore();

            return $this->successResponse(
                [
                    'size' => $size
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
