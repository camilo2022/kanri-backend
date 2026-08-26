<?php

namespace App\Http\Controllers;

use App\Http\Requests\Management\Collection\CollectionFindRequest;
use App\Http\Requests\Management\TechnicalSheet\TechnicalSheetSaveRequest;
use App\Http\Resources\Management\Collection\CollectionResource;
use App\Http\Resources\Management\Collection\TechnicalSheetResource;
use App\Models\Collection;
use App\Models\Product;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;

class ManagementCollectionController extends Controller
{
    use ApiMessage, ApiResponser;

    public function find(CollectionFindRequest $request, $id)
    {
        try {
            $collection = Collection::with(['technical_sheets' => ['collection', 'product' => ['trademark', 'subcategory' => ['category']], 'supplies' => ['supply_type'], 'technical_sheet_details', 'garment_type', 'wash_tone', 'boot_type', 'photo_d', 'photo_t']])
                ->findOrFail($id);

            return $this->successResponse(
                new CollectionResource($collection),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function save(TechnicalSheetSaveRequest $request)
    {
        $product = Product::updateOrCreate(
            ['id' => $request->integer('product.id')],
            [
                'code' => $request->input('product.code'),
                'subcategory_id' => $request->integer('product.subcategory_id'),
                'trademark_id' => $request->integer('product.trademark_id'),
            ]
        );

        /*$technical_sheet = TechnicalSheet::updateOrCreate(
            [
                'id' => $request->integer('id')
                'code' =>  $result[0]->code
            ]
            [
                'product_id' => $product->id,
                'collection_id' => $request->integer('collection_id'),
                'garment_type_id' => $request->integer('garment_type_id'),
                'wash_tone_id' => $request->integer('wash_tone_id'),
                'boot_type_id' => $request->integer('boot_type_id'),
                'observation' => $request->input('observation'),
                'status' => $request->input('status')
            ]
        );*/

        $technical_sheet = TechnicalSheet::find($request->integer('id'));

        if (! $technical_sheet) {
            $result = DB::select('CALL generate_technical_sheet_code()');

            $code = $result[0]->code;

            $technical_sheet = TechnicalSheet::create([
                'code' => $code,
                'product_id' => $product->id,
                'collection_id' => $request->integer('collection_id'),
                'garment_type_id' => $request->integer('garment_type_id'),
                'wash_tone_id' => $request->integer('wash_tone_id'),
                'boot_type_id' => $request->integer('boot_type_id'),
                'observation' => $request->input('observation'),
                'status' => $request->input('status'),
            ]);
        } else {
            $technical_sheet->update([
                'product_id' => $product->id,
                'collection_id' => $request->integer('collection_id'),
                'garment_type_id' => $request->integer('garment_type_id'),
                'wash_tone_id' => $request->integer('wash_tone_id'),
                'boot_type_id' => $request->integer('boot_type_id'),
                'observation' => $request->input('observation'),
                'status' => $request->input('status'),
            ]);
        }

        foreach ($request->input('technical_sheet_details', []) as $technical_sheet_detail) {
            TechnicalSheetDetail::updateOrCreate(
                [
                    'technical_sheet_id' => $technical_sheet->id,
                    'model_type' => $technical_sheet_detail['model_type'],
                    'model_id' => $technical_sheet_detail['model_id'],
                ],
                [
                    'status' => $technical_sheet_detail['status'],
                    'settings' => $technical_sheet_detail['settings'] ?? (object) [],
                ]
            );
        }

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

        $technical_sheet->auditSync('supplies', $request->input('supplies', []));

        $technical_sheet->load(['collection', 'product' => ['trademark', 'subcategory' => ['category']], 'supplies' => ['supply_type'], 'technical_sheet_details', 'garment_type', 'wash_tone', 'boot_type', 'photo_d', 'photo_t']);

        return $this->successResponse(
            new TechnicalSheetResource($technical_sheet),
            $this->getMessage('Success'),
            200
        );
    }
}
