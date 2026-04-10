<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categorization\Category\CategoryAllRequest;
use App\Http\Requests\Categorization\Category\CategoryDeleteRequest;
use App\Http\Requests\Categorization\Category\CategoryFindRequest;
use App\Http\Requests\Categorization\Category\CategoryRestoreRequest;
use App\Http\Requests\Categorization\Category\CategoryStoreRequest;
use App\Http\Requests\Categorization\Category\CategoryUpdateRequest;
use App\Http\Resources\Categorization\Category\CategoryCollection;
use App\Models\Category;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class CategoryController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(CategoryAllRequest $request)
    {
        try {
            $categories = Category::with(['subcategories'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $categories = $categories->paginate($request->integer('per_page', $categories->count()));

            return $this->successResponse(
                new CategoryCollection($categories),
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

    public function find(CategoryFindRequest $request, $id)
    {
        try {
            $category = Category::with(['subcategories'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'category' => $category
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

    public function store(CategoryStoreRequest $request)
    {
        try {
            $category = new Category();
            $category->name = $request->input('name');
            $category->description = $request->input('description');
            $category->save();

            return $this->successResponse(
                [
                    'category' => $category
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

    public function update(CategoryUpdateRequest $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->name = $request->input('name');
            $category->description = $request->input('description');
            $category->save();

            return $this->successResponse(
                [
                    'category' => $category
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

    public function delete(CategoryDeleteRequest $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return $this->successResponse(
                [
                    'category' => $category
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

    public function restore(CategoryRestoreRequest $request, $id)
    {
        try {
            $category = Category::withTrashed()->findOrFail($id);
            $category->restore();

            return $this->successResponse(
                [
                    'category' => $category
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
