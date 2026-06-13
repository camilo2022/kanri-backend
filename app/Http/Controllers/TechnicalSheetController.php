<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ProductAllRequest;
use App\Http\Requests\Product\ProductFindRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetFindRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetStoreRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetUpdateRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\TechnicalSheet\TechnicalSheetResource;
use App\Models\Product;
use App\Models\TechnicalSheet;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TechnicalSheetController extends Controller
{
    use ApiMessage, ApiResponser;

    /*
    public function all(ProductAllRequest $request)
    {
        try {
            $products = Product::with(['subcategory' => ['category'], 'trademark' => ['group'], 'technical_sheet' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type', 'technical_sheet_details']])
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
*/
    public function find(TechnicalSheetFindRequest $request)
    {
        try {
            $technical_sheet = TechnicalSheet::with([
                'collection',
                'subgroup',
                'color',
                'waistband_type',
                'garment_type',
                'wash_tone',
                'boot_type',
                'yoke_type',
                'back_type',
                'technical_sheet_details',
                'pattern_maker' => ['person', 'position']
            ])
                ->when($request->filled('id'), function ($query) use ($request) {
                    return $query->findOrFail($request->integer('id'));
                })
                ->when($request->filled('product_id'), function ($query) use ($request) {
                    return $query->where('product_id', $request->integer('product_id'));
                })
                ->when($request->filled('uuid'), function ($query) use ($request) {
                    return $query->where('uuid', $request->integer('uuid'));
                })->firstOrFail();

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
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

    public function store(TechnicalSheetStoreRequest $request)
    {
        try {
            $technical_sheet = new TechnicalSheet();

            $technical_sheet->uuid = Str::uuid();
            $technical_sheet->date = $request->input('date');
            $technical_sheet->product_id = $request->integer('product_id');
            $technical_sheet->collection_id = $request->integer('collection_id');
            $technical_sheet->garment_type_id = $request->integer('garment_type_id');
            $technical_sheet->subgroup_id = $request->integer('subgroup_id');
            $technical_sheet->wash_tone_id = $request->integer('wash_tone_id');
            $technical_sheet->color_id = $request->integer('color_id');
            $technical_sheet->waistband_type_id = $request->integer('waistband_type_id');
            $technical_sheet->measure_of_waistband = $request->integer('measure_of_waistband');
            $technical_sheet->boot_type_id = $request->integer('boot_type_id');
            $technical_sheet->yoke_type_id = $request->integer('yoke_type_id');
            $technical_sheet->back_type_id = $request->integer('back_type_id');
            $technical_sheet->physical_sample = $request->integer('physical_sample');
            $technical_sheet->number_of_buttons = $request->integer('number_of_buttons');
            $technical_sheet->pattern_maker_id = $request->integer('pattern_maker_id');
            $technical_sheet->description = $request->input('description');
            $technical_sheet->observation = $request->input('observation');

            $technical_sheet->save();

            if ($request->hasFile('photo_d')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('photo_d.file'),
                    $request->integer('photo_d.photo_type_id'),
                    $request->integer('photo_d.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            }

            if ($request->hasFile('photo_t')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('photo_t.file'),
                    $request->integer('photo_t.photo_type_id'),
                    $request->integer('photo_t.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            }

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
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

    public function update(TechnicalSheetUpdateRequest $request, $id)
    {
        try {
            $technical_sheet = TechnicalSheet::findOrFail($id);

            $technical_sheet->date = $request->input('date');
            $technical_sheet->product_id = $request->integer('product_id');
            $technical_sheet->collection_id = $request->integer('collection_id');
            $technical_sheet->garment_type_id = $request->integer('garment_type_id');
            $technical_sheet->subgroup_id = $request->integer('subgroup_id');
            $technical_sheet->wash_tone_id = $request->integer('wash_tone_id');
            $technical_sheet->color_id = $request->integer('color_id');
            $technical_sheet->waistband_type_id = $request->integer('waistband_type_id');
            $technical_sheet->measure_of_waistband = $request->integer('measure_of_waistband');
            $technical_sheet->boot_type_id = $request->integer('boot_type_id');
            $technical_sheet->yoke_type_id = $request->integer('yoke_type_id');
            $technical_sheet->back_type_id = $request->integer('back_type_id');
            $technical_sheet->physical_sample = $request->integer('physical_sample');
            $technical_sheet->number_of_buttons = $request->integer('number_of_buttons');
            $technical_sheet->description = $request->input('description');
            $technical_sheet->observation = $request->input('observation');
            $technical_sheet->pattern_maker_id = $request->integer('pattern_maker_id');

            $technical_sheet->save();

            if ($request->hasFile('photo_d')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('photo_d.file'),
                    $request->integer('photo_d.photo_type_id'),
                    $request->integer('photo_d.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            }

            if ($request->hasFile('photo_t')) {
                app(FileService::class)->save(
                    $technical_sheet,
                    $request->file('photo_t.file'),
                    $request->integer('photo_t.photo_type_id'),
                    $request->integer('photo_t.photo_subtype_id'),
                    'public',
                    'technical_sheet'
                );
            }

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
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
