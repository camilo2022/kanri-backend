<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClothingLine\ClothingLineAllRequest;
use App\Http\Requests\ClothingLine\ClothingLineDeleteRequest;
use App\Http\Requests\ClothingLine\ClothingLineFindRequest;
use App\Http\Requests\ClothingLine\ClothingLineRestoreRequest;
use App\Http\Requests\ClothingLine\ClothingLineStoreRequest;
use App\Http\Requests\ClothingLine\ClothingLineUpdateRequest;
use App\Http\Resources\ClothingLine\ClothingLineCollection;
use App\Models\ClothingLine;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class ClothingLineController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(ClothingLineAllRequest $request)
    {
        try {
            $clothing_lines = ClothingLine::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $clothing_lines = $clothing_lines->paginate($request->integer('per_page', $clothing_lines->count()));

            return $this->successResponse(
                new ClothingLineCollection($clothing_lines),
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

    public function find(ClothingLineFindRequest $request, $id)
    {
        try {
            $clothing_line = ClothingLine::findOrFail($id);

            return $this->successResponse(
                [
                    'clothing_line' => $clothing_line
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

    public function store(ClothingLineStoreRequest $request)
    {
        try {
            $clothing_line = new ClothingLine();
            $clothing_line->name = $request->input('name');
            $clothing_line->description = $request->input('description');
            $clothing_line->save();

            return $this->successResponse(
                [
                    'clothing_line' => $clothing_line
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

    public function update(ClothingLineUpdateRequest $request, $id)
    {
        try {
            $clothing_line = ClothingLine::findOrFail($id);
            $clothing_line->name = $request->input('name');
            $clothing_line->description = $request->input('description');
            $clothing_line->save();

            return $this->successResponse(
                [
                    'clothing_line' => $clothing_line
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

    public function delete(ClothingLineDeleteRequest $request, $id)
    {
        try {
            $clothing_line = ClothingLine::findOrFail($id);
            $clothing_line->delete();

            return $this->successResponse(
                [
                    'clothing_line' => $clothing_line
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

    public function restore(ClothingLineRestoreRequest $request, $id)
    {
        try {
            $clothing_line = ClothingLine::withTrashed()->findOrFail($id);
            $clothing_line->restore();

            return $this->successResponse(
                [
                    'clothing_line' => $clothing_line
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
