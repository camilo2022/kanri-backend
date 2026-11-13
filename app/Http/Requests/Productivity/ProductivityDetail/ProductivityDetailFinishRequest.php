<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use Carbon\Carbon;
use App\Services\ProductivityDetailService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Finalizar el trabajo en curso. En el último puesto de la cadena del primer proceso (Corte → ETIQUETADO) llegan las
 * unidades por destino y talla: cada destino y talla debe ser de la orden y debe haber al menos una unidad. En los
 * demás puestos no se envían unidades.
 */
class ProductivityDetailFinishRequest extends FormRequest
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
            'token' => ['required', 'string', 'uuid'],
            'productivity_detail_id' => ['nullable', 'integer'],
            'quantities' => ['nullable', 'array'],
            'quantities.*.production_order_detail_id' => ['required', 'integer'],
            'quantities.*.size_id' => ['required', 'integer'],
            'quantities.*.quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            // Puestos que digitan el total al finalizar (sin +1)
            'units' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $context = app(ProductivityDetailService::class)->workstationContext((string) $this->input('token'));

            if ($context['error']) {
                $validator->errors()->add('token', $context['error']);

                return;
            }

            $workstation = $context['workstation'];
            $process = $context['process'];

            $service = app(ProductivityDetailService::class);

            $productivity_detail = $service->inProgress($workstation, $this->integer('productivity_detail_id'));

            if (! $productivity_detail) {
                $validator->errors()->add('token', 'Este puesto no tiene un trabajo en curso.');

                return;
            }

            // Con una parada en curso nadie está trabajando: no se finaliza. Esperando unidades sí: el grupo puede cerrar e
            // irse a otra referencia
            if (($open = app(ProductivityDetailService::class)->openStop($productivity_detail)) && ! ($open->settings->automatic ?? false)) {
                $validator->errors()->add('token', ($open->settings->type ?? null) === 'programmed'
                    ? sprintf('Están en la parada %s hasta las %s. Finaliza el trabajo cuando termine.', $open->settings->name ?? '', Carbon::parse($open->settings->end)->format('g:i A'))
                    : sprintf('Hay una parada en curso (%s). Termínala antes de finalizar el trabajo.', $open->settings->name ?? ''));

                return;
            }

            // Sin +1: digitan cuántas hicieron, hasta lo que le falta al lote en el paso (su parte es solo la meta)
            if ($service->totalAtFinish($process, $workstation)) {
                $cap = $service->finishCap($productivity_detail, $process);

                if (! $this->filled('units')) {
                    $validator->errors()->add('units', 'Digita cuántas unidades hicieron.');
                } elseif ($this->integer('units') > $cap) {
                    $validator->errors()->add('units', "Máximo {$cap} unidades: es lo que tiene disponible este puesto.");
                }

                return;
            }

            if (! $service->createsIngress($process, $workstation)) {
                if ($this->filled('quantities')) {
                    $validator->errors()->add('quantities', 'Este puesto no registra unidades; solo se finaliza el trabajo.');
                }

                return;
            }

            if (! $this->filled('quantities')) {
                $validator->errors()->add('quantities', 'Registra al menos un destino y talla.');

                return;
            }

            // Tallas permitidas por destino de la orden: [production_order_detail_id => [size_id, …]]
            $allowed = collect($service->destinations($productivity_detail->production_order))
                ->mapWithKeys(fn ($destination) => [
                    $destination['production_order_detail_id'] => collect($destination['sizes'])->pluck('size_id')->all(),
                ]);

            foreach ($this->input('quantities') as $i => $quantity) {
                if (! in_array((int) $quantity['size_id'], $allowed->get((int) $quantity['production_order_detail_id'], []), true)) {
                    $validator->errors()->add("quantities.$i.size_id", 'El destino o la talla no son de la orden que se está trabajando.');
                }
            }

            if (collect($this->input('quantities'))->sum('quantity') < 1) {
                $validator->errors()->add('quantities', 'Registra al menos una unidad.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'token' => 'Código QR del puesto de trabajo',
            'quantities' => 'Unidades por destino y talla',
            'quantities.*.production_order_detail_id' => 'Destino',
            'quantities.*.size_id' => 'Talla',
            'quantities.*.quantity' => 'Unidades',
            'units' => 'Unidades hechas',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['token' => $this->route('token')]);
    }
}
