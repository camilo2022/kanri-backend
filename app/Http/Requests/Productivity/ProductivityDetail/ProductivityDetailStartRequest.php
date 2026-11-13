<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Employee;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\TechnicalSheetDetail;
use App\Services\ProductivityDetailService;
use App\Services\StandardRuleService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;

/**
 * Iniciar trabajo en el puesto con la orden escaneada y sus empleados: el puesto y su turno abierto, que no tenga un
 * trabajo en curso, que el turno no haya terminado, que el proceso tenga medición, que el primer proceso tenga sus hijos
 * en secuencia, que haya una regla estándar, que la orden tenga curva (si el puesto crea ingresos), que el lote haya
 * llegado y no esté completo (por unidades) o no se haya trabajado ya en el puesto (por lote), que haya terminado en los
 * puestos anteriores de la cadena y que los empleados sean exactamente las personas de la regla, asignados y libres.
 * Por unidades solo se inicia lo programado: el registro programado y sin iniciar del lote en el puesto dentro del turno,
 * que el controlador retoma (no crea uno nuevo).
 */
class ProductivityDetailStartRequest extends FormRequest
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
            'production_order' => ['required', 'string', 'uuid', 'exists:production_orders,uuid,deleted_at,NULL'],
            'employees' => ['required', 'array', 'min:1'],
            'employees.*' => ['required', 'integer', 'distinct'],
            'replaces' => ['nullable', 'array'],
            'replaces.*' => ['integer', 'distinct'],
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
            $shift = $context['shift'];

            $service = app(ProductivityDetailService::class);

            // En los puestos programados pueden trabajar varios grupos a la vez: lo que no se cruza son los empleados
            if (! $service->programmable($process, $workstation) && $service->inProgress($workstation)) {
                $validator->errors()->add('token', 'Este puesto ya tiene un trabajo en curso; finalízalo antes de iniciar otro.');

                return;
            }

            if ($shift->endsAt()->lte(now())) {
                $validator->errors()->add('token', 'El turno ya terminó; no se puede iniciar un trabajo.');

                return;
            }

            // La medición es la del puesto: la suya, o la heredada de su subproceso o de su proceso
            if (! $service->measurementOf($workstation)) {
                $validator->errors()->add('token', "{$workstation->name} no tiene definido cómo se mide su productividad (por lote o por unidades).");

                return;
            }

            // Programables (por unidades, salvo ETIQUETADO): con el +1 o con el total al finalizar
            $by_units = $service->programmable($process, $workstation);

            if ($process->isFirst() && ($parent = $service->parallelParent($workstation))) {
                $validator->errors()->add('token', "{$parent->name} trabaja sus hijos en paralelo; configúralo en secuencia para saber cuál registra las unidades.");

                return;
            }

            $production_order = ProductionOrder::where('uuid', $this->input('production_order'))->first();

            if ($error = $service->branchError($production_order, $process)) {
                $validator->errors()->add('production_order', $error);

                return;
            }

            // El proceso debe estar «En revisión» o «Aprobado» en la ficha técnica de la referencia (si sigue «Pendiente»,
            // aún no hay información para producir)
            $ready = TechnicalSheetDetail::where('technical_sheet_id', $production_order->technical_sheet_id)
                ->where('model_type', Process::class)
                ->where('model_id', $process->id)
                ->whereIn('status', [TechnicalSheetDetail::REVIEW, TechnicalSheetDetail::APPROVED])
                ->exists();

            if (! $ready) {
                $validator->errors()->add('production_order', "{$process->name} aún no está en revisión ni aprobado en la ficha técnica de esta referencia: no se puede producir.");

                return;
            }

            // La ficha debe llevar este puesto
            if ($error = $service->routeError($production_order, $workstation, $process)) {
                $validator->errors()->add('production_order', $error);

                return;
            }

            $rule = app(StandardRuleService::class)
                ->resolve($production_order, $workstation, $process)['rule'] ?? null;

            if (! $rule) {
                $validator->errors()->add('production_order', 'No hay una regla estándar que aplique a esta referencia en este puesto.');

                return;
            }

            // Sin unidades en las tallas activas de la marca no se trabaja (en el primer proceso, ni por lote)
            if (($service->createsIngress($process, $workstation) || $process->isFirst()) && empty($service->destinations($production_order))) {
                $validator->errors()->add('production_order', 'La orden de producción no tiene unidades en las tallas que hoy tiene la marca: no se puede trabajar.');

                return;
            }

            // En un proceso con anteriores el lote debe haber llegado (ingreso aprobado), se mida como se mida
            if (! $process->isFirst() && $service->receivedDetails($production_order, $process)->isEmpty()) {
                $validator->errors()->add('production_order', "El lote aún no ha llegado a {$process->name}: no tiene ingresos aprobados.");

                return;
            }

            // Por unidades: además debe quedar algo por hacer
            if ($by_units) {
                $counters = $service->counters($production_order, $process, $workstation);

                if (empty($counters['details'])) {
                    $validator->errors()->add('production_order', "El lote aún no ha llegado a {$process->name}: no tiene ingresos aprobados.");

                    return;
                }

                if (! $counters['current']) {
                    $validator->errors()->add('production_order', "El lote ya está completo en {$workstation->name}: se hicieron las {$counters['produced']} unidades recibidas.");

                    return;
                }

                // Solo se trabaja lo que el líder programó para el puesto en el turno
                $scheduled = $service->scheduledDetail($shift, $workstation, $production_order);

                // Sin programación en el puesto, se puede iniciar cualquier referencia lista
                if (! $scheduled && $service->workstationProgrammed($shift, $workstation)) {
                    $validator->errors()->add('production_order', "La referencia no está programada para {$workstation->name} en este turno.");

                    return;
                }

                // Lo programado se escanea desde su hora o antes, si se puede adelantar
                if ($error = $service->scheduleWindowError($shift, $workstation, $production_order)) {
                    $validator->errors()->add('production_order', $error);

                    return;
                }

                // Lo programado antes debe haber soltado prendas
                if ($error = $service->noUnitsError($service->unitsLimit($shift, $production_order, $workstation, $process, $scheduled ? $service->scheduledStart($scheduled) : now()), $production_order)) {
                    $validator->errors()->add('production_order', $error);

                    return;
                }

                $rule = $scheduled?->rule ?? $rule;
            }

            // Por lote se trabaja una sola vez en el puesto; por unidades, en varios registros hasta completarlo
            if (! $service->measuredByUnits($workstation) && $service->registeredDetail($production_order, $workstation)) {
                $validator->errors()->add('production_order', "El lote de esta orden ya se trabajó en {$workstation->name}.");

                return;
            }

            // ETIQUETADO: se puede partir en varios registros mientras a la curva de la orden le falten unidades
            if ($service->countsAtFinish($process, $workstation)
                && collect($service->pendingCurve($production_order, $workstation))->sum(fn ($destination) => collect($destination['sizes'])->sum('quantity')) < 1
                && $service->registeredDetail($production_order, $workstation)) {
                $validator->errors()->add('production_order', "El lote de esta orden ya se registró completo en {$workstation->name}.");

                return;
            }

            // Por lote en el primer proceso (Corte) también se trabaja solo lo programado, desde su hora
            if (! $by_units && $service->schedulable($process, $workstation)) {
                if (! $service->scheduledDetail($shift, $workstation, $production_order) && $service->workstationProgrammed($shift, $workstation)) {
                    $validator->errors()->add('production_order', "La referencia no está programada para {$workstation->name} en este turno.");

                    return;
                }

                if ($error = $service->scheduleWindowError($shift, $workstation, $production_order)) {
                    $validator->errors()->add('production_order', $error);

                    return;
                }
            }

            // En una cadena en secuencia, el lote debe haber terminado en los puestos anteriores (en paralelo basta con que
            // estén en curso o que el mismo grupo los inicie con este)
            $programmed_row = $service->schedulable($process, $workstation) ? $service->scheduledDetail($shift, $workstation, $production_order) : null;
            $starting = $programmed_row ? $service->companionsToStart($programmed_row, $process)->pluck('model') : null;
            $sequence = $service->sequenceStatus($production_order, $workstation, $process, $starting);

            if (! $sequence['ready']) {
                $validator->errors()->add('production_order', $sequence['message']);

                return;
            }

            $employees = collect($this->input('employees'))->map(fn ($id) => (int) $id)->values();

            // Lo programado lo trabaja el grupo que programó el líder. Solo quien está ocupado en otro trabajo en curso se
            // puede cambiar, uno por uno, por alguien libre del proceso (replaces: nuevo => programado)
            if ($service->schedulable($process, $workstation) && $service->scheduledDetails($shift, $workstation, $production_order)->isNotEmpty()) {
                $busy = $service->busyMap($workstation, $production_order->id);
                $replaces = collect((array) $this->input('replaces'))->mapWithKeys(fn ($old, $new) => [(int) $new => (int) $old]);
                $sorted = fn (Collection $ids) => $ids->sort()->values()->all();

                $fits = $service->scheduledNow($shift, $workstation, $production_order)->contains(function ($row) use ($employees, $busy, $replaces, $sorted) {
                    $group = $row->employees->pluck('id');
                    $missing = $group->diff($employees);

                    return $missing->every(fn ($id) => $busy->has($id))
                        && $sorted($replaces->keys()) === $sorted($employees->diff($group))
                        && $sorted($replaces->values()) === $sorted($missing);
                });

                if (! $fits) {
                    $validator->errors()->add('employees', 'Solo trabajan las personas que el líder programó para esta referencia; solo se puede reemplazar, una por una, a quien esté ocupado en otro trabajo. Si el grupo cambió, pide al líder que la reprograme.');

                    return;
                }
            }

            // Trabajan los empleados del puesto que vinieron (se pueden retirar los que faltan) y, de refuerzo, libres de
            // otros puestos del mismo proceso padre; la capacidad de la regla sale de cuántos son
            $eligible = $service->eligibleEmployees($workstation, $production_order->id)->pluck('id');
            $borrowable = $service->borrowableEmployees($workstation, $process, $production_order->id)->keyBy('id');

            foreach ($employees as $i => $id) {
                if ($eligible->contains($id)) {
                    continue;
                }

                $other = $borrowable->get($id);

                if (! $other && ($place = $service->busyMap($workstation, $production_order->id)->get($id))) {
                    $validator->errors()->add("employees.$i", $service->employeePayload(Employee::with('person')->find($id))['name']." está ocupado en {$place}.");
                } elseif (! $other) {
                    $validator->errors()->add("employees.$i", "El empleado no pertenece a {$process->name} o ya está en otro trabajo en curso.");
                } elseif ($other['busy']) {
                    $validator->errors()->add("employees.$i", "{$other['name']} está ocupado en {$other['busy_in']}.");
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $capacity = $service->capacity($rule, $workstation, $employees->count());

            if (! $capacity) {
                $validator->errors()->add('production_order', $service->capacityError($rule, $workstation, $employees->count()));
            }
        });
    }

    public function messages(): array
    {
        return [
            'production_order.uuid' => 'El código escaneado no es de una orden de producción.',
            'production_order.exists' => 'La orden de producción no existe o está desactivada.',
            'employees.required' => 'Selecciona los empleados que van a trabajar.',
            'employees.min' => 'Selecciona los empleados que van a trabajar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'Código QR del puesto de trabajo',
            'production_order' => 'Orden de producción escaneada',
            'employees' => 'Empleados',
            'employees.*' => 'Empleado',
            'replaces' => 'Reemplazos',
            'replaces.*' => 'Persona reemplazada',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['token' => $this->route('token')]);
    }
}
