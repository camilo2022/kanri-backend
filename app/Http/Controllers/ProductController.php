<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ProductAllRequest;
use App\Http\Requests\Product\ProductFindRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(ProductAllRequest $request)
    {
        try {
            $products = Product::with(['subcategory' => ['category'], 'trademark' => ['group'], 'technical_sheet' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type', 'technical_sheet_details', 'photo_d', 'photo_t']])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $products = $products->paginate($request->integer('per_page', $products->count()));

            return $this->successResponse(
                new ProductCollection($products),
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

    public function find(ProductFindRequest $request, $id)
    {
        try {
            $piece = Product::with(['trademark' => ['group'], 'subcategory' => ['category']])
                ->findOrFail($id);

            return $this->successResponse(
                new ProductResource($piece),
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

    public function store(ProductStoreRequest $request)
    {
        try {
            $product = new Product();
            $product->code = $request->input('code');
            $product->trademark_id = $request->input('trademark_id');
            $product->subcategory_id = $request->input('subcategory_id');
            $product->save();

            return $this->successResponse(
                new ProductResource($product),
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

    public function update(ProductUpdateRequest $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->code = $request->input('code');
            $product->trademark_id = $request->input('trademark_id');
            $product->subcategory_id = $request->input('subcategory_id');

            $product->save();


            return $this->successResponse(
                [
                    'product' => $product
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
}
