<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movement\MovementAllRequest;
use App\Http\Requests\Movement\MovementApproveRequest;
use App\Http\Requests\Movement\MovementCancelRequest;
use App\Http\Requests\Movement\MovementDeclineRequest;
use App\Http\Requests\Movement\MovementFindRequest;
use App\Http\Requests\Movement\MovementSaveRequest;
use App\Http\Resources\Movement\MovementCollection;
use App\Http\Resources\Movement\MovementResource;
use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\MovementDetailQuantity;
use App\Models\MovementDetailRedistribution;
use App\Models\Process;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Builder;

class MovementController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(MovementAllRequest $request)
    {
        try {
            $movements = Movement::with(['movement_details_input' => ['movement_detail_quantities'], 'movement_details_output' => ['movement_detail_quantities'], 'model', 'production_order_detail' => ['production_order_detail_quantities', 'production_order' => ['technical_sheet' => ['collection']], 'model' => ['trademark', 'subcategory' => ['category'], 'technical_sheet' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type']]]])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
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

    public function find(MovementFindRequest $request, $id)
    {
        try {
            $movement = Movement::with(['movement_details_output' => ['movement' => ['model', 'production_order_detail' => ['model', 'production_order']], 'model' => ['model', 'production_order_detail' => ['model' => ['trademark'], 'production_order' => ['technical_sheet' => ['collection']]]], 'movement_detail_redistributions', 'movement_detail_quantities', 'user_created' => ['employee' => ['person']], 'user_updated' => ['employee' => ['person']]],  'movement_details_input' => ['movement' => ['model', 'production_order_detail' => ['model', 'production_order']], 'model' => ['model', 'production_order_detail' => ['model' => ['trademark'], 'production_order' => ['technical_sheet' => ['collection']]]], 'movement_detail_redistributions', 'movement_detail_quantities', 'user_created' => ['employee' => ['person']], 'user_updated' => ['employee' => ['person']]], 'model' => ['after_processes', 'before_processes'], 'production_order_detail' => ['production_order_detail_quantities', 'production_order' => ['technical_sheet' => ['collection']], 'model' => ['trademark' => ['sizes'], 'subcategory' => ['category'], 'technical_sheet' => ['collection', 'subgroup', 'color', 'waistband_type', 'garment_type', 'wash_tone', 'boot_type', 'yoke_type', 'back_type']]]])
                ->findOrFail($id);

            return $this->successResponse(
                new MovementResource($movement),
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

    public function save(MovementSaveRequest $request)
    {
        try {
            if ($request->filled('movement_detail_id')) {
                $movement_detail = MovementDetail::findOrFail($request->input('movement_detail_id'));

                $movement_detail->update([
                    'observation' => $request->input('observation'),
                ]);

                $requested_quantities = collect(
                    $request->input('quantities', [])
                );

                foreach ($movement_detail->movement_detail_quantities as $quantity) {
                    $requested_quantity = $requested_quantities
                        ->firstWhere('id', $quantity->id);

                    if (! $requested_quantity) {
                        continue;
                    }

                    $difference = max(0, (int) $quantity->quantity - (int) $requested_quantity['quantity']);

                    if ($difference === 0) {
                        continue;
                    }

                    $redistributions = MovementDetailRedistribution::where('movement_detail_id', $movement_detail->id)
                        ->where('input_size_id', $quantity->size_id)
                        ->orderBy('id')
                        ->get();

                    foreach ($redistributions as $redistribution) {
                        if ($difference <= 0) {
                            break;
                        }

                        $aux = max(0, (int) $redistribution->quantity - $difference);

                        $returned = (int) $redistribution->quantity - $aux;

                        $redistribution->update([
                            'quantity' => $aux,
                        ]);

                        $difference -= $returned;
                    }
                }

                foreach ($request->input('quantities', []) as $quantity) {
                    MovementDetailQuantity::updateOrCreate(
                        [
                            'id' => $quantity['id'] ?? null,
                            'movement_detail_id' => $movement_detail->id,
                            'size_id' => $quantity['size_id'],
                        ],
                        [
                            'quantity' => $quantity['quantity'],
                        ]
                    );
                }

            } else {
                $origin_movement = Movement::where('production_order_detail_id', $request->input('origin.production_order_detail_id'))
                    ->whereHasMorph(
                        'model',
                        [Process::class],
                        function (Builder $query) use ($request) {
                            $query->where(
                                'id',
                                $request->input('origin.process.model_id')
                            );
                        }
                    )
                    ->firstOrFail();

                $destination_movement = Movement::firstOrCreate(
                    [
                        'production_order_detail_id' => $request->input(
                            'destination.production_order_detail_id'
                        ),
                        'model_type' => $request->input(
                            'destination.process.model_type'
                        ),
                        'model_id' => $request->input(
                            'destination.process.model_id'
                        ),
                    ],
                    [
                        'status' => 'Ingreso',
                    ]
                );

                $movement_detail = MovementDetail::create([
                    'movement_id' => $destination_movement->id,
                    'model_type' => Movement::class,
                    'model_id' => $origin_movement->id,
                    'status' => 'Pendiente',
                    'observation' => $request->input('observation'),
                ]);

                foreach ($request->input('quantities', []) as $quantity) {
                    MovementDetailQuantity::create([
                        'movement_detail_id' => $movement_detail->id,
                        'size_id' => $quantity['size_id'],
                        'quantity' => $quantity['quantity'],
                    ]);
                }

                foreach ($request->input('redistributions', []) as $redistribution) {
                    MovementDetailRedistribution::create([
                        'movement_detail_id' => $movement_detail->id,
                        'output_size_id' => $redistribution['output_size_id'],
                        'input_size_id' => $redistribution['input_size_id'],
                        'quantity' => $redistribution['quantity'],
                    ]);
                }
            }

            $movement_detail->load([
                'movement' => ['model'],
                'model' => [
                    'model',
                    'production_order_detail' => [
                        'model',
                        'production_order' => [
                            'technical_sheet' => ['collection'],
                        ],
                    ],
                ],
                'movement_detail_quantities',
                'movement_detail_redistributions',
                'user_created' => ['employee' => ['person']],
            ]);

            return $this->successResponse(
                new MovementResource($movement_detail),
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

    public function approve(MovementApproveRequest $request)
    {
        try {
            $movement_detail = MovementDetail::findOrFail($request->input('movement_detail_id'));

            $requested_quantities = collect($request->input('quantities', []));

            foreach ($movement_detail->movement_detail_quantities as $movement_detail_quantity) {
                $requested_quantity = $requested_quantities
                    ->firstWhere('id', $movement_detail_quantity->id);

                if (! $requested_quantity) continue;

                $difference = max(0, (int) $movement_detail_quantity->quantity - (int) $requested_quantity['quantity']);

                if ($difference === 0) continue;

                $redistributions = MovementDetailRedistribution::where('movement_detail_id', $movement_detail->id)
                    ->where('input_size_id', $movement_detail_quantity->size_id)
                    ->orderBy('id')
                    ->get();

                foreach ($redistributions as $redistribution) {
                    if ($difference <= 0) break;

                    $aux = max(0, (int) $redistribution->quantity - $difference);

                    $returned = (int) $redistribution->quantity - $aux;

                    $redistribution->update([
                        'quantity' => $aux,
                    ]);

                    $difference -= $returned;
                }
            }

            foreach ($request->input('quantities', []) as $quantity) {
                MovementDetailQuantity::updateOrCreate(
                    [
                        'id' => $quantity['id'] ?? null,
                        'movement_detail_id' => $movement_detail->id,
                        'size_id' => $quantity['size_id'],
                    ],
                    [
                        'quantity' => $quantity['quantity'],
                    ]
                );
            }

            $movement_detail->update([
                'status' => 'Aprobado',
                'observation' => $request->input('observation'),
            ]);

            $movement_detail->load([
                'movement' => ['model'],
                'model' => [
                    'model',
                    'production_order_detail' => [
                        'production_order_detail_quantities',
                        'model',
                        'production_order' => [
                            'technical_sheet' => ['collection'],
                        ],
                    ],
                    'movement_details_input' => ['movement_detail_quantities'],
                    'movement_details_output' => ['movement_detail_quantities']
                ],
                'movement_detail_quantities',
                'movement_detail_redistributions',
                'user_created' => ['employee' => ['person']],
            ]);


            return $this->successResponse(
                new MovementResource($movement_detail),
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

    public function decline(MovementDeclineRequest $request)
    {
        try {
            $movement_detail = MovementDetail::findOrFail(
                $request->input('movement_detail_id')
            );

            $movement_detail->update([
                'status' => 'Rechazado',
                'observation' => $request->input('observation'),
            ]);

            $movement_detail->load([
                'movement' => ['model'],
                'model' => [
                    'model',
                    'production_order_detail' => [
                        'model',
                        'production_order' => [
                            'technical_sheet' => ['collection'],
                        ],
                    ],
                ],
                'movement_detail_quantities',
                'movement_detail_redistributions',
                'user_created' => ['employee' => ['person']],
            ]);

            return $this->successResponse(
                new MovementResource($movement_detail),
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

    public function cancel(MovementCancelRequest $request)
    {
        try {
            $movement_detail = MovementDetail::findOrFail(
                $request->input('movement_detail_id')
            );

            $movement_detail->update([
                'status' => 'Cancelado',
                'observation' => $request->input('observation'),
            ]);

            $movement_detail->load([
                'movement' => ['model'],
                'model' => [
                    'model',
                    'production_order_detail' => [
                        'model',
                        'production_order' => [
                            'technical_sheet' => ['collection'],
                        ],
                    ],
                ],
                'movement_detail_quantities',
                'movement_detail_redistributions',
                'user_created' => ['employee' => ['person']],
            ]);

            return $this->successResponse(
                new MovementResource($movement_detail),
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
