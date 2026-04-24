<?php

namespace App\Http\Controllers;

use App\Http\Requests\Color\ColorAllRequest;
use App\Http\Requests\Color\ColorDeleteRequest;
use App\Http\Requests\Color\ColorFindRequest;
use App\Http\Requests\Color\ColorRestoreRequest;
use App\Http\Requests\Color\ColorStoreRequest;
use App\Http\Requests\Color\ColorUpdateRequest;
use App\Http\Resources\Color\ColorCollection;
use App\Models\Color;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class ColorController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(ColorAllRequest $request)
    {
        try {
            $colors = Color::with(['item'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $colors = $colors->paginate($request->integer('per_page', $colors->count()));

            return $this->successResponse(
                new ColorCollection($colors),
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

    public function find(ColorFindRequest $request, $id)
    {
        try {
            $color = Color::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'color' => $color
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

    public function store(ColorStoreRequest $request)
    {
        try {
            $color = new Color();
            $color->name = $request->input('name');
            $color->description = $request->input('description');
            $color->settings = $request->input('settings');

            $color->save();

            return $this->successResponse(
                [
                    'color' => $color
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

    public function update(ColorUpdateRequest $request, $id)
    {
        try {
            $color = Color::findOrFail($id);
            $color->name = $request->input('name');
            $color->description = $request->input('description');
            $color->settings = $request->input('settings');

            $color->save();

            return $this->successResponse(
                [
                    'color' => $color
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

    public function delete(ColorDeleteRequest $request, $id)
    {
        try {
            $color = Color::findOrFail($id);
            $color->delete();

            return $this->successResponse(
                [
                    'color' => $color
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

    public function restore(ColorRestoreRequest $request, $id)
    {
        try {
            $color = Color::withTrashed()->findOrFail($id);
            $color->restore();

            return $this->successResponse(
                [
                    'color' => $color
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
