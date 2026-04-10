<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categorization\Subcategory\SubcategoryAllRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryDeleteRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryFindRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryRestoreRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryStoreRequest;
use App\Http\Requests\Categorization\Subcategory\SubcategoryUpdateRequest;
use App\Http\Resources\Categorization\Subcategory\SubcategoryCollection;
use App\Models\Subcategory;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class SubcategoryController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(SubcategoryAllRequest $request, $category_id)
    {
        try {
            $subcategories = Subcategory::with(['category'])
                ->whereHas('category', fn($r) => $r->where('id', $category_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $subcategories = $subcategories->paginate($request->integer('per_page', $subcategories->count()));

            return $this->successResponse(
                new SubcategoryCollection($subcategories),
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

    public function find(SubcategoryFindRequest $request, $id)
    {
        try {
            $subcategory = Subcategory::with(['category'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'subcategory' => $subcategory
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

    public function store(SubcategoryStoreRequest $request)
    {
        try {
            $subcategory = new Subcategory();
            $subcategory->name = $request->input('name');
            $subcategory->description = $request->input('description');
            $subcategory->save();

            $subcategory->category()->sync([$request->integer('category_id')]);

            return $this->successResponse(
                [
                    'subcategory' => $subcategory
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

    public function update(SubcategoryUpdateRequest $request, $id)
    {
        try {
            $subcategory = Subcategory::findOrFail($id);
            $subcategory->name = $request->input('name');
            $subcategory->description = $request->input('description');
            $subcategory->save();

            $subcategory->category()->sync([$request->integer('category_id')]);

            return $this->successResponse(
                [
                    'subcategory' => $subcategory
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

    public function delete(SubcategoryDeleteRequest $request, $id)
    {
        try {
            $subcategory = Subcategory::findOrFail($id);
            $subcategory->delete();

            return $this->successResponse(
                [
                    'subcategory' => $subcategory
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

    public function restore(SubcategoryRestoreRequest $request, $id)
    {
        try {
            $subcategory = Subcategory::withTrashed()->findOrFail($id);
            $subcategory->restore();

            return $this->successResponse(
                [
                    'subcategory' => $subcategory
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
