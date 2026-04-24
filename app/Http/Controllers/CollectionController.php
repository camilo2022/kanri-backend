<?php

namespace App\Http\Controllers;

use App\Http\Requests\Collection\CollectionAllRequest;
use App\Http\Requests\Collection\CollectionDeleteRequest;
use App\Http\Requests\Collection\CollectionFindRequest;
use App\Http\Requests\Collection\CollectionRestoreRequest;
use App\Http\Requests\Collection\CollectionStoreRequest;
use App\Http\Requests\Collection\CollectionUpdateRequest;
use App\Http\Resources\Collection\CollectionCollection;
use App\Models\Collection;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(CollectionAllRequest $request)
    {
        try {
            $collections = Collection::with(['item'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $collections = $collections->paginate($request->integer('per_page', $collections->count()));

            return $this->successResponse(
                new CollectionCollection($collections),
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

    public function find(CollectionFindRequest $request, $id)
    {
        try {
            $collection = Collection::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'collection' => $collection
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

    public function store(CollectionStoreRequest $request)
    {
        try {
            $collection = new Collection();
            $collection->name = $request->input('name');
            $collection->description = $request->input('description');

            $collection->save();

            return $this->successResponse(
                [
                    'collection' => $collection
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

    public function update(CollectionUpdateRequest $request, $id)
    {
        try {
            $collection = Collection::findOrFail($id);
            $collection->name = $request->input('name');
            $collection->description = $request->input('description');

            $collection->save();

            return $this->successResponse(
                [
                    'collection' => $collection
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

    public function delete(CollectionDeleteRequest $request, $id)
    {
        try {
            $collection = Collection::findOrFail($id);
            $collection->delete();

            return $this->successResponse(
                [
                    'collection' => $collection
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

    public function restore(CollectionRestoreRequest $request, $id)
    {
        try {
            $collection = Collection::withTrashed()->findOrFail($id);
            $collection->restore();

            return $this->successResponse(
                [
                    'collection' => $collection
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
