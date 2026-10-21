<?php

namespace App\Http\Requests\Movement;

use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\Process;
use App\Models\Size;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MovementDeclineRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors(),
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        logger($this);

        return [
            'movement_origin_id' => ['required', 'exists:movements,id'],
            'movement_detail_id' => ['sometimes', 'exists:movement_details,id'],
            'origin' => ['required', 'array'],
            'origin.production_order_detail_id' => ['required', 'exists:production_order_details,id'],
            'origin.process' => ['required', 'array'],
            'origin.process.model_type' => ['required', 'in:'.implode(',', [Process::class])],
            'origin.process.model_id' => ['required_with:origin.process.model_type',
                match ($this->input('origin.process.model_type')) {
                    Process::class => 'exists:subitems,id,item_id,'.Process::ITEM_ID,
                    default => 'nullable'
                }],
            'destination' => ['required', 'array'],
            'destination.production_order_detail_id' => ['required', 'exists:production_order_details,id'],
            'destination.process' => ['required', 'array'],
            'destination.process.model_type' => ['required', 'in:'.implode(',', [Process::class])],
            'destination.process.model_id' => ['required_with:destination.process.model_type',
                match ($this->input('destination.process.model_type')) {
                    Process::class => 'exists:subitems,id,item_id,'.Process::ITEM_ID,
                    default => 'nullable'
                }],
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*.id' => ['nullable'],
            'quantities.*.size_id' => ['required', 'distinct', 'exists:subitems,id,item_id,'.Size::ITEM_ID],
            'quantities.*.quantity' => ['required', 'numeric'],
            'redistributions' => ['nullable', 'array'],
            'redistributions.*.output_size_id' => ['required', 'integer', 'exists:subitems,id,item_id,'.Size::ITEM_ID],
            'redistributions.*.input_size_id' => ['required', 'integer', 'exists:subitems,id,item_id,'.Size::ITEM_ID],
            'redistributions.*.quantity' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:'.implode(',', MovementDetail::statuses())],
            'observation' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'required_with' => 'Es obligatorio cuando se proporciona :values.',
            'exists' => 'No registrado en el sistema.',
            'boolean' => 'Debe ser un dato tipo sí/no.',
            'numeric' => 'Debe ser un dato numérico.',
            'integer' => 'Debe ser un número entero.',
            'min' => 'El valor debe ser mayor o igual a :min.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'distinct' => 'Debe ser un valor único.',
            'array' => 'Debe ser una lista o estructura válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'movement_origin_id' => 'Identificador del movimiento de origen',
            'movement_detail_id' => 'Identificador del detalle del movimiento',
            'origin.production_order_detail_id' => 'Detalle de la orden de producción de origen',
            'origin.process.model_type' => 'Tipo de modelo del proceso de origen',
            'origin.process.model_id' => 'Proceso de origen',
            'destination.production_order_detail_id' => 'Detalle de la orden de producción de destino',
            'destination.process.model_type' => 'Tipo de modelo del proceso de destino',
            'destination.process.model_id' => 'Proceso de destino',
            'quantities' => 'Cantidades del traslado',
            'quantities.*.size_id' => 'Talla',
            'quantities.*.quantity' => 'Cantidad',
            'redistributions.*.output_size_id' => 'Talla de salida',
            'redistributions.*.input_size_id' => 'Talla de entrada',
            'redistributions.*.quantity' => 'Cantidad redistribuida',
            'observation' => 'Observación',
            'status' => 'Estado del movimiento',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $origin_id = $this->input('origin.process.model_id');
            $destination_id = $this->input('destination.process.model_id');

            if (
                $origin_id &&
                $destination_id &&
                is_numeric($origin_id) &&
                is_numeric($destination_id)
            ) {
                $origin_id = (int) $origin_id;
                $destination_id = (int) $destination_id;

                if ($origin_id !== $destination_id) {
                    $is_related_process = Process::where('id', $origin_id)
                        ->where(function ($query) use ($destination_id) {
                            $query
                                ->whereHas('after_processes', function ($subquery) use ($destination_id) {
                                    $subquery->where('id', $destination_id);
                                })
                                ->orWhereHas('before_processes', function ($subquery) use ($destination_id) {
                                    $subquery->where('id', $destination_id);
                                });
                        })
                        ->exists();

                    if (! $is_related_process) {
                        $validator->errors()->add(
                            'destination.process.model_id',
                            'El proceso destino debe ser el mismo proceso o un proceso anterior o siguiente del proceso origen.'
                        );
                    }
                }
            }

            if ($this->filled('movement_detail_id') && is_array($this->input('quantities'))) {
                $movement_detail = MovementDetail::with(
                    'movement_detail_quantities'
                )->find($this->input('movement_detail_id'));

                if ($movement_detail) {

                    if (
                        (int) $movement_detail->movement_id !==
                        (int) $this->input('movement_origin_id')
                    ) {
                        $validator->errors()->add(
                            'movement_detail_id',
                            'El detalle no pertenece al movimiento indicado.'
                        );
                    }

                    if ($movement_detail->status !== MovementDetail::PENDING) {
                        $validator->errors()->add(
                            'status',
                            'Solo se pueden aprobar movimientos que estén pendientes.'
                        );
                    }

                    $original_quantities = $movement_detail
                        ->movement_detail_quantities
                        ->keyBy('id');

                    $original_total = $movement_detail
                        ->movement_detail_quantities
                        ->sum('quantity');

                    $received_quantities = collect(
                        $this->input('quantities')
                    );

                    $received_total = $received_quantities->sum(
                        function ($quantity) {
                            return (int) ($quantity['quantity'] ?? 0);
                        }
                    );

                    if ($received_total > $original_total) {
                        $validator->errors()->add(
                            'quantities',
                            "La cantidad total aceptada ({$received_total}) no puede superar las unidades enviadas ({$original_total})."
                        );
                    }

                    foreach ($received_quantities as $index => $quantity) {
                        $quantity_id = $quantity['id'] ?? null;
                        $size_id = $quantity['size_id'] ?? null;

                        if ($quantity_id !== null && $quantity_id !== '') {
                            $original_quantity = $original_quantities->get(
                                $quantity_id
                            );

                            if (
                                ! $original_quantity ||
                                (int) $original_quantity->size_id !==
                                (int) $size_id
                            ) {
                                $validator->errors()->add(
                                    "quantities.{$index}.id",
                                    'La cantidad indicada no pertenece a este movimiento o no corresponde a la talla enviada.'
                                );
                            }
                        }
                    }
                }
            }

            $redistributions = $this->input('redistributions', []);

            if (! is_array($redistributions)) {
                $redistributions = [];
            }

            foreach ($redistributions as $index => $redistribution) {
                $output_size_id = $redistribution['output_size_id'] ?? null;
                $input_size_id = $redistribution['input_size_id'] ?? null;

                if (
                    $output_size_id &&
                    $input_size_id &&
                    (int) $output_size_id === (int) $input_size_id
                ) {
                    $validator->errors()->add(
                        "redistributions.{$index}.input_size_id",
                        'La talla origen y la talla destino deben ser diferentes.'
                    );
                }
            }

            if (! empty($redistributions)) {

                $movement = Movement::with([
                    'movement_details_input' => [
                        'movement_detail_quantities',
                    ],
                    'movement_details_output' => [
                        'movement_detail_quantities',
                        'movement_detail_redistributions',
                    ],
                ])->find($this->input('movement_origin_id'));

                if ($movement) {

                    $details_inputs = $movement->movement_details_input
                        ->where('status', MovementDetail::APPROVED);

                    $details_outputs = $movement->movement_details_output
                        ->whereIn('status', [
                            MovementDetail::PENDING,
                            MovementDetail::APPROVED,
                        ]);

                    $original_by_size_inputs = $details_inputs
                        ->flatMap(function ($movement_detail) {
                            return $movement_detail->movement_detail_quantities;
                        })
                        ->groupBy('size_id')
                        ->map(function ($items) {
                            return $items->sum('quantity');
                        });

                    $received_by_size = $details_outputs
                        ->flatMap(function ($movement_detail) {
                            return $movement_detail->movement_detail_redistributions;
                        })
                        ->groupBy('input_size_id')
                        ->map(function ($items) {
                            return $items->sum('quantity');
                        });

                    $donated_by_size = $details_outputs
                        ->flatMap(function ($movement_detail) {
                            return $movement_detail->movement_detail_redistributions;
                        })
                        ->groupBy('output_size_id')
                        ->map(function ($items) {
                            return $items->sum('quantity');
                        });

                    $donating_now = collect($redistributions)
                        ->groupBy('output_size_id')
                        ->map(function ($items) {
                            return $items->sum('quantity');
                        });

                    foreach ($donating_now as $size_id => $quantity_to_donate) {
                        $original = $original_by_size_inputs->get($size_id, 0);
                        $received = $received_by_size->get($size_id, 0);
                        $donated = $donated_by_size->get($size_id, 0);

                        $available = $original + $received - $donated;

                        if ($quantity_to_donate > $available) {
                            $validator->errors()->add(
                                'redistributions',
                                "La talla {$size_id} no tiene suficientes unidades disponibles para donar. Disponible: {$available}. Solicitado: {$quantity_to_donate}."
                            );
                        }
                    }
                }
            }
        });
    }
}
