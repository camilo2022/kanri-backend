<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movement\MovementAllRequest;
use App\Http\Resources\Movement\MovementCollection;
use App\Models\Movement;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class MovementController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(MovementAllRequest $request)
    {
        try {
            $movements = Movement::with(['model', 'production_order_detail' => ['production_order_detail_quantities', 'production_order', 'model' => ['trademark', 'subcategory' => ['category'], 'technical_sheet' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type']]]])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $movements = $movements->paginate($request->integer('per_page', $movements->count()));

            return $this->successResponse(
                new MovementCollection($movements),
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
}
