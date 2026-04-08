<?php

namespace App\Http\Controllers;

use App\Http\Requests\Silhouette\SilhouetteAllRequest;
use App\Http\Requests\Silhouette\SilhouetteDeleteRequest;
use App\Http\Requests\Silhouette\SilhouetteFindRequest;
use App\Http\Requests\Silhouette\SilhouetteRestoreRequest;
use App\Http\Requests\Silhouette\SilhouetteStoreRequest;
use App\Http\Requests\Silhouette\SilhouetteUpdateRequest;
use App\Http\Resources\Silhouette\SilhouetteCollection;
use App\Models\Silhouette;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class SilhouetteController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(SilhouetteAllRequest $request)
    {
        try {
            $silhouettes = Silhouette::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $silhouettes = $silhouettes->paginate($request->integer('per_page', $silhouettes->count()));

            return $this->successResponse(
                new SilhouetteCollection($silhouettes),
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

    public function find(SilhouetteFindRequest $request, $id)
    {
        try {
            $silhouette = Silhouette::findOrFail($id);

            return $this->successResponse(
                [
                    'silhouette' => $silhouette
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

    public function store(SilhouetteStoreRequest $request)
    {
        try {
            $silhouette = new Silhouette();
            $silhouette->name = $request->input('name');
            $silhouette->description = $request->input('description');
            $silhouette->save();

            return $this->successResponse(
                [
                    'silhouette' => $silhouette
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

    public function update(SilhouetteUpdateRequest $request, $id)
    {
        try {
            $silhouette = Silhouette::findOrFail($id);
            $silhouette->name = $request->input('name');
            $silhouette->description = $request->input('description');
            $silhouette->save();

            return $this->successResponse(
                [
                    'silhouette' => $silhouette
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

    public function delete(SilhouetteDeleteRequest $request, $id)
    {
        try {
            $silhouette = Silhouette::findOrFail($id);
            $silhouette->delete();

            return $this->successResponse(
                [
                    'silhouette' => $silhouette
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

    public function restore(SilhouetteRestoreRequest $request, $id)
    {
        try {
            $silhouette = Silhouette::withTrashed()->findOrFail($id);
            $silhouette->restore();

            return $this->successResponse(
                [
                    'silhouette' => $silhouette
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
