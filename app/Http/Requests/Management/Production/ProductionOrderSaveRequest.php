<?php

namespace App\Http\Requests\Management\Production;

use App\Models\Color;
use App\Models\Piece;
use App\Models\Process;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Supplier;
use App\Models\Supply;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductionOrderSaveRequest extends FormRequest
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
        return [
            'id' => ['nullable', 'exists:production_orders,id'],
            'technical_sheet_id' => ['required', 'exists:technical_sheets,id'],
            'cut' => ['required', 'string', 'uppercase', 'max:2'],
            'status' => ['required', 'in:'.implode(',', [ProductionOrder::PENDING, ProductionOrder::APPROVED, ProductionOrder::CANCELLED])],
            'production_place' => ['required', 'string', 'in:BLESS,SATELITE'],
            'supplier_id' => ['nullable', 'required_if:production_place,SATELITE', 'exists:subitems,id,item_id,'.Supplier::ITEM_ID],
            'priority' => ['required', 'in:'.implode(',', ProductionOrder::priorities())],
            'reasigned_curve' => ['boolean'],
            'reassigned_origins' => ['nullable', 'required_if:reasigned_curve,true', 'array', 'min:1'],
            'reassigned_origins.*.production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'reassigned_origins.*.selected_rows' => ['required', 'array', 'min:1'],
            'reassigned_origins.*.selected_rows.*' => ['string', 'in:NACIONAL,MEDELLIN,STARA'],
            'reassigned_origins.*.reassigned_details' => ['required', 'array', 'min:1'],
            'production_order_details' => ['required', 'array', 'min:1'],
            'production_order_details.*.model_type' => ['required',  'in:'.implode(',', [Product::class, Supply::class, Color::class, Process::class, Piece::class, ProductionOrder::class])],
            'production_order_details.*.model_id' => ['nullable', 'numeric'],
            'production_order_details.*.destination' => ['nullable', 'string', 'in:NACIONAL,MEDELLIN,STARA'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'date_format' => 'Formato inválido. Permitido :format.',
            'date.before_or_equal' => 'No puede ser posterior a :date.',
            'exists' => 'No registrado en el sistema.',
            'boolean' => 'Debe ser un dato tipo si/no',
            'numeric' => 'Debe ser un dato númerico',
            'min' => 'El valor debe ser mayor o igual a :min',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'integer' => 'Debe ser un dato númerico',
            'distinct' => 'Debe ser un valor unico',
        ];
    }

    public function attributes(): array
    {
        return [
            'technical_sheet_id' => 'Identificador de la ficha técnica',
            'efficiency' => 'Eficiencia',
            'cut' => 'Identificador de lote',
            'fabric_id' => 'Identificador de la tela',
            'width' => 'Ancho de la tela',
            'pocket_fabric' => 'Indicador de uso de tela de bolsillo',
            'observation' => 'Observación',
            'status' => 'Estado de la orden de producción',
        ];
    }

    private function getNextCut(string $cut): string
    {
        $cut = strtoupper(trim($cut));

        return chr(ord($cut) + 1);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->id === null) {
                $current_cut = strtoupper(trim($this->cut ?? ''));

                if ($this->technical_sheet_id && $current_cut) {
                    $last_production_order = ProductionOrder::where(
                        'technical_sheet_id',
                        $this->technical_sheet_id
                    )
                        ->when($this->id, function ($query) {
                            $query->where('id', '!=', $this->id);
                        })
                        ->orderByDesc('id')
                        ->first();

                    if ($last_production_order) {
                        $lastCut = strtoupper(trim($last_production_order->cut));
                        $expectedCut = $this->getNextCut($lastCut);
                        if ($current_cut !== $expectedCut) {
                            $validator->errors()->add(
                                'cut',
                                'No es consecutivo.'
                            );
                        }
                    } else {
                        if ($current_cut !== 'A') {
                            $validator->errors()->add(
                                'cut',
                                'El primer corte de esta ficha técnica debe ser A.'
                            );
                        }
                    }
                }
            }

            $details = $this->production_order_details ?? [];

            $hasSupply = collect($details)->contains(function ($detail) {
                return ($detail['model_type'] ?? null) === Supply::class;
            });

            if (! $hasSupply) {
                $validator->errors()->add('fabric_id', 'Es obligatorio.');
            }

            $hasProducts = collect($details)->contains(function ($detail) {
                return ($detail['model_type'] ?? null) === Product::class;
            });

            if (! $hasProducts) {
                $validator->errors()->add('curve', 'Es obligatorio.');
            }

            $empty = [];

            foreach ($details as $index => $detail) {
                $model = $detail['model_type'];
                $destination = $detail['destination'] ?? null;

                if (! class_exists($model)) {
                    $validator->errors()->add(
                        "production_order_details.$model.model_type",
                        'El tipo de modelo no es válido.'
                    );

                    continue;
                }

                if (! isset($detail['model_id'])) {
                    $validator->errors()->add(
                        "production_order_details.$model.$destination.model_id",
                        'No hay identificador del detalle.'
                    );

                    continue;
                }

                if (! $model::whereKey($detail['model_id'])->exists()) {
                    $validator->errors()->add(
                        "production_order_details.$model.model_id",
                        'El modelo seleccionado no existe.'
                    );
                }

                if ($model === Product::class) {
                    if (empty($destination)) {
                        $validator->errors()->add(
                            "production_order_details.$model.destination",
                            'El destino es obligatorio para los productos.'
                        );
                    }

                    if ((array_sum(array_column($detail['sizes'], 'quantity')) === 0) && $detail['id'] === null) {
                        $validator->errors()->add(
                            "curve.$destination",
                            'Es obligatorio ingresar curva.'
                        );

                        continue;
                    }

                    if ($destination !== 'STARA') {
                        if (empty($detail['sizes'])) {
                            $empty[] = $destination;
                        }
                    }
                }

                if ($model === Supply::class) {
                    if (! empty($detail['model_id'])) {
                        $supply = Supply::with('supply_type')->find($detail['model_id']);
                        $settings = $supply->supply_type->first()->settings;

                        if (! empty($this->color_id)) {
                            $exists = $supply
                                ->related(Color::class)
                                ->where('id', $this->color_id)
                                ->exists();

                            if (! $exists) {
                                $validator->errors()->add(
                                    'color_id',
                                    'El color seleccionado no esta asociado a la tela.'
                                );
                            }
                        }

                        if (empty($settings->in_production_order) && $settings->paragraph !== 'fabric') {
                            $validator->errors()->add(
                                'fabric_id',
                                'El tipo de insumo no es apto para asociar una orden de producción'
                            );
                        }
                    }
                }

                if ($model === Color::class) {
                    if ($detail['model_id'] !== $this->color_id) {
                        $validator->errors()->add(
                            "production_order_details.$index.model_id",
                            'El modelo seleccionado no corresponde con el color de la tela seleccionada.'
                        );

                        continue;
                    }
                }

                if ($model === Process::class) {

                    $process = Process::with(['before_processes', 'after_processes'])->find($detail['model_id']);

                    if (! $process) {
                        continue;
                    }

                    $current_date = data_get($detail, 'settings.date');

                    if (! $current_date) {
                        continue;
                    }

                    foreach ($details as $other_index => $other_detail) {

                        if ($other_index === $index) {
                            continue;
                        }

                        if (($other_detail['model_type'] ?? null) !== Process::class) {
                            continue;
                        }

                        $other_date = data_get($other_detail, 'settings.date');

                        if (! $other_date) {
                            continue;
                        }

                        if ($current_date === $other_date) {
                            $validator->errors()->add(
                                "production_order_details.$process->id.settings.date",
                                'La fecha de programación de este proceso ya está asignada a otro proceso.'
                            );

                            break;
                        }
                    }

                    foreach ($process->before_processes ?? [] as $before_process) {

                        $before_detail = collect($details)->first(function ($other_detail) use ($before_process) {
                            return ($other_detail['model_type'] ?? null) === Process::class
                                && (int) ($other_detail['model_id'] ?? 0) === (int) $before_process->id;
                        });

                        if (! $before_detail) {
                            continue;
                        }

                        $before_date = data_get($before_detail, 'settings.date');

                        if (! $before_date) {
                            continue;
                        }

                        if ($current_date < $before_date) {
                            $validator->errors()->add(
                                "production_order_details.$process->id.settings.date",
                                "La fecha de programación del proceso {$process->name} no puede ser anterior a la fecha de programación del proceso {$before_process->name}."
                            );
                        }
                    }

                    foreach ($process->after_processes ?? [] as $after_process) {

                        $after_detail = collect($details)->first(function ($other_detail) use ($after_process) {
                            return ($other_detail['model_type'] ?? null) === Process::class
                                && (int) ($other_detail['model_id'] ?? 0) === (int) $after_process->id;
                        });

                        if (! $after_detail) {
                            continue;
                        }

                        $after_date = data_get($after_detail, 'settings.date');

                        if (! $after_date) {
                            continue;
                        }

                        if ($current_date > $after_date) {
                            $validator->errors()->add(
                                "production_order_details.$index.settings.date",
                                "La fecha de programación del proceso {$process->name} no puede ser posterior a la fecha de programación del proceso {$after_process->name}."
                            );
                        }
                    }
                }
            }

            if (count($empty) > 1) {
                foreach ($empty as $item) {
                    $validator->errors()->add(
                        "production_order_details.$model.$item",
                        'Es obligatorio definir la especificación de curva por talla.'
                    );
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['pocket_fabric' => $this->boolean('pocket_fabric')]);
        $this->merge(['reasigned_curve' => $this->boolean('reasigned_curve')]);
    }
}
