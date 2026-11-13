<?php

namespace App\Http\Controllers;

use App\Http\Requests\Movement\ProductionOrderDetail\ProductionOrderDetailAllRequest;
use App\Http\Resources\Movement\ProductionOrderDetail\ProductionOrderDetailCollection;
use App\Models\Product;
use App\Models\ProductionOrderDetail;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductionOrderDetailController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/movements/production_order_details/all",
     *     tags={"Movements › Production Order Details"},
     *     summary="Listar curvas de lotes para elegir el destino de un traslado",
     *     description="Alimenta el selector de 'otro lote' al trasladar unidades. Devuelve detalles de órdenes de producción (sin paginar), con su orden y su producto (marca con tallas).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="production_order_detail_id",
     *         in="query",
     *         required=false,
     *         description="Curva actual: se excluye del resultado.",
     *         @OA\Schema(type="integer", example=40122)
     *     ),
     *     @OA\Parameter(
     *         name="has_destination",
     *         in="query",
     *         required=false,
     *         description="`true`/`1`: solo detalles con destino (curvas NACIONAL, MEDELLIN o STARA). Se convierte antes de validar, así que en la práctica es opcional.",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de curvas cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="production_order_details",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=40130),
     *                         @OA\Property(property="production_order_id", type="integer", example=1315),
     *                         @OA\Property(property="destination", type="string", nullable=true, enum={"NACIONAL", "MEDELLIN", "STARA"}, example="MEDELLIN"),
     *                         @OA\Property(property="model", type="object", additionalProperties=true, description="Producto de la curva, con `trademark.sizes`."),
     *                         @OA\Property(property="production_order", type="object", additionalProperties=true, description="Orden de producción (lote) a la que pertenece."),
     *                         @OA\Property(property="created_at", type="string", example="2026-10-22 11:00:00"),
     *                         @OA\Property(property="updated_at", type="string", example="2026-10-22 11:00:00"),
     *                         @OA\Property(property="deleted_at", nullable=true, example=null)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Parámetros inválidos.",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ValidationErrorResponse",
     *             example={"message": "Error de validación.", "attributes": {"production_order_detail_id": "Identificado del detalle de la orden de producción."}, "errors": {"production_order_detail_id": {"No hay ningún registro."}}}
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function all(ProductionOrderDetailAllRequest $request)
    {
        try {
            $production_order_details = ProductionOrderDetail::with(['production_order', 'model' => fn (MorphTo $morph) => $morph->morphWith([Product::class => ['trademark' => ['sizes']]])])
                ->when($request->filled('production_order_detail_id'),
                    function ($query) use ($request) {
                        return $query->where('id', '!=', $request->input('production_order_detail_id'));
                    }
                )
                ->when($request->boolean('has_destination'),
                    function ($query) {
                        return $query->whereNotNull('destination');
                    }
                )
                ->get();

            return $this->successResponse(
                new ProductionOrderDetailCollection($production_order_details),
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
