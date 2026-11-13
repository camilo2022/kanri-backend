<?php

namespace App\Http\Requests\Productivity\ProductivityDetail;

use App\Models\Operation;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Productivity;
use App\Models\Subprocess;
use App\Models\TechnicalSheetDetail;
use App\Services\ProductivityDetailService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Programar un registro de trabajo (procesos por unidades), antes de que inicie el turno: el lote en un puesto, con su
 * inicio (HH:MM, dentro del turno) y sus empleados (los del puesto, sin los que no vienen, más prestados de otros puestos
 * del proceso que no estén en otra fila del turno a la misma hora). El lote debe tener unidades de ingreso por hacer y la regla, tiempo
 * por unidad; no se cruza con otra fila del puesto.
 */
class ProductivityDetailStoreRequest extends FormRequest
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
            'productivity_id' => ['required', 'integer', 'exists:productivity,id,deleted_at,NULL'],
            'model_type' => ['required', 'string', 'in:'.implode(',', [Process::class, Subprocess::class, Operation::class])],
            'model_id' => ['required', 'integer'],
            'production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'start' => ['required', 'date_format:H:i'],
            'employees' => ['required', 'array', 'min:1'],
            // Grupo nuevo de las otras filas que se guardan en el mismo envío (se guardan una por una)
            'pending_employees' => ['nullable', 'array'],
            'pending_employees.*' => ['array'],
            'employees.*' => ['required', 'integer', 'distinct'],
            'multiplier' => ['nullable', 'numeric', 'min:1', 'max:1000'],
            // Tandas: unidades que digita quien programa (solo en los puestos con «Unidades al programar: se digitan»)
            'units' => ['nullable', 'integer', 'min:1', 'max:100000'],
            // Programación por lote (Corte): la hora de fin la pone quien programa
            'end' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $shift = Productivity::find($this->integer('productivity_id'));

            $service = app(ProductivityDetailService::class);
            $process = $shift->model;

            if (! $process instanceof Process || ! $service->workstations($process)->contains(fn ($item) => $service->schedulable($process, $item['model_type']::find($item['model_id'])))) {
                $validator->errors()->add('productivity_id', 'La programación solo aplica a procesos con puestos que tengan medición (por lote o por unidades).');

                return;
            }

            if ($shift->status === Productivity::FINISHED) {
                $validator->errors()->add('productivity_id', 'El turno ya terminó; su programación no se puede cambiar.');

                return;
            }

            $workstations = $service->workstations($process);
            $entry = $workstations->first(fn ($item) => $item['model_type'] === $this->input('model_type') && $item['model_id'] === $this->integer('model_id'));
            $workstation = $entry ? $this->input('model_type')::find($this->integer('model_id')) : null;

            if (! $workstation) {
                $validator->errors()->add('model_id', "No es un puesto de trabajo de {$process->name}.");

                return;
            }

            if (! $service->schedulable($process, $workstation)) {
                $validator->errors()->add('model_id', "{$workstation->name} no se programa: se mide por lote o registra sus unidades al finalizar.");
                return;
            }

            $production_order = ProductionOrder::find($this->integer('production_order_id'));

            if ($error = $service->branchError($production_order, $process)) {
                $validator->errors()->add('production_order_id', $error);

                return;
            }

            if ($workstation && ($error = $service->previousScheduledError($production_order, $workstation, $process))) {
                $validator->errors()->add('production_order_id', $error);

                return;
            }


            $ready = TechnicalSheetDetail::where('technical_sheet_id', $production_order->technical_sheet_id)
                ->where('model_type', Process::class)
                ->where('model_id', $process->id)
                ->whereIn('status', [TechnicalSheetDetail::REVIEW, TechnicalSheetDetail::APPROVED])
                ->exists();

            if (! $ready) {
                $validator->errors()->add('production_order_id', "{$process->name} aún no está en revisión ni aprobado en la ficha técnica de esta referencia: no se puede producir.");

                return;
            }

            // La ficha debe llevar este puesto
            if ($error = $service->routeError($production_order, $workstation, $process)) {
                $validator->errors()->add('production_order_id', $error);

                return;
            }

            // El lote debe haber llegado al proceso y tener unidades por hacer en el paso de la ficha del puesto
            // Por lote (se mide por lote o crea el ingreso) o por unidades, según la configuración del puesto
            $by_lot = $service->schedulesByLot($process, $workstation);

            if ($by_lot && ($error = $service->lotScheduleError($shift, $production_order, $workstation, $process, count($this->input('employees')), null))) {
                $validator->errors()->add('production_order_id', $error);

                return;
            }

            if (! $process->isFirst() && $service->receivedDetails($production_order, $process)->isEmpty()) {
                $validator->errors()->add('production_order_id', "La referencia no tiene ingresos aprobados en {$process->name}.");

                return;
            }

            $units = $by_lot ? $service->lotUnitsIn($production_order, $process) : $service->unitsToDo($production_order, $workstation, $process);

            if ($units < 1) {
                $validator->errors()->add('production_order_id', "La referencia ya está completa en {$workstation->name}: no quedan unidades por hacer.");

                return;
            }

            if (! $by_lot && ($service->multiplierOf($workstation)['mode'] ?? null) === 'manual' && ! $this->filled('multiplier')) {
                $validator->errors()->add('multiplier', 'Digita '.mb_strtolower($service->multiplierOf($workstation)['label']).'.');

                return;
            }

            $time = $by_lot ? [] : $service->unitTime($production_order, $workstation, $process, $this->input('multiplier'));

            if ($time['error'] ?? null) {
                $validator->errors()->add('production_order_id', $time['error']);

                return;
            }

            $rule = $time['rule'] ?? null;
            $start = Carbon::parse($shift->date->format('Y-m-d').' '.$this->input('start'));

            if ($start->lt($shift->startsAt()) || $start->gte($shift->endsAt())) {
                $validator->errors()->add('start', 'El inicio debe estar dentro del turno ('.$shift->startsAt()->format('H:i').' a '.$shift->endsAt()->format('H:i').').');

                return;
            }

            // Con el turno en curso no se programa hacia atrás
            if ($shift->status === Productivity::IN_PROGRESS && $start->lt(now()->startOfMinute())) {
                $validator->errors()->add('start', 'El turno está en curso: el inicio no puede ser antes de las '.now()->format('H:i').'.');

                return;
            }

            $rows = $shift->productivity_details()
                ->with(['production_order' => ['technical_sheet' => ['product']], 'employees', 'model'])
                ->get();

            $same_workstation = $rows->where('model_type', $workstation::class)->where('model_id', $workstation->id);

            $typed = $service->typedUnits($process, $workstation);

            if ($typed && ! $this->filled('units')) {
                $validator->errors()->add('units', 'Digita las unidades de la tanda.');

                return;
            }

            // La franja de esta fila (con su parte del reparto del lote). En un mismo puesto se pueden trabajar
            // referencias a la vez si los grupos de trabajo son distintos: lo que no se cruza son los empleados
            // El reparto es entre las filas del lote que trabajan el mismo paso de la ficha
            $step = $service->stepWorkstations($service->routeStep($production_order, $workstation, $process), $process)
                ->map(fn ($item) => $item::class.':'.$item->id);
            $lot = $rows->where('production_order_id', $production_order->id)
                ->filter(fn ($row) => $step->contains($row->model_type.':'.$row->model_id));
            $parts = $service->share($units, $lot->filter(fn ($row) => ! $row->end)->count() + 1);

            // Tandas: la suma de lo programado del lote en el paso no pasa de lo que le falta
            if ($typed) {
                $other = $service->pendingUnits($lot);
                $label = collect([$production_order->technical_sheet?->product?->code, $production_order->cut])->filter()->implode('-');

                if ($this->integer('units') > $units - $other) {
                    $validator->errors()->add('units', "A {$label} le faltan {$units} unidades en {$workstation->name} y ya hay {$other} programadas: puedes programar hasta ".max(0, $units - $other).'.');

                    return;
                }

                $parts = [$this->integer('units')];

                // Una tanda (filas del puesto a la misma hora) junta referencias solo si tienen el mismo tono de lavado
                $production_order->loadMissing(['technical_sheet' => ['wash_tone']]);
                $mate = $same_workstation->filter(fn ($row) => ! $row->end && $row->start->format('H:i') === $this->input('start'))
                    ->first(fn ($row) => $row->production_order_id !== $production_order->id
                        && $row->production_order?->technical_sheet?->wash_tone_id !== $production_order->technical_sheet?->wash_tone_id);

                if ($mate) {
                    $mate->loadMissing(['production_order' => ['technical_sheet' => ['wash_tone']]]);
                    $mate_label = collect([$mate->production_order->technical_sheet?->product?->code, $mate->production_order->cut])->filter()->implode('-');
                    $validator->errors()->add('start', "En la tanda de las {$this->input('start')} de {$workstation->name} va {$mate_label} con tono ".($mate->production_order->technical_sheet?->wash_tone?->name ?? 'sin tono').'; esta referencia tiene tono '.($production_order->technical_sheet?->wash_tone?->name ?? 'sin tono').'. Una tanda solo junta referencias del mismo tono.');

                    return;
                }
            }
            if ($by_lot) {
                if (! $this->filled('end') || $this->input('end') <= $this->input('start')) {
                    $validator->errors()->add('end', 'Digita una hora de fin posterior al inicio.');

                    return;
                }

                $end = Carbon::parse($shift->date->format('Y-m-d').' '.$this->input('end'));
            } else {
                $unit_minutes = $time['time_unit'] === 'seg' ? $time['unit_time'] / 60 : $time['unit_time'];
                $end = $start->copy()->addSeconds((int) ceil($unit_minutes * end($parts) / count($this->input('employees')) * 60));
            }
            // La misma referencia en otro paso del proceso, a la vez, solo con el mismo grupo
            $group = collect($this->input('employees'))->map(fn ($id) => (int) $id);
            $pending = (array) $this->input('pending_employees', []);

            if ($error = $service->lotOverlapError($production_order, $workstation, $process, $start, $end, $group, null, $pending)) {
                $validator->errors()->add('start', $error);

                return;
            }

            // En paralelo, RELOJERA no queda antes que PARCHE para el mismo lote
            if ($error = $service->chainOrderError($shift, $production_order, $workstation, $process, $start, $group, null, $pending)) {
                $validator->errors()->add('start', $error);

                return;
            }

            // Cualquier registro de cualquier turno o proceso que se cruce con esta franja (no solo los de este turno)
            $overlaps = $service->overlappingRows($start, $end, null)
                // El mismo grupo puede trabajar dos puestos del lote a la vez (bisturí y motor, parche y relojera)
                ->reject(fn ($row) => $service->sameGroupOverlap($workstation, $row, $production_order, $process, $group, $pending))
                // Las filas de la misma tanda (el puesto a la misma hora) las hace el mismo grupo: no se cruzan
                ->reject(fn ($row) => $typed && $row->productivity_id === $shift->id && ! $row->end
                    && $row->model_type === $workstation::class && $row->model_id === $workstation->id
                    && $row->start->format('H:i') === $this->input('start'));

            // Empleados: los del puesto o prestados de otro puesto del proceso; ninguno en otro trabajo que se cruce con
            // esta franja
            $own = collect($entry['employees'])->pluck('id');
            $others = $workstations->filter(fn ($item) => $item !== $entry)
                ->flatMap(fn ($item) => collect($item['employees'])->map(fn ($employee) => array_merge($employee, ['workstation' => $item['name']])))
                ->keyBy('id');
            $taken = $overlaps->flatMap(fn ($row) => $service->rowEmployees($row, $pending)->map(fn ($id) => [$id, $row]))
                ->mapWithKeys(fn ($pair) => [$pair[0] => $pair[1]]);

            foreach (array_values($this->input('employees')) as $i => $id) {
                $id = (int) $id;

                if ($taken->has($id)) {
                    $validator->errors()->add("employees.$i", $service->busyMessage($taken[$id], $shift, $id));
                } elseif (! $own->contains($id) && ! $others->has($id)) {
                    $validator->errors()->add("employees.$i", "El empleado no pertenece a {$process->name}.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'multiplier.numeric' => 'Debe ser un número.',
            'multiplier.min' => 'Debe ser al menos :min.',
            'multiplier.max' => 'Debe ser máximo :max.',
            'employees.required' => 'Debe trabajar al menos una persona.',
            'employees.min' => 'Debe trabajar al menos una persona.',
        ];
    }

    public function attributes(): array
    {
        return [
            'productivity_id' => 'Turno.',
            'model_type' => 'Tipo de puesto.',
            'model_id' => 'Puesto de trabajo.',
            'production_order_id' => 'Referencia.',
            'start' => 'Hora de inicio.',
            'employees' => 'Empleados.',
            'employees.*' => 'Empleado.',
            'multiplier' => 'Cantidad por prenda.',
            'end' => 'Hora de fin',
        ];
    }
}
