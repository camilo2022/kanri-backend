<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Movement;
use App\Models\MovementDetail;
use App\Models\MovementDetailQuantity;
use App\Models\Operation;
use App\Models\Process;
use App\Models\ProductionOrder;
use App\Models\Productivity;
use App\Models\ProductivityDetail;
use App\Models\ProductivityDetailModel;
use App\Models\Rule;
use App\Models\Stop;
use App\Models\Subprocess;
use App\Models\TechnicalSheet;
use App\Models\TechnicalSheetDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use DomainException;

/**
 * Registro de trabajo en un puesto (escaneo del QR del puesto → referencia → empleados → iniciar → paradas →
 * finalizar).
 *
 * - Cada trabajo es un `productivity_details` (turno, puesto, lote, regla, inicio y fin; sin fin está en curso). Sus
 *   empleados y paradas son filas de `productivity_detail_models`.
 * - Solo el último puesto de la cadena del primer proceso (Corte → ETIQUETADO, o Corte si no tiene subprocesos) crea
 *   ingresos al finalizar: un detalle de movimiento por destino de la orden, Pendiente, cuyo origen (model) es el
 *   registro de trabajo. Los demás puestos solo registran tiempos y eficiencia.
 *
 * Tiempos (todo en minutos):
 * - Inicio oficial: el inicio del turno o, si el puesto ya trabajó en el turno, el fin de su último registro (sin
 *   tiempos vacíos). La hora real del escaneo queda en settings.scanned_at.
 * - Fin oficial: la hora de finalizar, o la hora de fin del turno si se finaliza después (la real queda en la
 *   auditoría).
 * - Paradas: programadas (terminan solas con la duración del catálogo) y no programadas (hasta «Terminar parada»).
 *   Si se cruzan, el rato común se descuenta una sola vez.
 * - Por lote (todas las personas paran): personas = empleados del registro;
 *   disponible = duración × personas; muerto = paradas × personas; trabajado = disponible − muerto;
 *   estándar = tiempo de la regla × personas; eficiencia = estándar ÷ trabajado × 100.
 * - Estos resultados no se guardan: el frontend los calcula con los datos crudos (utils/productivityMetrics.js); aquí
 *   solo se calculan para la vista previa y la respuesta de finalizar en la estación.
 */
class ProductivityDetailService
{
    /**
     * Unidades que debe soltar el puesto programado antes para que termine la parada de espera.
     */
    public const WAITING_RELEASE = 10;

    /**
     * Operaciones ya consultadas en opensLine: [id => bool].
     */
    protected array $opens_line = [];

    /**
     * Catálogo ya consultado durante la petición (no cambia mientras se arma un reporte o se registra un +1):
     * operaciones propias de cada subproceso, puestos de cada proceso, subproceso de cada operación y los anteriores de
     * cada subproceso. [id => resultado].
     */
    protected array $own_operations = [];

    protected array $workstation_models = [];

    protected array $subprocess_of = [];

    protected array $before_subprocesses = [];

    protected array $before_operations = [];

    protected array $excluded_places = [];

    /**
     * Proceso al que pertenece el puesto.
     */
    public function processOfWorkstation(Model $workstation): ?Process
    {
        return match (true) {
            $workstation instanceof Process => $workstation,
            $workstation instanceof Subprocess => $workstation->process()->first(),
            $workstation instanceof Operation => $workstation->subprocess()->first()?->process()->first(),
            default => null,
        };
    }

    /**
     * Puesto, proceso y turno a partir del uuid del QR del puesto (lo usan las acciones del registro de trabajo).
     * El turno es el del registro en curso del puesto (aunque ya haya pasado su hora de fin, para poder finalizarlo) o,
     * si no, el abierto: el de hoy del proceso cuya franja incluye la hora actual.
     *
     * @return array{workstation: ?Model, process: ?Process, shift: ?Productivity, error: ?string}
     */
    public function workstationContext(string $token): array
    {
        $context = ['workstation' => null, 'process' => null, 'shift' => null, 'error' => null];

        foreach ([Operation::class, Subprocess::class, Process::class] as $model) {
            $context['workstation'] ??= $model::where('settings->uuid', $token)->first();
        }

        $workstation = $context['workstation'];

        if (! $workstation) {
            return array_merge($context, ['error' => 'El código QR no corresponde a ningún puesto de trabajo activo.']);
        }

        if ($workstation instanceof Process && $workstation->subprocesses()->exists()) {
            return array_merge($context, ['error' => 'El proceso tiene subprocesos; escanea el QR del subproceso.']);
        }

        if ($workstation instanceof Subprocess && $workstation->operations()->exists()) {
            return array_merge($context, ['error' => 'El subproceso tiene operaciones; escanea el QR de la operación.']);
        }

        $process = match (true) {
            $workstation instanceof Operation => $workstation->subprocess()->first()?->process()->first(),
            $workstation instanceof Subprocess => $workstation->process()->first(),
            default => $workstation,
        };

        if (! $process) {
            return array_merge($context, ['error' => 'El puesto de trabajo no pertenece a ningún proceso activo.']);
        }

        $context['process'] = $process;
        $now = now()->format('H:i:s');

        $context['shift'] = $this->inProgress($workstation)?->productivity
            ?? Productivity::where('model_type', Process::class)
                ->where('model_id', $process->id)
                ->whereDate('date', today())
                ->where('start_time', '<=', $now)
                ->where('end_time', '>', $now)
                ->first();

        return $context['shift']
            ? $context
            : array_merge($context, ['error' => "No hay un turno abierto en este momento para el proceso {$process->name}."]);
    }

    /**
     * Registro en curso del puesto (en cualquier turno), o null: el indicado o, si no se indica, el último.
     */
    public function inProgress(Model $workstation, ?int $productivity_detail_id = null): ?ProductivityDetail
    {
        return ProductivityDetail::with(['productivity', 'stops', 'employees' => ['person']])
            ->where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->inProgress()
            ->when($productivity_detail_id, fn ($query) => $query->whereKey($productivity_detail_id))
            ->latest('id')
            ->first();
    }

    /**
     * Puestos a los que entra el empleado al iniciar sesión, según lo que tiene a esta hora: sus trabajos en curso; si no,
     * lo programado para ahora; lo atrasado sin iniciar; lo próximo del día; y, sin programación, su puesto fijo. Puede
     * ser más de uno si el mismo grupo tiene dos puestos a la misma hora.
     */
    public function employeeWorkstations(Employee $employee): Collection
    {
        $rows = ProductivityDetail::with(['model', 'productivity'])
            ->whereHas('employees', fn ($query) => $query->where('employees.id', $employee->id))
            ->whereNull('end')
            ->orderBy('id')
            ->get()
            ->filter(fn (ProductivityDetail $row) => $row->model && $row->productivity);

        $running = $rows->filter(fn (ProductivityDetail $row) => isset($row->settings->scanned_at));

        if ($running->isNotEmpty()) {
            return $this->workstationsOfRows($running);
        }

        $today = $rows->filter(fn (ProductivityDetail $row) => $row->productivity->date->isToday() && $row->productivity->endsAt()->gt(now()))
            ->sortBy(fn (ProductivityDetail $row) => $this->scheduledStart($row)->timestamp);
        $reached = $today->filter(fn (ProductivityDetail $row) => $this->inScheduleWindow($row));
        $on_time = $reached->filter(fn (ProductivityDetail $row) => empty($row->settings->scheduled->end) || now()->lte(Carbon::parse($row->settings->scheduled->end)));
        $turn = $on_time->whenEmpty(fn () => $reached)->whenEmpty(fn () => $today);

        if ($turn->isNotEmpty()) {
            $first = $this->scheduledStart($turn->first())->timestamp;

            return $this->workstationsOfRows($turn->filter(fn (ProductivityDetail $row) => $this->scheduledStart($row)->timestamp === $first));
        }

        $own = Operation::find($employee->workstation_id) ?? Subprocess::find($employee->workstation_id) ?? Process::find($employee->workstation_id);

        return match (true) {
            $own instanceof Operation => $this->workstationPayloads(collect([$own])),
            $own instanceof Subprocess => $this->workstationPayloads($this->ownOperations($own)->whenEmpty(fn () => collect([$own]))),
            $own instanceof Process => $this->workstationPayloads($this->workstationModels($own)),
            default => collect(),
        };
    }

    protected function workstationsOfRows(Collection $rows): Collection
    {
        return $rows->unique(fn (ProductivityDetail $row) => $row->model_type.':'.$row->model_id)
            ->map(fn (ProductivityDetail $row) => $this->workstationPayload($row->model, $this->scheduledStart($row)->format('g:i A')))
            ->values();
    }

    protected function workstationPayloads(Collection $workstations): Collection
    {
        return $workstations->map(fn (Model $workstation) => $this->workstationPayload($workstation))->values();
    }

    protected function workstationPayload(Model $workstation, ?string $start = null): array
    {
        $subprocess = $workstation instanceof Operation ? $workstation->subprocess()->first() : null;

        return [
            // El mismo código del QR del puesto
            'token' => $workstation->settingsUuid(),
            'name' => $workstation->name,
            'path' => collect([$workstation instanceof Process ? null : $this->processOfWorkstation($workstation)?->name, $subprocess?->name])->filter()->implode(' › '),
            'start' => $start,
        ];
    }

    /**
     * Lotes que se pueden trabajar en el puesto, para elegirlos si no se puede escanear el QR de la ficha: primero los
     * programados y sin iniciar en el turno abierto (con su hora), luego los demás. Al elegirlo se valida igual que al
     * escanear.
     */
    public function workstationReferences(Model $workstation): Collection
    {
        $process = $this->processOfWorkstation($workstation);

        if (! $process) {
            return collect();
        }

        $scheduled = ProductivityDetail::where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->scheduled()
            ->whereHas('productivity', fn ($query) => $query->whereDate('date', today())
                ->where('start_time', '<=', now()->format('H:i:s'))
                ->where('end_time', '>', now()->format('H:i:s')))
            ->get()
            ->sortBy(fn (ProductivityDetail $row) => $this->scheduledStart($row)->timestamp)
            ->unique('production_order_id')
            ->mapWithKeys(fn (ProductivityDetail $row) => [$row->production_order_id => $this->scheduledStart($row)]);

        return ProductionOrder::with(['technical_sheet' => ['product']])
            ->where(fn ($query) => $query
                ->whereIn('id', $scheduled->keys())
                ->orWhereIn('id', ProductionOrder::programmableIn($process->id, $workstation)->select('id')))
            ->get()
            ->sortBy([
                fn ($a, $b) => ($scheduled->get($a->id)?->timestamp ?? PHP_INT_MAX) <=> ($scheduled->get($b->id)?->timestamp ?? PHP_INT_MAX),
                fn ($a, $b) => "{$a->technical_sheet?->product?->code}-{$a->cut}" <=> "{$b->technical_sheet?->product?->code}-{$b->cut}",
            ])
            ->map(fn (ProductionOrder $production_order) => [
                'uuid' => $production_order->uuid,
                'cut' => $production_order->cut,
                'technical_sheet' => ['product' => ['code' => $production_order->technical_sheet?->product?->code]],
                'start' => $scheduled->get($production_order->id)?->format('g:i A'),
            ])
            ->values();
    }

    /**
     * Registros en curso del puesto: en los programados puede haber uno por grupo.
     */
    public function inProgressRows(Model $workstation): Collection
    {
        return ProductivityDetail::with(['productivity', 'stops', 'employees' => ['person']])
            ->where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->inProgress()
            ->orderBy('id')
            ->get();
    }

    /**
     * ¿El inicio oficial es la hora real del escaneo? En los procesos con subprocesos en paralelo sí (nunca es antes de la
     * hora programada: antes no se puede escanear); en secuencia, el inicio depende del puesto anterior.
     */
    public function scheduleIsGuide(Process $process): bool
    {
        return ($process->settings->subprocess_flow ?? Process::PARALLEL) === Process::PARALLEL;
    }

    /**
     * Inicio oficial del siguiente registro del puesto en el turno: lo más tardío entre el inicio del turno, el fin
     * del último registro del puesto en el turno y, en una cadena en secuencia, el fin del lote en el puesto
     * anterior (aunque escaneen más tarde, empieza apenas terminó el anterior). Si está programado, también su hora
     * programada, y del puesto solo cuenta el fin de los registros con alguna de sus personas (otro grupo no lo atrasa).
     * Tampoco se le cobra si el puesto programado antes empezó después de su hora.
     */
    public function startTime(Productivity $shift, Model $workstation, ?ProductionOrder $production_order = null, ?ProductivityDetail $scheduled = null, ?Collection $employees = null): Carbon
    {
        // En paralelo la programación es una guía: inicia cuando realmente empieza; el atraso se ve en el cumplimiento
        if ($scheduled && $this->scheduleIsGuide($shift->model)) {
            return now();
        }

        // Adelantado (se escanea antes de su hora) empieza ahora, no a la hora programada
        $start = $scheduled ? $shift->startsAt()->max($this->scheduledStart($scheduled)->min(now())) : $shift->startsAt();

        if ($scheduled && $production_order && ($limit = $this->unitsLimit($shift, $production_order, $workstation, $shift->model, $this->scheduledStart($scheduled)))) {
            $start = $start->max(Carbon::parse($limit['start']));
        }

        $last = $shift->productivity_details()
            ->where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->when($scheduled, fn ($query) => $query->whereHas('employees', fn ($employee) => $employee->whereIn('employees.id', $employees ?? $scheduled->employees->pluck('id'))))
            ->max('end');

        if ($last) {
            $start = $start->max(Carbon::parse($last));
        }

        $previous_end = $production_order ? $this->sequenceStatus($production_order, $workstation)['previous_end'] : null;

        return $previous_end ? $start->max(Carbon::parse($previous_end)) : $start;
    }

    /**
     * Puestos anteriores en la cadena, donde el lote debe haber terminado antes de trabajar en este. Solo cuando el
     * padre tiene sus hijos en secuencia:
     * - Subproceso: sus subprocesos anteriores.
     * - Operación: sus operaciones anteriores; la primera de su subproceso (o si el subproceso las tiene en
     *   paralelo), lo anterior del subproceso.
     * Un subproceso anterior con operaciones se cuenta por sus últimas operaciones (o todas, si van en paralelo),
     * que son los puestos donde se registra el trabajo.
     */
    public function previousWorkstations(Model $workstation, ?ProductionOrder $production_order = null): Collection
    {
        if ($workstation instanceof Operation) {
            $subprocess = $workstation->subprocess()->first();

            if (! $subprocess) {
                return collect();
            }

            // En secuencia, su anterior en la cadena; en paralelo, de las que depende («Depende de»)
            $before = $workstation->before_operations()->get();

            if ($before->isNotEmpty()) {
                return $before;
            }

            // La que abre una línea (RESINA) no espera lo anterior de su subproceso
            return $this->opensLine($workstation) ? collect() : $this->previousOfSubprocess($subprocess, $production_order);
        }

        return $workstation instanceof Subprocess ? $this->previousOfSubprocess($workstation, $production_order) : collect();
    }

    protected function previousOfSubprocess(Subprocess $subprocess, ?ProductionOrder $production_order = null): Collection
    {
        $process = $subprocess->process()->first();

        if (! $process || ! $this->inSequence($process, 'subprocess_flow')) {
            return collect();
        }

        return $this->beforeSubprocesses($subprocess, $production_order)
            ->flatMap(fn (Subprocess $previous) => $this->leaves($previous))
            ->merge($subprocess->before_operations()->get())
            ->values();
    }

    /**
     * ¿La operación sale de su subproceso y abre una línea (tiene subprocesos siguientes, RESINA → LAVADO RESINA)? Su
     * lugar en la cadena lo dan sus conexiones: no espera lo anterior de su subproceso ni cuenta como parte de él.
     */
    public function opensLine(Operation $operation): bool
    {
        return $this->opens_line[$operation->id] ??= $operation->after_subprocesses()->exists();
    }

    /**
     * Operaciones del subproceso que son parte de él (sin las que abren una línea).
     */
    protected function ownOperations(Subprocess $subprocess): Collection
    {
        return $this->own_operations[$subprocess->id] ??= $subprocess->operations()->get()->reject(fn (Operation $operation) => $this->opensLine($operation))->values();
    }

    /**
     * Subproceso de la operación, consultado una vez por petición.
     */
    public function subprocessOf(Operation $operation): ?Subprocess
    {
        if (! array_key_exists($operation->id, $this->subprocess_of)) {
            $this->subprocess_of[$operation->id] = $operation->subprocess()->first();
        }

        return $this->subprocess_of[$operation->id];
    }

    /**
     * Subprocesos anteriores que valen para el lote: los de la cadena, menos los que tienen una condición de tono que el
     * lote no cumple (p. ej. PLANCHA → SEDA solo en tono NEGRO). Sin lote, todos.
     */
    public function beforeSubprocesses(Subprocess $subprocess, ?ProductionOrder $production_order = null, bool $returns = false): Collection
    {
        return ($this->before_subprocesses[$subprocess->id] ??= $subprocess->before_subprocesses()->get())
            ->filter(fn (Subprocess $previous) => $this->chainApplies($previous, $subprocess, $production_order)
                && $this->chainReturns($previous, $subprocess) === $returns)
            ->values();
    }

    /**
     * ¿La conexión anterior → siguiente es un regreso (settings.next_conditions.{id}.returns)? El lote vuelve a pasar
     * por el siguiente (LAVADO ADICIONAL ↩ CENTRIFUGADO 1): no es requisito para entrar, le devuelve unidades.
     */
    public function chainReturns(Subprocess $previous, Subprocess $next): bool
    {
        $conditions = (array) ($previous->settings->next_conditions ?? []);

        return (bool) (((array) ($conditions[$next->id] ?? $conditions[(string) $next->id] ?? []))['returns'] ?? false);
    }

    /**
     * Veces que el lote pasa por el subproceso del paso: 1 + los regresos que llegan desde pasos que el lote lleva
     * (con trapo, CENTRIFUGADO 1 dos veces).
     */
    public function passes(ProductionOrder $production_order, Model $step, Process $process, ?Collection $marked = null): int
    {
        $subprocess = $step instanceof Operation ? $step->subprocess()->first() : $step;

        if (! $subprocess instanceof Subprocess) {
            return 1;
        }

        $marked ??= $this->markedInSheet($production_order);

        return 1 + $this->beforeSubprocesses($subprocess, $production_order, true)
            ->filter(fn (Subprocess $source) => $this->routeStep($production_order, $source, $process, $marked))
            ->count();
    }

    /**
     * Unidades que un paso anterior deja listas para el puesto. Si el anterior se hace varias veces (regreso), al
     * siguiente que está dentro de la vuelta (TRAPO) le llega la primera pasada y al de afuera (SECADO 1), la última.
     */
    protected function unitsFrom(ProductionOrder $production_order, Model $previous, Model $workstation, Process $process): int
    {
        $produced = array_sum($this->stepProduced($production_order, $previous, $process));
        $passes = $this->passes($production_order, $previous, $process);

        if ($passes < 2) {
            return $produced;
        }

        $subprocess = $previous instanceof Operation ? $previous->subprocess()->first() : $previous;
        $entering = $this->previousInRoute($production_order, $previous, $process)
            ->map(fn (Model $step) => $this->unitsFrom($production_order, $step, $previous, $process))
            ->min() ?? $produced;
        $first_pass = min($produced, $entering);

        return $this->insideLoop($production_order, $workstation, $subprocess)
            ? $first_pass
            : max(0, $produced - ($passes - 1) * $first_pass);
    }

    /**
     * ¿El puesto está dentro de una vuelta que regresa al subproceso? (desde él, siguiendo la cadena, se llega a un
     * paso que regresa ahí)
     */
    protected function insideLoop(ProductionOrder $production_order, Model $workstation, Subprocess $target): bool
    {
        $sources = $this->beforeSubprocesses($target, $production_order, true)->pluck('id')->all();
        $start = $workstation instanceof Operation ? $workstation->subprocess()->first() : $workstation;
        $pending = $start instanceof Subprocess ? [$start] : [];
        $seen = [];

        while ($pending) {
            $current = array_pop($pending);
            if (in_array($current->id, $sources, true)) {
                return true;
            }
            if ($current->id === $target->id || isset($seen[$current->id])) {
                continue;
            }
            $seen[$current->id] = true;
            array_push($pending, ...$current->after_subprocesses()->get()
                ->filter(fn (Subprocess $next) => ! $this->chainReturns($current, $next))
                ->all());
        }

        return false;
    }

    /**
     * Unidades que devuelven al paso los regresos que el lote lleva (lo que ya terminó LAVADO ADICIONAL vuelve a
     * CENTRIFUGADO 1).
     */
    protected function returnedUnits(ProductionOrder $production_order, Model $workstation, Process $process): int
    {
        $subprocess = $workstation instanceof Operation ? $workstation->subprocess()->first() : $workstation;

        if (! $subprocess instanceof Subprocess) {
            return 0;
        }

        return (int) $this->beforeSubprocesses($subprocess, $production_order, true)
            ->sum(fn (Subprocess $source) => $this->leaves($source)
                ->filter(fn (Model $leaf) => $this->routeStep($production_order, $leaf, $process))
                ->map(fn (Model $leaf) => array_sum($this->stepProduced($production_order, $leaf, $process)))
                ->min() ?? 0);
    }

    /**
     * ¿Vale para el lote la conexión anterior → siguiente? Si el anterior pone tonos para ese siguiente
     * (settings.next_conditions.{id}.wash_tone_ids), solo con el tono de lavado de la ficha del lote.
     */
    public function chainApplies(Subprocess $previous, Subprocess $next, ?ProductionOrder $production_order): bool
    {
        $conditions = (array) ($previous->settings->next_conditions ?? []);
        $condition = (array) ($conditions[$next->id] ?? $conditions[(string) $next->id] ?? []);
        $tones = (array) ($condition['wash_tone_ids'] ?? []);

        if (! $tones || ! $production_order) {
            return true;
        }

        $production_order->loadMissing('technical_sheet');
        $listed = in_array((int) $production_order->technical_sheet?->wash_tone_id, array_map('intval', $tones), true);

        // Con except: todos los tonos menos estos (SECADO 2 → SEDA menos NEGRO)
        return ($condition['except'] ?? false) ? ! $listed : $listed;
    }

    /**
     * ¿El lote lleva el subproceso? Si va en la ficha, cuando la ficha lo trae (él o alguna de sus operaciones); si no,
     * siempre. Con «Solo si el lote hace el subproceso anterior», además debe llevar alguno de sus anteriores
     * (p. ej. CENTRIFUGADO 2 solo si hizo LAVADO 2).
     */
    public function subprocessApplies(ProductionOrder $production_order, Subprocess $subprocess, ?Collection $marked = null): bool
    {
        $marked ??= $this->markedInSheet($production_order);

        $in_sheet = ! ($subprocess->settings->in_technical_sheet ?? false)
            || $marked->contains(Subprocess::class.':'.$subprocess->id)
            || $this->ownOperations($subprocess)->contains(fn (Operation $operation) => $marked->contains(Operation::class.':'.$operation->id));

        if (! $in_sheet) {
            return false;
        }

        if (! ($subprocess->settings->only_after_previous ?? false)) {
            return true;
        }

        // El anterior puede ser una operación que abre la línea (LAVADO RESINA solo si la ficha trae RESINA)
        return $this->beforeSubprocesses($subprocess, $production_order)
            ->contains(fn (Subprocess $previous) => $this->subprocessApplies($production_order, $previous, $marked))
            // Una operación (la que abre la línea, p. ej. RESINA) cuenta solo si la ficha la trae: las operaciones se eligen siempre en la ficha
            || ($this->before_operations[$subprocess->id] ??= $subprocess->before_operations()->get())->contains(fn (Operation $operation) => $marked->contains(Operation::class.':'.$operation->id));
    }

    /**
     * ¿La ficha lleva el subproceso? Lo mismo que subprocessApplies, pero con lo que trae la ficha (aún sin guardar) y sin
     * un lote: un subproceso automático con «Solo si hace el anterior» solo va si la ficha trae alguno de sus anteriores
     * (p. ej. LAVADO 3 solo con EFECTO TRAPO).
     */
    public function sheetSubprocessApplies(Subprocess $subprocess, Collection $keys, ?int $wash_tone_id): bool
    {
        $production_order = (new ProductionOrder)->setRelation('technical_sheet', (new TechnicalSheet)->forceFill(['wash_tone_id' => $wash_tone_id]));

        return $this->subprocessApplies($production_order, $subprocess, $keys);
    }

    /**
     * Puestos donde se registra el trabajo de un subproceso: él mismo o sus últimas operaciones.
     */
    protected function leaves(Subprocess $subprocess): Collection
    {
        $operations = $this->ownOperations($subprocess);

        if ($operations->isEmpty()) {
            return collect([$subprocess]);
        }

        return $this->inSequence($subprocess, 'operation_flow')
            ? $operations->reject(fn (Operation $operation) => $operation->after_operations()->exists())->values()
            : $operations;
    }

    protected function inSequence(Model $parent, string $flow_key): bool
    {
        return ($parent->settings->{$flow_key} ?? Process::PARALLEL) === Process::SEQUENCE;
    }

    /**
     * Lugares de producción que NO pasan por el proceso. Un proceso marcado como lugar de producción
     * (settings.paragraph = production_place, p. ej. SATELITE) abre una rama desde donde se bifurca la línea hasta donde
     * se vuelve a juntar: los lotes de ese lugar no pasan por las otras ramas entre ese par de puntos, y los demás lotes
     * no pasan por esa rama. Sale de la línea de procesos (anteriores y siguientes), no de nombres.
     *
     * @return array<int, string>
     */
    public function excludedPlaces(Process $process): array
    {
        return $this->excluded_places[$process->id] ??= (function () use ($process) {
            $reach = function (Collection $from) {
                $seen = collect();
                $queue = $from->values();

                while ($queue->isNotEmpty()) {
                    $current = $queue->shift();

                    if ($seen->has($current->id)) {
                        continue;
                    }

                    $seen->put($current->id, $current);
                    $queue = $queue->concat($current->after_processes()->get());
                }

                return $seen->keys();
            };
            $excluded = [];

            foreach (Process::where('settings->paragraph', 'production_place')->get() as $place) {
                foreach ($place->before_processes()->get() as $fork) {
                    $others = $fork->after_processes()->get()->reject(fn (Process $next) => $next->id === $place->id);
                    $own = $reach(collect([$place]));
                    $rest = $reach($others);

                    // Lo que solo está en las otras ramas no es para los lotes de este lugar, y viceversa
                    if ($rest->diff($own)->contains($process->id)) {
                        $excluded[] = ProductionOrder::SATELITE;
                    }

                    if ($own->diff($rest)->contains($process->id)) {
                        $excluded[] = ProductionOrder::BLESS;
                    }
                }
            }

            return array_values(array_unique($excluded));
        })();
    }

    /**
     * Mensaje si el lote no pasa por el proceso según su lugar de producción (otra rama de la línea), o null.
     */
    public function branchError(ProductionOrder $production_order, Process $process): ?string
    {
        if (! in_array($production_order->production_place, $this->excludedPlaces($process), true)) {
            return null;
        }

        $production_order->loadMissing(['technical_sheet' => ['product']]);
        $lot = collect([$production_order->technical_sheet?->product?->code, $production_order->cut])->filter()->implode('-');

        return $production_order->production_place === ProductionOrder::SATELITE
            ? "{$lot} se produce en satélite: no pasa por {$process->name}."
            : "{$lot} se produce en la empresa: no pasa por {$process->name}.";
    }

    /**
     * Por qué no se puede quitar un subproceso u operación de la ficha, o null: un subproceso automático (no se elige en
     * la ficha) va siempre; y si algún lote de la ficha ya se trabajó ahí (o en una operación del subproceso), quitarlo
     * cambiaría la ruta de lo ya hecho.
     */
    public function sheetRemovalError(Model $model, int $technical_sheet_id): ?string
    {
        if ($model instanceof Subprocess && ! ($model->settings->in_technical_sheet ?? false)) {
            return "{$model->name} es automático: la referencia siempre pasa por ahí, no se puede quitar de la ficha.";
        }

        $workstations = collect([$model])->concat($model instanceof Subprocess ? $model->operations : []);
        $worked = ProductivityDetail::with(['production_order' => ['technical_sheet' => ['product']]])
            ->whereNotNull('settings->scanned_at')
            ->where(fn ($query) => $workstations->each(fn (Model $workstation) => $query->orWhere(fn ($own) => $own
                ->where('model_type', $workstation::class)
                ->where('model_id', $workstation->id))))
            ->whereHas('production_order', fn ($order) => $order->where('technical_sheet_id', $technical_sheet_id))
            ->first();

        if (! $worked) {
            return null;
        }

        $lot = collect([$worked->production_order?->technical_sheet?->product?->code, $worked->production_order?->cut])->filter()->implode('-');

        return "No se puede quitar {$model->name} de la ficha: el lote {$lot} ya se trabajó ahí.";
    }

    /**
     * Lo que la ficha técnica trae en secuencia sin su anterior, por id del puesto: una operación de un subproceso con
     * operaciones en secuencia debe tener en la ficha al menos una de sus anteriores (para subprocesos en secuencia está
     * por decidir). Un subproceso anterior que no se elige en la ficha (automático) cuenta como cumplido; las
     * flechas con condición de tono solo cuentan si aplican al tono de la ficha y los regresos no son anteriores.
     *
     * @param  Collection<int, string>  $keys  «Modelo:id» de lo que la ficha tendrá marcado
     * @return array<int, string>
     */
    public function sheetSequenceErrors(Collection $keys, ?int $wash_tone_id): array
    {
        $production_order = (new ProductionOrder)->setRelation('technical_sheet', (new TechnicalSheet)->forceFill(['wash_tone_id' => $wash_tone_id]));
        $marked = fn (Model $model) => $keys->contains($model::class.':'.$model->id)
            || ($model instanceof Subprocess && $model->operations->contains(fn (Operation $operation) => $keys->contains(Operation::class.':'.$operation->id)));
        // Como en la ruta: una operación solo cuenta si la ficha la trae; un subproceso que no se elige en la ficha va siempre
        $automatic = fn (Model $model) => $model instanceof Subprocess && ! ($model->settings->in_technical_sheet ?? false);
        $errors = [];

        foreach ($keys as $key) {
            [$type, $id] = explode(':', $key);
            $model = in_array($type, [Operation::class, Subprocess::class], true) ? $type::find($id) : null;

            $subprocess = $model instanceof Operation ? $model->subprocess()->first() : null;
            // En secuencia, su anterior en la cadena; en paralelo, de las que depende
            $previous = match (true) {
                $model instanceof Operation && $subprocess => $model->before_operations()->get(),
                // Pendiente de decidir con el usuario (2026-11-06): si también aplica a subprocesos en secuencia (Lavandería)
                default => collect(),
            };

            // En paralelo depende de todas: la ficha debe traerlas todas
            if ($subprocess && ! $this->inSequence($subprocess, 'operation_flow')) {
                $missing = $previous->reject(fn (Model $item) => $marked($item));

                if ($missing->isNotEmpty()) {
                    $errors[(int) $id] = "{$model->name} depende de {$missing->pluck('name')->implode(' y ')}: para registrarlo en la ficha, también debe tenerlo.";
                }

                continue;
            }

            if ($previous->isEmpty() || $previous->contains(fn (Model $item) => $automatic($item) || $marked($item))) {
                continue;
            }

            $names = $previous->pluck('name')->implode(' o ');
            $errors[(int) $id] = $previous->count() === 1
                ? "{$model->name} va en secuencia después de {$names}: para registrarlo en la ficha, también debe tener {$names}."
                : "{$model->name} va en secuencia después de {$names}: para registrarlo en la ficha, debe tener al menos uno de ellos.";
        }

        return $errors;
    }

    /**
     * Estado del lote en los puestos anteriores de la cadena: si está listo para trabajar en este puesto, qué
     * falta (mensaje) y a qué hora terminó el último anterior (para la hora de inicio).
     *
     * En un nivel en paralelo, un puesto por unidades no espera a que el anterior termine: le basta con que esté en curso o
     * que empiece con él ($starting, los puestos que el mismo grupo inicia juntos), y trabaja lo que el anterior suelta.
     *
     * @return array{ready: bool, message: ?string, previous: array, previous_end: ?string}
     */
    public function sequenceStatus(ProductionOrder $production_order, Model $workstation, ?Process $process = null, ?Collection $starting = null): array
    {
        $production_order->loadMissing(['technical_sheet' => ['product']]);
        $lot = collect([$production_order->technical_sheet?->product?->code, $production_order->cut])->filter()->implode('-');

        $groups = $this->previousInRoute($production_order, $workstation, $process)->map(fn (Model $previous) => collect([$previous]));
        $own_process = $process ?? $this->processOfWorkstation($workstation);

        if ($own_process && $this->typedUnits($own_process, $workstation)) {
            return $this->unitsSequence($production_order, $workstation, $own_process, $groups, $lot);
        }

        $flowing = fn (Collection $group) => $own_process && $this->programmable($own_process, $workstation)
            && $group->contains(fn (Model $step) => $this->sharesParallel($workstation, $step, $own_process));
        $together = fn (Collection $group) => $group->contains(fn (Model $step) => ($starting ?? collect())
            ->contains(fn (Model $item) => $item::class === $step::class && $item->id === $step->id));

        $previous = $groups->map(fn (Collection $group) => array_merge($this->groupStatus($production_order, $group, null), [
            'flowing' => $flowing($group),
            'together' => $together($group),
        ]))->values();

        $blocking = $previous->first(fn ($item) => $item['status'] !== 'finished'
            && ! ($item['flowing'] && ($item['status'] === 'in_progress' || $item['together'])));

        return [
            'ready' => ! $blocking,
            'message' => match ($blocking['status'] ?? null) {
                'missing' => "El lote {$lot} no ha pasado por {$blocking['name']}.",
                'in_progress' => "El lote {$lot} aún se está trabajando en {$blocking['name']}.",
                default => null,
            },
            'previous' => $previous->all(),
            'previous_end' => $blocking ? null : $previous->pluck('end')->filter()->max(),
        ];
    }

    /**
     * Secuencia por unidades (tandas): el lote avanza por partes. Una tanda puede empezar si los pasos anteriores ya
     * hicieron unidades que este paso aún no ha tomado (lo hecho aquí más lo que está en curso). En un grupo «al menos
     * uno» cuenta el que más lleva; entre grupos, el que menos.
     */
    protected function unitsSequence(ProductionOrder $production_order, Model $workstation, Process $process, Collection $groups, string $lot): array
    {
        $previous = $groups->map(function (Collection $group) use ($production_order, $process, $workstation) {
            $units = $group->map(fn (Model $step) => $this->unitsFrom($production_order, $step, $workstation, $process))->max() ?? 0;

            return [
                'name' => $group->pluck('name')->implode(' o '),
                'status' => $units > 0 ? 'finished' : 'missing',
                'end' => null,
                'units' => (int) $units,
            ];
        })->values();

        if ($previous->isEmpty()) {
            return ['ready' => true, 'message' => null, 'previous' => [], 'previous_end' => null, 'available' => null];
        }

        $step = $this->routeStep($production_order, $workstation, $process) ?? $workstation;
        $taken = array_sum($this->stepProduced($production_order, $step, $process))
            + $this->pendingUnits(ProductivityDetail::where('production_order_id', $production_order->id)
                ->whereNotNull('settings->scanned_at')
                ->get()
                ->filter(fn ($row) => $this->stepWorkstations($step, $process)->contains(fn (Model $item) => $item::class === $row->model_type && $item->id === $row->model_id)));
        $slowest = $previous->sortBy('units')->first();
        // Lo que regresa (segunda pasada) se suma a lo que llega por la cadena
        $returned = $this->returnedUnits($production_order, $workstation, $process);
        $slowest['units'] += $returned;
        $available = max(0, $slowest['units'] - $taken);

        return [
            'ready' => $available > 0,
            'message' => $available > 0 ? null : ($slowest['units'] > 0
                ? "El lote {$lot} no tiene unidades listas: {$slowest['name']} lleva {$slowest['units']} y aquí ya se tomaron {$taken}."
                : "El lote {$lot} no ha pasado por {$slowest['name']}."),
            'previous' => $previous->all(),
            'previous_end' => null,
            'available' => $available,
        ];
    }

    /**
     * Estado del lote en un grupo de pasos (se cumple con uno): terminado si alguno terminó; en curso si alguno se
     * está trabajando; si no, falta. Con línea, un subproceso se mira por sus puestos (él o sus operaciones).
     */
    protected function groupStatus(ProductionOrder $production_order, Collection $group, ?Process $process): array
    {
        $states = $group->flatMap(fn (Model $step) => $process ? $this->stepWorkstations($step, $process) : [$step])
            ->map(function (Model $workstation) use ($production_order) {
                $detail = ProductivityDetail::where('model_type', $workstation::class)
                    ->where('model_id', $workstation->id)
                    ->where('production_order_id', $production_order->id)
                    ->whereNotNull('settings->scanned_at')
                    ->latest('id')
                    ->first();

                return [
                    'status' => ! $detail ? 'missing' : ($detail->end ? 'finished' : 'in_progress'),
                    'end' => $detail?->end?->format('Y-m-d H:i:s'),
                ];
            });

        $finished = $states->where('status', 'finished');

        return [
            'name' => $group->pluck('name')->implode(' o '),
            'status' => $finished->isNotEmpty() ? 'finished' : ($states->contains('status', 'in_progress') ? 'in_progress' : 'missing'),
            'end' => $finished->pluck('end')->filter()->max(),
        ];
    }

    /**
     * Mensaje si el puesto tiene anteriores en la cadena (según la ruta del lote) y el lote todavía no está programado ni
     * trabajado en alguno de ellos, en cualquier turno: no se programa RELOJERA sin haber programado o hecho PARCHE.
     */
    public function previousScheduledError(ProductionOrder $production_order, Model $workstation, Process $process): ?string
    {
        $missing = $this->previousMissing($production_order, $workstation, $process);

        if ($missing->isEmpty()) {
            return null;
        }

        $names = $missing->pluck('name')->implode(' y ');
        // La anterior es obligatoria por la regla del proceso, pero la ficha no la trae: hay que corregir la ficha
        $outside = $missing->reject(fn (Model $previous) => $this->routeStep($production_order, $previous, $process));

        return $outside->isNotEmpty()
            ? "{$workstation->name} va después de {$outside->pluck('name')->implode(' y ')} y la ficha técnica de esta referencia no lo trae: agrégalo en la ficha técnica para poder programarla."
            : "{$workstation->name} va después de {$names}: programa primero esta referencia en {$names}.";
    }

    /**
     * ¿La anterior es obligatoria por la regla del proceso aunque la ficha no la traiga? Sí entre operaciones del mismo
     * subproceso: su anterior en la cadena (en secuencia) o una de la que depende (en paralelo, RELOJERA depende de PARCHE). Los subprocesos «Según la ficha» que la referencia
     * no lleva se siguen saltando; los «Automático» ya van siempre.
     */
    protected function sequenceRequired(Model $workstation, Model $previous): bool
    {
        if (! $workstation instanceof Operation || ! $previous instanceof Operation || $this->opensLine($previous)) {
            return false;
        }

        $subprocess = $previous->subprocess()->first();

        // En secuencia es su anterior en la cadena; en paralelo, una de la que depende
        return $subprocess && $workstation->subprocess()->first()?->id === $subprocess->id;
    }

    /**
     * Anteriores de la cadena (según la ruta del lote) donde el lote no tiene ningún registro, programado o trabajado, de
     * ningún turno. Con un turno, sus filas sin iniciar no cuentan: la pantalla de programación las maneja en vivo (al
     * poner o quitar el lote en el anterior, aparece o se quita en el siguiente).
     */
    public function previousMissing(ProductionOrder $production_order, Model $workstation, Process $process, ?int $productivity_id = null): Collection
    {
        return $this->previousInRoute($production_order, $workstation, $process)
            ->reject(fn (Model $previous) => ProductivityDetail::where('model_type', $previous::class)
                ->where('model_id', $previous->id)
                ->where('production_order_id', $production_order->id)
                ->when($productivity_id, fn ($query) => $query->where(fn ($rows) => $rows
                    ->where('productivity_id', '!=', $productivity_id)
                    ->orWhereNotNull('settings->scanned_at')))
                ->exists())
            ->values();
    }

    /**
     * Puestos anteriores de la cadena que la referencia sí lleva: los que su ficha no lleva se saltan y se mira lo que
     * está antes de ellos (A → B → C sin B: C espera a A). Salvo la operación anterior de un subproceso en secuencia, que
     * es obligatoria aunque la ficha no la traiga (sequenceRequired).
     */
    protected function previousInRoute(ProductionOrder $production_order, Model $workstation, ?Process $process, array $seen = []): Collection
    {
        if (! $process) {
            return $this->previousWorkstations($workstation, $production_order);
        }

        return $this->previousWorkstations($workstation, $production_order)
            ->flatMap(function (Model $previous) use ($production_order, $workstation, $process, $seen) {
                $key = $previous::class.':'.$previous->id;

                if (in_array($key, $seen, true)) {
                    return [];
                }

                // La regla del proceso manda sobre la ficha: en un subproceso con sus operaciones en secuencia, la anterior es
                // obligatoria aunque la ficha no la traiga (RELOJERA sin PARCHE en la ficha igual espera a PARCHE)
                if ($this->routeStep($production_order, $previous, $process) || $this->sequenceRequired($workstation, $previous)) {
                    return [$previous];
                }

                // Operación en paralelo que la ficha no trae, de un subproceso que sí lleva: la cubren sus hermanas
                // marcadas (LAVADO 1 espera solo BISTURÍ, no lo que está antes de manualidades)
                if ($previous instanceof Operation && ! $this->opensLine($previous)
                    && ($subprocess = $previous->subprocess()->first())
                    && ! $this->inSequence($subprocess, 'operation_flow')
                    && $this->ownOperations($subprocess)->contains(fn (Operation $sibling) => $this->routeStep($production_order, $sibling, $process))) {
                    return [];
                }

                return $this->previousInRoute($production_order, $previous, $process, [...$seen, $key])->all();
            })
            ->unique(fn (Model $previous) => $previous::class.':'.$previous->id)
            ->values();
    }

    /**
     * ¿El puesto es el último de su cadena? Un subproceso sin siguientes (si su proceso los tiene en secuencia, o es
     * el único) y una operación sin siguientes dentro de un subproceso que a su vez es el último.
     */
    public function isLast(Model $workstation): bool
    {
        return match (true) {
            $workstation instanceof Operation => ! $this->opensLine($workstation)
                && $this->isLastChild($workstation, $workstation->subprocess()->first(), 'operation_flow', 'operations', 'after_operations')
                && ($subprocess = $workstation->subprocess()->first()) && $this->isLast($subprocess),
            $workstation instanceof Subprocess => $this->isLastChild($workstation, $workstation->process()->first(), 'subprocess_flow', 'subprocesses', 'after_subprocesses'),
            default => true,
        };
    }

    protected function isLastChild(Model $child, ?Model $parent, string $flow_key, string $children, string $after): bool
    {
        if (! $parent || $child->{$after}()->exists()) {
            return false;
        }

        return ($parent->settings->{$flow_key} ?? Process::PARALLEL) === Process::SEQUENCE
            || $parent->{$children}()->count() === 1;
    }

    /**
     * Padre del puesto que tiene varios hijos en paralelo (proceso o subproceso), o null. En el primer proceso no se
     * permite: sin una cadena no hay un único último que cree los ingresos.
     */
    public function parallelParent(Model $workstation): ?Model
    {
        $levels = match (true) {
            $workstation instanceof Operation => [
                [$workstation->subprocess()->first(), 'operation_flow', 'operations'],
                [$workstation->subprocess()->first()?->process()->first(), 'subprocess_flow', 'subprocesses'],
            ],
            $workstation instanceof Subprocess => [[$workstation->process()->first(), 'subprocess_flow', 'subprocesses']],
            default => [],
        };

        foreach ($levels as [$parent, $flow_key, $children]) {
            if ($parent && ($parent->settings->{$flow_key} ?? Process::PARALLEL) !== Process::SEQUENCE && $parent->{$children}()->count() > 1) {
                return $parent;
            }
        }

        return null;
    }

    /**
     * ¿Al finalizar en este puesto se piden unidades y se crean los ingresos? Solo en el último puesto de la cadena
     * del primer proceso.
     */
    public function createsIngress(Process $process, Model $workstation): bool
    {
        return $process->isFirst() && $this->isLast($workstation);
    }

    /**
     * Empleados que pueden trabajar en el puesto: los asignados a él (asignar un proceso asigna sus subprocesos y
     * operaciones), activos y que no estén en otro registro en curso.
     */
    /**
     * Capacidad de la regla para las personas del puesto:
     * - Por lotes: la capacidad con exactamente ese número de personas, o null si la regla no la define.
     * - Por unidades: la única capacidad es lo que tarda una persona en una unidad (base_time); con más personas una
     *   unidad sale más rápido: time = base_time ÷ personas.
     */
    public function capacity(Rule $rule, Model $workstation, int $people): ?array
    {
        if ($people < 1) {
            return null;
        }

        if (! $this->measuredByUnits($workstation)) {
            return $rule->capacityFor($people);
        }

        $base = $rule->capacities()[0] ?? null;

        return $base ? [
            'people' => $people,
            'time' => round($base['time'] / $people, 4),
            'time_unit' => $base['time_unit'],
            'base_time' => $base['time'],
        ] : null;
    }

    /**
     * Por qué no hay capacidad para el puesto (sin empleados, regla sin tiempo o sin ese número de personas), o null.
     */
    public function capacityError(Rule $rule, Model $workstation, int $people): ?string
    {
        if ($people < 1) {
            return "No hay empleados asociados a {$workstation->name}: no se puede iniciar sin mano de obra. Asócialos en Empleados.";
        }

        if (! $rule->capacities()) {
            return "La regla {$rule->name} no tiene el tiempo registrado. Regístralo en Reglas.";
        }

        return sprintf(
            'La regla %s no tiene capacidad para %d %s. Agrégala en Reglas o ajusta las personas de %s.',
            $rule->name,
            $people,
            $people === 1 ? 'persona' : 'personas',
            $workstation->name
        );
    }

    /**
     * ¿El registro es del mismo proceso y de otro paso del lote que el puesto?
     */
    protected function otherStep(ProductionOrder $production_order, Model $workstation, ProductivityDetail $row, Process $process): bool
    {
        if (! $row->model || $row->productivity?->model_type !== Process::class || $row->productivity?->model_id !== $process->id) {
            return false;
        }

        $marked = $this->markedInSheet($production_order);
        $own = $this->routeStep($production_order, $workstation, $process, $marked) ?? $workstation;
        $other = $this->routeStep($production_order, $row->model, $process, $marked) ?? $row->model;

        return $own::class !== $other::class || $own->id !== $other->id;
    }

    /**
     * ¿Los dos puestos están dentro de un nivel en paralelo, donde se pueden trabajar a la vez? Sí si el proceso tiene sus
     * subprocesos en paralelo (ALISTAMIENTO, CONFECCION) o si son operaciones de un subproceso que las tiene en paralelo
     * (BISTURÍ y MOTOR). En un proceso en secuencia (CORTE) nunca.
     */
    public function sharesParallel(Model $one, Model $other, Process $process): bool
    {
        if (! $this->inSequence($process, 'subprocess_flow')) {
            return true;
        }

        $subprocess = $one instanceof Operation ? $one->subprocess()->first() : null;

        return $subprocess && $other instanceof Operation
            && $other->subprocess()->first()?->id === $subprocess->id
            && ! $this->inSequence($subprocess, 'operation_flow');
    }

    /**
     * Grupos de puestos del proceso que comparten un nivel en paralelo (la regla de sharesParallel), para la programación:
     * todos los puestos si el proceso va en paralelo; si no, las operaciones de cada subproceso en paralelo.
     *
     * @return array<int, array<int, array{model_type: string, model_id: int}>>
     */
    public function parallelGroups(Process $process): array
    {
        $payload = fn (Collection $workstations) => $workstations
            ->map(fn (Model $workstation) => ['model_type' => $workstation::class, 'model_id' => $workstation->id])
            ->values()
            ->all();

        if (! $this->inSequence($process, 'subprocess_flow')) {
            return [$payload($this->workstationModels($process))];
        }

        return $process->subprocesses()->get()
            ->reject(fn (Subprocess $subprocess) => $this->inSequence($subprocess, 'operation_flow'))
            ->map(fn (Subprocess $subprocess) => $subprocess->operations()->get())
            ->filter(fn (Collection $operations) => $operations->count() > 1)
            ->map($payload)
            ->values()
            ->all();
    }

    /**
     * Puestos anteriores del lote en su cadena, de todos los niveles hacia atrás (BOLSILLO: RELOJERA y PARCHE).
     */
    public function chainAncestors(ProductionOrder $production_order, Model $workstation, Process $process): Collection
    {
        $found = collect();
        $queue = $this->previousInRoute($production_order, $workstation, $process);

        while ($queue->isNotEmpty()) {
            $previous = $queue->shift();
            $key = $previous::class.':'.$previous->id;

            if ($found->has($key)) {
                continue;
            }

            $found->put($key, $previous);
            $queue->push(...$this->previousInRoute($production_order, $previous, $process)->all());
        }

        return $found->values();
    }

    /**
     * ¿Uno de los dos puestos va antes que el otro en la cadena del lote (PARCHE → RELOJERA)?
     */
    public function chainRelated(ProductionOrder $production_order, Model $one, Model $other, Process $process): bool
    {
        $before = fn (Model $step, Model $previous) => $this->chainAncestors($production_order, $step, $process)
            ->contains(fn (Model $item) => $item::class === $previous::class && $item->id === $previous->id);

        return $before($one, $other) || $before($other, $one);
    }

    /**
     * ¿Son puestos independientes para el lote? Están en un nivel en paralelo y ninguno va antes que el otro (PINZAS y
     * PARCHE): cada uno trabaja sus piezas sin esperar al otro, a la hora y con el grupo que sea.
     */
    public function independentSteps(ProductionOrder $production_order, Model $one, Model $other, Process $process): bool
    {
        return $this->sharesParallel($one, $other, $process) && ! $this->chainRelated($production_order, $one, $other, $process);
    }

    /**
     * Mensaje si el puesto quedaría antes que un puesto anterior de su cadena con el mismo lote en el turno, dentro de un
     * nivel en paralelo: con el mismo grupo arranca a la vez o después; con otro grupo, cuando termina el anterior.
     */
    public function chainOrderError(Productivity $shift, ProductionOrder $production_order, Model $workstation, Process $process, Carbon $start, Collection $employees, ?int $except_id = null, array $pending = []): ?string
    {
        $ancestors = $this->chainAncestors($production_order, $workstation, $process)
            ->filter(fn (Model $previous) => $this->sharesParallel($workstation, $previous, $process));

        if ($ancestors->isEmpty()) {
            return null;
        }

        $same = fn (ProductivityDetail $row) => $this->rowEmployees($row, $pending)->sort()->values()->all() === $employees->sort()->values()->all();

        $row = $shift->productivity_details()
            ->with(['model', 'employees'])
            ->where('production_order_id', $production_order->id)
            ->whereNull('end')
            ->when($except_id, fn ($query) => $query->whereKeyNot($except_id))
            ->get()
            ->filter(fn (ProductivityDetail $row) => $ancestors->contains(fn (Model $previous) => $previous::class === $row->model_type && $previous->id === $row->model_id))
            ->first(fn (ProductivityDetail $row) => $start->lt($same($row) ? $this->scheduledStart($row)->startOfMinute() : $this->busyUntil($row)->ceilMinute()));

        if (! $row) {
            return null;
        }

        $production_order->loadMissing(['technical_sheet' => ['product']]);
        $lot = collect([$production_order->technical_sheet?->product?->code, $production_order->cut])->filter()->implode('-');
        $from = $this->scheduledStart($row)->format('g:i A');
        $until = $this->busyUntil($row)->ceilMinute()->format('g:i A');

        return $same($row)
            ? "{$workstation->name} va después de {$row->model->name} en la cadena: con el mismo grupo arranca a la vez, desde las {$from}."
            : "{$workstation->name} va después de {$row->model->name} en la cadena: {$lot} está programada allá de {$from} a {$until} con otro grupo. Prográmala desde las {$until}.";
    }

    /**
     * ¿El registro es del mismo lote, en otro puesto del proceso dentro de un nivel en paralelo, y lo hacen exactamente esos
     * empleados? Solo así una persona trabaja dos puestos a la vez: el mismo grupo hace los dos, sin esperar a nadie
     * (bisturí y motor, parche y relojera). Cada registro mide su eficiencia contra el tiempo de su operación. En un
     * proceso en secuencia no: una persona está en un solo paso. Las tandas siguen su propia regla.
     */
    public function sameGroupOverlap(Model $workstation, ProductivityDetail $row, ProductionOrder $production_order, Process $process, Collection $employees, array $pending = []): bool
    {
        return $row->production_order_id === $production_order->id
            && $row->model
            && $row->productivity?->model_type === Process::class
            && $row->productivity?->model_id === $process->id
            && ($row->model_type !== $workstation::class || $row->model_id !== $workstation->id)
            && ! $this->typedUnits($process, $workstation)
            && ! $this->typedUnits($process, $row->model)
            && $this->sharesParallel($workstation, $row->model, $process)
            && $this->rowEmployees($row, $pending)->sort()->values()->all() === $employees->sort()->values()->all();
    }

    /**
     * Registros que el mismo grupo trabaja a la vez con este, del mismo lote en el turno, en otros puestos con +1 de un
     * nivel en paralelo (PARCHE + RELOJERA): la estación los maneja como uno solo y un +1 suma en todos. Si el registro ya
     * inició, los que siguen en curso; si no, los programados sin iniciar que se cruzan con su franja.
     */
    public function companions(ProductivityDetail $detail): Collection
    {
        $detail->loadMissing(['productivity' => ['model'], 'model', 'employees', 'production_order']);
        $process = $detail->productivity?->model;

        if (! $process instanceof Process || ! $detail->model || ! $this->countsWithPlus($process, $detail->model)) {
            return collect();
        }

        $started = isset($detail->settings->scanned_at);

        return ProductivityDetail::with(['model', 'productivity', 'employees', 'production_order'])
            ->where('productivity_id', $detail->productivity_id)
            ->where('production_order_id', $detail->production_order_id)
            ->whereKeyNot($detail->id)
            ->whereNull('end')
            ->when($started, fn ($query) => $query->inProgress(), fn ($query) => $query->scheduled())
            ->get()
            ->filter(fn (ProductivityDetail $row) => $row->model
                && $this->countsWithPlus($process, $row->model)
                && $this->sameGroupOverlap($detail->model, $row, $detail->production_order, $process, $detail->employees->pluck('id'))
                && $this->scheduledTogether($detail, $row))
            ->values();
    }

    /**
     * ¿Los dos registros se programaron a la vez (sus franjas programadas se cruzan)? Así se decide si el mismo grupo los
     * trabaja juntos. Las horas reales no cuentan: que PARCHE se atrase no junta lo que se programó separado (PARCHE de 7 a
     * 8 y RELOJERA a la 1); ahí, si el grupo sigue en PARCHE, sale ocupado en RELOJERA y se reemplaza.
     */
    public function scheduledTogether(ProductivityDetail $one, ProductivityDetail $other): bool
    {
        $end = fn (ProductivityDetail $row) => isset($row->settings->scheduled->end)
            ? Carbon::parse($row->settings->scheduled->end)
            : $this->scheduledStart($row)->addMinute();

        return $this->scheduledStart($one)->lt($end($other)) && $this->scheduledStart($other)->lt($end($one));
    }

    /**
     * Acompañantes programados que se inician con el registro: les quedan unidades, la ficha los lleva, están en su franja
     * y sus anteriores en la cadena ya empezaron o empiezan con ellos.
     */
    public function companionsToStart(ProductivityDetail $scheduled, Process $process): Collection
    {
        $companions = $this->companions($scheduled);
        $starting = $companions->pluck('model')->push($scheduled->model);

        return $companions->filter(fn (ProductivityDetail $row) => $this->counters($scheduled->production_order, $process, $row->model)['current']
            && ! $this->routeError($scheduled->production_order, $row->model, $process)
            && ! $this->scheduleWindowError($scheduled->productivity, $row->model, $scheduled->production_order)
            && $this->sequenceStatus($scheduled->production_order, $row->model, $process, $starting)['ready'])
            ->values();
    }

    /**
     * Mensaje si el registro no puede sumar un +1 ahora, o null.
     */
    public function plusError(ProductivityDetail $detail, Process $process): ?string
    {
        if ($detail->productivity->endsAt()->lte(now())) {
            return 'El turno ya terminó; finaliza el trabajo.';
        }

        if ($this->openStop($detail)) {
            return 'Hay una parada en curso; termínala antes de seguir contando.';
        }

        // El único límite es el lote completo en el paso: la parte de la programación es una meta, no un tope (el módulo
        // que termina su parte sigue sumando y ayuda al otro)
        if (! $this->counters($detail->production_order, $process, $detail->model)['current']) {
            return 'El lote ya está completo: no quedan unidades por hacer.';
        }

        // Solo se cuenta lo que ya soltó el puesto programado antes: lo demás sigue físicamente allá
        $limit = $this->unitsLimitOf($detail, $process);

        return $limit && $limit['available'] < 1
            ? "Ya hicieron todas las unidades disponibles ({$limit['limit']}). {$limit['from']} todavía no ha soltado más."
            : null;
    }

    /**
     * Empleados de un registro: el grupo nuevo si se está guardando en el mismo envío o, si no, el guardado.
     */
    public function rowEmployees(ProductivityDetail $row, array $pending = []): Collection
    {
        return isset($pending[$row->id])
            ? collect($pending[$row->id])->map(fn ($id) => (int) $id)
            : $row->employees->pluck('id');
    }

    /**
     * ¿El registro en curso lo hace el mismo grupo que tiene programado el lote en este puesto? Entonces no están ocupados
     * para iniciarlo.
     */
    protected function sameGroupScheduled(Model $workstation, ProductivityDetail $row, ?int $production_order_id): bool
    {
        if (! $production_order_id || $row->production_order_id !== $production_order_id || ! $row->productivity?->model instanceof Process) {
            return false;
        }

        $production_order = ProductionOrder::find($production_order_id);

        // Solo si se programaron a la vez: programados separados, el grupo que sigue en el otro puesto está ocupado
        return $this->scheduledDetails($row->productivity, $workstation, $production_order)
            ->contains(fn ($scheduled) => $this->sameGroupOverlap($workstation, $row, $production_order, $row->productivity->model, $scheduled->employees->pluck('id'))
                && $this->scheduledTogether($scheduled, $row));
    }

    /**
     * Unidades que el puesto puede trabajar según la programación, o null si no tiene límite. No es una secuencia: no
     * necesita que el otro termine, sino prendas libres. El límite es lo que lleva el puesto programado antes (el más
     * cercano en la hora) que sigue en curso; las demás prendas siguen físicamente allá. Si ese puesto no inició o ya
     * finalizó, se salta; el que empezó después de este tampoco lo limita. Los de la misma hora no se limitan entre sí.
     * Si al iniciar el anterior no había hecho nada, el que inicia toma el lote (settings.took_lot): trabaja sin límite y
     * el otro queda limitado por él. Las tandas siguen su propia regla.
     *
     * @return array{from: string, limit: int, available: int, start: string}|null
     */
    public function unitsLimit(Productivity $shift, ProductionOrder $production_order, Model $workstation, Process $process, Carbon $programmed, ?Carbon $scanned_at = null): ?array
    {
        if (! $this->programmable($process, $workstation) || $this->typedUnits($process, $workstation)) {
            return null;
        }

        $rows = $shift->productivity_details()
            ->with(['model', 'productivity'])
            ->where('production_order_id', $production_order->id)
            ->inProgress()
            ->get()
            ->filter(fn (ProductivityDetail $row) => $row->model
                && ! $this->typedUnits($process, $row->model)
                && $this->otherStep($production_order, $workstation, $row, $process)
                // En paralelo, un puesto independiente no le suelta prendas a este (PINZAS no limita a RELOJERA)
                && ! $this->independentSteps($production_order, $workstation, $row->model, $process)
                && (isset($row->settings->took_lot)
                    || ($this->scheduledStart($row)->lt($programmed) && (! $scanned_at || Carbon::parse($row->settings->scanned_at)->lte($scanned_at)))));

        if ($rows->isEmpty()) {
            return null;
        }

        $closest = $rows->max(fn (ProductivityDetail $row) => $this->scheduledStart($row)->timestamp);
        $from = $rows->filter(fn (ProductivityDetail $row) => $this->scheduledStart($row)->timestamp === $closest)
            ->map(fn (ProductivityDetail $row) => [
                'from' => $row->model->name,
                'limit' => $this->counters($production_order, $process, $row->model)['produced'],
                'start' => $row->start->format('Y-m-d H:i:s'),
                // Va antes en la cadena de un nivel en paralelo (PARCHE para RELOJERA): sin unidades de allá no se toma
                // el lote, se espera
                'chain' => $this->sharesParallel($workstation, $row->model, $process)
                    && $this->chainRelated($production_order, $workstation, $row->model, $process),
            ])
            ->sortByDesc('limit')
            ->first();

        return [...$from, 'available' => max(0, $from['limit'] - $this->counters($production_order, $process, $workstation)['produced'])];
    }

    /**
     * Límite de un registro ya iniciado.
     */
    public function unitsLimitOf(ProductivityDetail $row, Process $process): ?array
    {
        if (isset($row->settings->took_lot)) {
            return null;
        }

        return $this->unitsLimit($row->productivity, $row->production_order, $row->model, $process, $this->scheduledStart($row), Carbon::parse($row->settings->scanned_at ?? now()));
    }

    /**
     * Mensaje si este puesto ya alcanzó lo que soltó el programado antes, o null. Si el anterior no ha hecho nada no es
     * error: este puesto toma el lote.
     */
    public function noUnitsError(?array $limit, ProductionOrder $production_order): ?string
    {
        if (! $limit || $limit['limit'] < 1 || $limit['available'] > 0) {
            return null;
        }

        $production_order->loadMissing(['technical_sheet' => ['product']]);
        $lot = collect([$production_order->technical_sheet?->product?->code, $production_order->cut])->filter()->implode('-');

        return "{$limit['from']} aún no ha soltado unidades de {$lot}: no hay unidades disponibles para este puesto.";
    }

    /**
     * Mensaje si el lote ya está programado en esa franja en otro paso del mismo proceso con otro grupo: uno tendría que
     * esperar las prendas del otro, y mientras puede trabajar otra referencia. Sí se cruzan el mismo paso (se reparten el
     * lote), el mismo grupo, las tandas y otros procesos (el lote puede estar partido).
     */
    public function lotOverlapError(ProductionOrder $production_order, Model $workstation, Process $process, Carbon $start, Carbon $end, Collection $employees, ?int $except_id = null, array $pending = []): ?string
    {
        if ($this->typedUnits($process, $workstation)) {
            return null;
        }

        $row = $this->overlappingRows($start, $end, $except_id)
            ->where('production_order_id', $production_order->id)
            ->first(fn (ProductivityDetail $row) => $this->otherStep($production_order, $workstation, $row, $process)
                && ! $this->typedUnits($process, $row->model)
                // En paralelo, los puestos independientes del lote van a la hora y con el grupo que sea
                && ! $this->independentSteps($production_order, $workstation, $row->model, $process)
                && ! $this->sameGroupOverlap($workstation, $row, $production_order, $process, $employees, $pending));

        if (! $row) {
            return null;
        }

        $production_order->loadMissing(['technical_sheet' => ['product']]);
        $lot = collect([$production_order->technical_sheet?->product?->code, $production_order->cut])->filter()->implode('-');
        $until = $this->busyUntil($row)->format('g:i A');

        return "{$lot} está programada en {$row->model->name} de {$row->start->format('g:i A')} a {$until} con otro grupo. Programa {$workstation->name} con el mismo grupo o después de las {$until}.";
    }

    /**
     * Empleados ocupados en un trabajo en curso => puesto donde están, sin contar los trabajos que se pueden hacer a la
     * vez con el puesto (el mismo grupo en otro puesto del lote).
     */
    public function busyMap(?Model $workstation = null, ?int $production_order_id = null): Collection
    {
        return ProductivityDetailModel::with(['productivity_detail' => ['model']])
            ->where('model_type', Employee::class)
            ->whereHas('productivity_detail', fn ($query) => $query->inProgress())
            ->get()
            ->reject(fn ($row) => $workstation && $row->productivity_detail
                && $this->sameGroupScheduled($workstation, $row->productivity_detail, $production_order_id))
            ->mapWithKeys(fn ($row) => [$row->model_id => $row->productivity_detail?->model?->name]);
    }

    public function eligibleEmployees(Model $workstation, ?int $production_order_id = null): Collection
    {
        return Employee::with('person')
            ->where('workstation_id', $workstation->id)
            ->whereNotIn('id', $this->busyMap($workstation, $production_order_id)->keys())
            ->get()
            ->map(fn ($employee) => $this->employeePayload($employee))
            ->sortBy('name')
            ->values();
    }

    /**
     * Empleados que están en un registro en curso.
     */
    /**
     * Empleados del mismo proceso padre que pueden reforzar el puesto: asociados a otro puesto del proceso o al proceso
     * mismo, con el puesto al que pertenecen y si están ocupados en otro trabajo en curso (esos no se pueden elegir).
     */
    public function borrowableEmployees(Model $workstation, Process $process, ?int $production_order_id = null): Collection
    {
        $subprocesses = $process->subprocesses()->with('operations')->get();
        $subprocess_ids = $subprocesses->pluck('id');
        $operation_ids = $subprocesses->flatMap(fn ($subprocess) => $subprocess->operations)->pluck('id');

        // Empleado ocupado => puesto donde está trabajando
        $busy = $this->busyMap($workstation, $production_order_id);

        return Employee::with(['person', 'workstation'])
            ->whereIn('workstation_id', $subprocess_ids->merge($operation_ids)->push($process->id))
            ->where('workstation_id', '!=', $workstation->id)
            ->get()
            ->map(fn ($employee) => array_merge($this->employeePayload($employee), [
                'workstation' => $employee->workstation?->name,
                'busy' => $busy->has($employee->id),
                'busy_in' => $busy->get($employee->id),
            ]))
            ->sortBy('name')
            ->values();
    }

    /**
     * Empleados del puesto que están ocupados en otro trabajo en curso, con el puesto donde están: la estación los muestra
     * (no seleccionables) para que se sepa por qué el grupo de trabajo está incompleto.
     */
    public function busyEmployees(Model $workstation, ?int $production_order_id = null): Collection
    {
        $busy = $this->busyMap($workstation, $production_order_id);

        return Employee::with('person')
            ->where('workstation_id', $workstation->id)
            ->whereIn('id', $busy->keys())
            ->get()
            ->map(fn ($employee) => array_merge($this->employeePayload($employee), ['busy_in' => $busy->get($employee->id)]))
            ->sortBy('name')
            ->values();
    }

    public function busyEmployeeIds(): Collection
    {
        return ProductivityDetailModel::where('model_type', Employee::class)
            ->whereHas('productivity_detail', fn ($query) => $query->inProgress())
            ->pluck('model_id');
    }

    public function employeePayload(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => trim(($employee->person?->names ?? '').' '.($employee->person?->last_names ?? '')),
            'document' => $employee->person?->document,
        ];
    }

    public function isProgrammed(Stop $stop): bool
    {
        return (bool) ($stop->stop_type->first()?->settings->has_defined_time_range ?? false);
    }

    /**
     * Abre o cierra sola la parada de espera (la del catálogo con settings.waiting_units) en los registros en curso del
     * lote en el turno: se abre al alcanzar lo que soltó el puesto programado antes, y se cierra cuando hay unidades que
     * valga la pena pasar (WAITING_RELEASE, o las que le falten al lote si son menos) o ya no hay límite. No se ejecuta
     * sola: se llama después de iniciar, +1, −1, terminar una parada y finalizar. Sin esa parada en el catálogo solo se
     * detiene el +1.
     */
    public function refreshWaitingStops(Productivity $shift, ProductionOrder $production_order, Process $process): void
    {
        $stop = Stop::with('stop_type')->where('settings->waiting_units', true)->first();

        $shift->productivity_details()
            ->with(['model', 'productivity', 'production_order'])
            ->where('production_order_id', $production_order->id)
            ->inProgress()
            ->get()
            ->filter(fn (ProductivityDetail $row) => $row->model)
            ->each(function (ProductivityDetail $row) use ($stop, $production_order, $process) {
                $open = $this->openStop($row);
                $limit = $this->unitsLimitOf($row, $process);
                $to_do = $this->unitsToDo($production_order, $row->model, $process);

                if ($open?->settings->automatic ?? false) {
                    if (! $limit || $limit['available'] >= min(self::WAITING_RELEASE, $to_do)) {
                        $this->endStop($row);
                    }

                    return;
                }

                if ($stop && ! $open && $limit && $limit['available'] < 1 && $to_do > 0) {
                    $this->startStop($row, $stop, true);
                }
            });
    }

    /**
     * Cierra la parada de espera si está abierta (el grupo va a finalizar o a registrar otra parada).
     */
    public function endWaitingStop(ProductivityDetail $detail): void
    {
        if ($this->openStop($detail)?->settings->automatic ?? false) {
            $this->endStop($detail);
        }
    }

    /**
     * Registra una parada ahora. La programada termina sola con la duración del catálogo; la no programada queda
     * abierta hasta «Terminar parada»; la automática (espera de unidades) la cierra el sistema.
     */
    public function startStop(ProductivityDetail $detail, Stop $stop, bool $automatic = false): void
    {
        $programmed = $this->isProgrammed($stop);

        $detail->models()->create([
            'model_type' => Stop::class,
            'model_id' => $stop->id,
            'settings' => [
                'type' => $programmed ? ProductivityDetailModel::PROGRAMMED : ProductivityDetailModel::UNPROGRAMMED,
                'name' => $stop->name,
                'start' => now()->format('Y-m-d H:i:s'),
                'end' => $programmed ? now()->addMinutes((int) ($stop->settings->range_time ?? 0))->format('Y-m-d H:i:s') : null,
                'automatic' => $automatic,
            ],
        ]);
    }

    /**
     * Termina ahora la parada no programada abierta.
     */
    public function endStop(ProductivityDetail $detail): void
    {
        $open = $this->openStop($detail);

        if ($open) {
            $open->settings = array_merge((array) $open->settings, ['end' => now()->format('Y-m-d H:i:s')]);
            $open->save();
        }
    }

    /**
     * Parada en curso del registro: la no programada sin fin o la programada que aún no termina.
     */
    public function openStop(ProductivityDetail $detail, ?Carbon $at = null): ?ProductivityDetailModel
    {
        $at ??= now();

        return $detail->stops()->get()->first(fn ($stop) => empty($stop->settings->end) || Carbon::parse($stop->settings->end)->gt($at));
    }

    /**
     * Fin oficial si se finalizara ahora: la hora actual o, si ya pasó, el fin del turno.
     */
    public function officialEnd(ProductivityDetail $detail): Carbon
    {
        return now()->min($detail->productivity->endsAt())->max($detail->start);
    }

    /**
     * Paradas del registro como arreglos, con el fin de las abiertas en $end.
     */
    public function stopRows(ProductivityDetail $detail, Carbon $end): Collection
    {
        return $detail->stops->map(fn ($stop) => [
            'stop_id' => $stop->model_id,
            'name' => $stop->settings->name ?? null,
            'type' => $stop->settings->type ?? ProductivityDetailModel::UNPROGRAMMED,
            'start' => $stop->settings->start,
            'end' => $stop->settings->end ?? $end->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Tiempos y eficiencia de un registro que termina en $end.
     */
    public function summary(ProductivityDetail $detail, Carbon $end, ?int $units = null): array
    {
        $start = $detail->start->copy();

        // Paradas que tocan el registro, recortadas a su rango
        $stops = $this->stopRows($detail, $end)
            ->map(function ($stop) use ($start, $end) {
                $from = Carbon::parse($stop['start'])->max($start);
                $to = Carbon::parse($stop['end'])->min($end);

                return $to->gt($from) ? array_merge($stop, [
                    'start' => $from->format('Y-m-d H:i:s'),
                    'end' => $to->format('Y-m-d H:i:s'),
                    'minutes' => round($this->minutes($from, $to), 2),
                ]) : null;
            })
            ->filter()
            ->sortBy('start')
            ->values();

        // Tiempo muerto: la unión de las paradas, para no descontar dos veces el rato en que se cruzan
        $dead = 0;
        $cursor = null;

        foreach ($stops as $stop) {
            $from = Carbon::parse($stop['start']);
            $to = Carbon::parse($stop['end']);

            if ($cursor && $from->lt($cursor)) {
                $from = $cursor;
            }

            if ($to->gt($from)) {
                $dead += $this->minutes($from, $to);
                $cursor = $to;
            }
        }

        $people = max(1, $detail->employees()->count());
        $standard = $this->standardMinutes($detail, $people);

        // Por unidades la regla es lo que tarda una unidad: el estándar se multiplica por lo que hizo el registro (con
        // el +1, o la curva que se registra al finalizar en ETIQUETADO)
        $by_units = $detail->model && $this->measuredByUnits($detail->model);
        $units = $by_units ? ($units ?? $this->producedBy($detail)) : null;

        $duration = $this->minutes($start, $end);
        $available = $duration * $people;
        $dead_people = $dead * $people;
        $worked = $available - $dead_people;
        // Por unidades la regla es lo que tarda una persona en una unidad: el estándar en minutos-persona es eso × unidades
        $standard_people = $by_units ? $standard * $units : $standard * $people;

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'people' => $people,
            'duration_minutes' => round($duration, 2),
            'stop_minutes' => round($dead, 2),
            'available_minutes' => round($available, 2),
            'dead_minutes' => round($dead_people, 2),
            'worked_minutes' => round($worked, 2),
            'standard_minutes' => round($standard_people, 2),
            'efficiency' => $worked > 0 ? round($standard_people / $worked * 100, 2) : null,
            'units' => $units,
            'stops' => $stops->all(),
        ];
    }

    /**
     * Finaliza el registro: fija el fin oficial, cierra la parada abierta, devuelve el resumen y, si el puesto crea
     * ingresos, un ingreso pendiente por destino con las unidades.
     *
     * @param  array|null  $quantities  [{production_order_detail_id, size_id, quantity}], solo si crea ingresos
     */
    public function finish(ProductivityDetail $detail, Process $process, ?array $quantities, ?int $total = null): array
    {
        $this->endWaitingStop($detail);

        $current = ProductivityDetail::with(['productivity', 'stops', 'rule'])->findOrFail($detail->id);

        if ($current->end) {
            throw new DomainException('El trabajo ya se finalizó.');
        }

        // Total digitado al finalizar (sin +1): va al contador del paso como si se hubiera contado
        if ($total !== null) {
            $this->addProducedTotal($current, $process, $total);
        }

        $end = $this->officialEnd($current);

        $summary = $this->summary($current->refresh(), $end, $quantities ? (int) collect($quantities)->sum('quantity') : null);

        // No se guardan resultados (minutos, eficiencia): el frontend los calcula con start, end, empleados, paradas y la
        // regla; el resumen solo se devuelve a la estación para mostrarlo al finalizar
        $current->end = $end;
        $current->save();

        $details = [];

        foreach (collect($quantities ?? [])->groupBy('production_order_detail_id') as $production_order_detail_id => $sizes) {
            $sizes = $sizes->filter(fn ($size) => (int) $size['quantity'] > 0);

            if ($sizes->isEmpty()) {
                continue;
            }

            $movement = Movement::firstOrCreate(
                [
                    'production_order_detail_id' => $production_order_detail_id,
                    'model_type' => Process::class,
                    'model_id' => $process->id,
                ],
                [
                    'status' => 'Ingreso',
                ]
            );

            $movement_detail = MovementDetail::create([
                'movement_id' => $movement->id,
                'model_type' => ProductivityDetail::class,
                'model_id' => $current->id,
                'status' => MovementDetail::PENDING,
            ]);

            foreach ($sizes as $size) {
                MovementDetailQuantity::create([
                    'movement_detail_id' => $movement_detail->id,
                    'size_id' => $size['size_id'],
                    'quantity' => (int) $size['quantity'],
                ]);
            }

            $details[] = $movement_detail->id;
        }

        return array_merge($summary, [
            // Corte: lo digitado; por unidades: lo que sumó el registro con el +1
            'units' => $quantities ? (int) collect($quantities)->sum('quantity') : $summary['units'],
            'movement_details' => $details,
        ]);
    }

    /**
     * Cómo se mide el puesto (lots | units): la suya si la tiene; si no, la de su subproceso y luego la de su proceso.
     * Así un subproceso como ETIQUETADO se mide por unidades dentro de un CORTE por lotes.
     */
    public function measurementOf(Model $workstation): ?string
    {
        $own = $workstation->settings->productivity_measurement ?? null;

        if (in_array($own, ['lots', 'units'], true)) {
            return $own;
        }

        $parent = match (true) {
            $workstation instanceof Operation => $this->subprocessOf($workstation),
            $workstation instanceof Subprocess => $workstation->process()->first(),
            default => null,
        };

        return $parent ? $this->measurementOf($parent) : null;
    }

    /**
     * Por qué se multiplica el tiempo de la regla en el puesto: el suyo o, si no lo define, el de su subproceso.
     * - buttons: N° de botones de la ficha (1 si falta). Ej.: OJAL, 6 s por ojal × 2 botones.
     * - manual: una cantidad que el líder digita al programar (label: «Golpes por prenda» en TACHES).
     * null = nada (se usa el tiempo de la regla tal cual).
     *
     * @return array{mode: string, label: ?string}|null
     */
    public function multiplierOf(Model $workstation): ?array
    {
        $own = $workstation->settings->time_multiplier ?? null;

        if (in_array($own, ['buttons', 'manual'], true)) {
            return [
                'mode' => $own,
                'label' => $own === 'buttons' ? 'Botones' : ($workstation->settings->time_multiplier_label ?? null ?: 'Cantidad por prenda'),
            ];
        }

        if ($own === 'none' || ! $workstation instanceof Operation) {
            return null;
        }

        $subprocess = $workstation->subprocess()->first();

        return $subprocess ? $this->multiplierOf($subprocess) : null;
    }

    /**
     * Factor por el que se multiplica el tiempo de la regla para el lote en el puesto (1 si no aplica). Con «cantidad
     * que se digita», la del registro programado.
     */
    public function factor(ProductionOrder $production_order, Model $workstation, mixed $manual = null): float
    {
        return match ($this->multiplierOf($workstation)['mode'] ?? null) {
            'buttons' => (float) max(1, (int) ($production_order->technical_sheet?->number_of_buttons ?? 1)),
            'manual' => (float) ($manual ?: 1),
            default => 1.0,
        };
    }

    public function detailFactor(ProductivityDetail $detail): float
    {
        return $detail->model && $detail->production_order
            ? $this->factor($detail->production_order, $detail->model, $detail->settings->multiplier ?? null)
            : 1.0;
    }

    /**
     * Lo que la estación y el modal muestran del multiplicador: el modo, su nombre y el factor del registro.
     */
    public function multiplierPayload(ProductionOrder $production_order, Model $workstation, mixed $manual = null): ?array
    {
        $multiplier = $this->multiplierOf($workstation);

        return $multiplier ? array_merge($multiplier, ['factor' => $this->factor($production_order, $workstation, $manual)]) : null;
    }

    public function measuredByUnits(Model $workstation): bool
    {
        return $this->measurementOf($workstation) === 'units';
    }

    /**
     * Cómo registra las unidades un puesto por unidades: plus (un +1 por prenda) o finish (digita el total al
     * finalizar, p. ej. SANDBLAST). La suya o la de su subproceso; por defecto, plus.
     */
    public function unitsEntryOf(Model $workstation): string
    {
        $own = $workstation->settings->units_entry ?? null;

        if (in_array($own, ['plus', 'finish'], true)) {
            return $own;
        }

        $subprocess = $workstation instanceof Operation ? $workstation->subprocess()->first() : null;

        return $subprocess ? $this->unitsEntryOf($subprocess) : 'plus';
    }

    /**
     * Cómo se ponen las unidades al programar el puesto: 'auto' (reparto del lote entre sus filas) o 'typed' (las
     * digita quien programa: tandas de lavandería). La operación hereda la de su subproceso; por defecto, 'auto'.
     */
    public function scheduleUnitsOf(Model $workstation): string
    {
        $own = $workstation->settings->schedule_units ?? null;

        if (in_array($own, ['auto', 'typed'], true)) {
            return $own;
        }

        $subprocess = $workstation instanceof Operation ? $workstation->subprocess()->first() : null;

        return $subprocess ? $this->scheduleUnitsOf($subprocess) : 'auto';
    }

    /**
     * ¿Las unidades de cada fila programada las digita quien programa (tandas)? Solo en puestos que se programan.
     */
    public function typedUnits(Process $process, Model $workstation): bool
    {
        return $this->programmable($process, $workstation) && $this->scheduleUnitsOf($workstation) === 'typed';
    }

    /**
     * Lo que ya tienen tomado las otras filas del lote en el paso (sin terminar): lo programado que aún no hacen.
     */
    public function pendingUnits(Collection $lot): int
    {
        return (int) $lot->filter(fn ($row) => ! $row->end)
            ->sum(fn ($row) => max(0, (int) ($row->settings->scheduled->units ?? 0) - $this->producedBy($row)));
    }

    /**
     * ¿El puesto se programa? Por unidades, salvo el que crea los ingresos (ETIQUETADO). Lleva el lote repartido como
     * meta, cuente con el +1 o digite el total al finalizar.
     */
    public function programmable(Process $process, Model $workstation): bool
    {
        return $this->measuredByUnits($workstation) && ! $this->createsIngress($process, $workstation);
    }

    /**
     * ¿Se puede programar en el turno? Todo puesto con medición configurada (por lote o por unidades): los programables
     * por unidades con su cuota y los demás por lote (quién trabaja el lote y a qué hora).
     */
    public function schedulable(Process $process, Model $workstation): bool
    {
        return $this->measurementOf($workstation) !== null;
    }

    /**
     * ¿El puesto se programa por lote? El que se mide por lote o el que crea el ingreso al siguiente proceso: no tiene
     * unidades que repartir, el lote se trabaja una vez y completo.
     */
    public function schedulesByLot(Process $process, Model $workstation): bool
    {
        return $this->schedulable($process, $workstation) && ! $this->programmable($process, $workstation);
    }

    /**
     * Unidades del lote en el proceso: en el que no tiene procesos anteriores (por donde entran las unidades), su curva;
     * en los demás, lo recibido en ingresos aprobados.
     */
    public function lotUnitsIn(ProductionOrder $production_order, Process $process): int
    {
        if ($process->isFirst()) {
            return $this->lotUnits($production_order);
        }

        return (int) $this->receivedDetails($production_order, $process)
            ->sum(fn ($detail) => $detail->movement_detail_quantities->whereNotNull('size_id')->sum('quantity'));
    }

    /**
     * Unidades del lote según su curva.
     */
    public function lotUnits(ProductionOrder $production_order): int
    {
        return (int) collect($this->destinations($production_order))
            ->sum(fn ($destination) => collect($destination['sizes'])->sum('quantity'));
    }

    /**
     * ¿Al lote le falta trabajarse en este puesto del primer proceso? El que registra las unidades, mientras a la curva
     * le falten; los demás, una sola vez.
     */
    public function lotWaiting(ProductionOrder $production_order, Model $workstation, Process $process): bool
    {
        if ($this->countsAtFinish($process, $workstation)) {
            return collect($this->pendingCurve($production_order, $workstation))
                ->sum(fn ($destination) => collect($destination['sizes'])->sum('quantity')) > 0;
        }

        return ! $this->registeredDetail($production_order, $workstation);
    }

    /**
     * Por qué no se puede programar el lote en este puesto del primer proceso, o null.
     */
    public function lotScheduleError(Productivity $shift, ProductionOrder $production_order, Model $workstation, Process $process, int $people, ?int $except_id = null): ?string
    {
        $rule = app(StandardRuleService::class)->resolve($production_order, $workstation, $process)['rule'] ?? null;

        if (! $rule) {
            return 'No hay una regla estándar que aplique a esta referencia en este puesto.';
        }

        // Por lote, la regla define cuánto tarda cada cantidad de personas: sin ese dato la estación no deja iniciar
        if (! $this->capacity($rule, $workstation, $people)) {
            return "La regla {$rule->name} no tiene capacidad para {$people} personas. Agrégala en Reglas o ajusta las personas de {$workstation->name}.";
        }

        if ($this->lotUnitsIn($production_order, $process) < 1) {
            return $process->isFirst()
                ? 'La orden de producción no tiene unidades en las tallas que hoy tiene la marca: no se puede trabajar.'
                : "La referencia no tiene ingresos aprobados en {$process->name}.";
        }

        if (! $this->lotWaiting($production_order, $workstation, $process)) {
            return "La referencia ya se trabajó en {$workstation->name}.";
        }

        $repeated = $this->scheduledDetails($shift, $workstation, $production_order)
            ->when($except_id, fn ($rows) => $rows->where('id', '!=', $except_id))
            ->isNotEmpty();

        return $repeated ? "La referencia ya está programada en {$workstation->name} en este turno." : null;
    }

    /**
     * Programación por lote: inicio y fin los pone quien programa; la meta es el lote completo.
     */
    public function scheduleLot(ProductivityDetail $row, string $end): void
    {
        $start = $row->start->copy();
        $finish = Carbon::parse($start->format('Y-m-d').' '.$end);

        $row->settings = array_merge((array) $row->settings, [
            'scheduled' => [
                'start' => $start->format('Y-m-d H:i:s'),
                'units' => $this->lotUnitsIn($row->production_order, $row->productivity->model),
                'minutes' => (float) $start->diffInMinutes($finish),
                'end' => $finish->format('Y-m-d H:i:s'),
            ],
        ]);
        $row->save();
    }

    /**
     * ¿El turno se programa como una cadena? Un proceso que se mide por lote con sus subprocesos en secuencia: el lote
     * pasa completo de un puesto al siguiente, así que el líder solo elige las referencias y su orden; las horas las
     * calcula el sistema.
     */
    public function programsChain(Process $process): bool
    {
        return $this->measurementOf($process) === 'lots' && ! $this->scheduleIsGuide($process);
    }

    /**
     * Minutos estándar del lote en el puesto con esas personas: por lote, el tiempo de la regla para ese número de
     * personas; por unidades, el tiempo por unidad (repartido entre las personas) por las unidades del lote.
     *
     * @return array{rule?: Rule, minutes?: float, error?: string, capacities?: array<int, int>}
     */
    public function chainMinutes(ProductionOrder $production_order, Model $workstation, Process $process, int $people): array
    {
        $rule = app(StandardRuleService::class)->resolve($production_order, $workstation, $process)['rule'] ?? null;

        if (! $rule) {
            return ['error' => 'No hay una regla que aplique.'];
        }

        $capacity = $this->capacity($rule, $workstation, $people);

        if (! $capacity) {
            return [
                'error' => $rule->capacities()
                    ? sprintf('La regla no tiene capacidad para %d %s.', $people, $people === 1 ? 'persona' : 'personas')
                    : 'La regla no tiene el tiempo registrado.',
                // Para sugerir la más cercana: las cantidades de personas que la regla sí tiene
                'capacities' => collect($rule->capacities())->pluck('people')->values()->all(),
            ];
        }

        $time = $capacity['time'] * $this->factor($production_order, $workstation);
        $minutes = ($capacity['time_unit'] === 'seg' ? $time / 60 : $time) * ($this->measuredByUnits($workstation) ? $this->lotUnitsIn($production_order, $process) : 1);

        return ['rule' => $rule, 'minutes' => $minutes];
    }

    /**
     * Programación en cadena del turno: con las referencias en el orden que eligió el líder, cada lote entra a cada puesto
     * cuando lo soltó el puesto anterior y el puesto terminó el lote anterior. Lo ya iniciado se queda y ocupa su puesto;
     * lo demás se recalcula desde el inicio del turno (o desde ahora, si está en curso). Cada lote trae `id`, `removed`
     * (puestos «model_type:model_id» que el líder quitó: desde el primero quitado no sigue, porque la cadena es en
     * secuencia) y `employees` por puesto: las personas las elige el líder en cada fila (no hay grupo por defecto). Sin
     * personas la fila no sabe cuánto dura y las siguientes esperan.
     *
     * Una persona no está en dos trabajos a la vez, pero el sistema nunca quita a nadie solo: si dos filas se cruzan o
     * alguien está ocupado en otro turno, la fila queda con el aviso para que el líder decida.
     *
     * @return array{rows: array<int, array>, errors: array<string, string>, removed: array<int, array>}
     */
    public function chainPlan(Productivity $shift, array $lots): array
    {
        $process = $shift->model;
        $workstations = $this->workstations($process)->map(fn ($item) => array_merge($item, [
            'key' => $item['model_type'].':'.$item['model_id'],
            'model' => $item['model_type']::find($item['model_id']),
        ]));
        $floor = $shift->status === Productivity::IN_PROGRESS ? now()->startOfMinute()->addMinute()->max($shift->startsAt()) : $shift->startsAt();
        $started = $shift->productivity_details()->whereNotNull('settings->scanned_at')->get();
        $members = $workstations->flatMap(fn ($item) => collect($item['employees'])->map(fn ($employee) => [$employee['id'], $item['name']]))
            ->mapWithKeys(fn ($pair) => [$pair[0] => $pair[1]]);
        $orders = collect($lots)->values()->map(fn ($lot) => ProductionOrder::with(['technical_sheet' => ['product']])->find($lot['id']));
        // Sin hora de fin, la fila ocupa a su gente desde que empieza
        $until = fn ($row) => $row['end'] ?? $row['start']?->copy()->addMinute();
        $crosses = fn ($row, $other) => $row['start'] && $other['start'] && $other['start']->lt($until($row)) && $until($other)->gt($row['start']);
        $external = fn ($start, $end) => $this->overlappingRows($start, $end)
            ->reject(fn ($other) => $other->productivity_id === $shift->id && ! isset($other->settings->scanned_at));
        $free = $workstations->mapWithKeys(fn ($item) => [$item['key'] => $started
            ->filter(fn ($row) => $row->model_type === $item['model_type'] && $row->model_id === $item['model_id'])
            ->map(fn ($row) => $this->busyUntil($row))
            ->push($floor)
            ->max()])->all();
        $rows = [];
        $removed = [];

        foreach (array_values($lots) as $index => $lot) {
            $production_order = $orders[$index];
            $ready = $floor->copy();
            $blocked = false;
            $staff = (array) ($lot['employees'] ?? []);

            foreach ($workstations->values() as $position => $item) {
                $workstation = $item['model'];
                $own = $started->first(fn ($row) => $row->production_order_id === $production_order->id && $row->model_type === $item['model_type'] && $row->model_id === $item['model_id']);

                // El líder lo quitó de este puesto: tampoco pasa a los siguientes. Los que le tocaban y no se han
                // trabajado quedan aparte, para que el líder los pueda volver a programar
                if (! $own && in_array($item['key'], (array) ($lot['removed'] ?? []), true)) {
                    foreach ($workstations->values()->slice($position) as $next) {
                        if (! $this->routeError($production_order, $next['model'], $process) && $this->lotWaiting($production_order, $next['model'], $process)
                            && ! $started->contains(fn ($row) => $row->production_order_id === $production_order->id && $row->model_type === $next['model_type'] && $row->model_id === $next['model_id'])) {
                            $removed[] = ['index' => $index, 'production_order' => $production_order, 'workstation' => $next['model']];
                        }
                    }

                    continue 2;
                }

                // Ya iniciado en el puesto: no se mueve y el siguiente puesto lo espera
                if ($own) {
                    $ready = $ready->max($this->busyUntil($own));

                    continue;
                }

                if ($this->routeError($production_order, $workstation, $process) || ! $this->lotWaiting($production_order, $workstation, $process)) {
                    continue;
                }

                $employees = collect($staff[$item['key']] ?? [])->map(fn ($employee) => (int) $employee)->unique()->values();
                $row = [
                    'index' => $index,
                    'key' => $item['key'],
                    'production_order' => $production_order,
                    'workstation' => $workstation,
                    'rule' => null,
                    'start' => null,
                    'end' => null,
                    'minutes' => null,
                    'employees' => $employees,
                    'error' => null,
                    'capacities' => null,
                    // Un puesto anterior no se pudo calcular: este no tiene hora hasta que se arregle
                    'blocked' => $blocked,
                ];

                // El puesto tiene antes un lote sin hora de fin: este no sabe cuándo puede entrar
                if (! $blocked && ! $free[$item['key']]) {
                    $row['blocked'] = $blocked = true;
                }

                if (! $blocked) {
                    $start = $ready->copy()->max($free[$item['key']]);
                    $time = $employees->isEmpty() ? ['error' => 'Elige las personas que van a trabajar.'] : $this->chainMinutes($production_order, $workstation, $process, $employees->count());
                    $end = ($time['error'] ?? null) ? null : $start->copy()->addSeconds((int) ceil($time['minutes'] * 60));

                    // El inicio se sabe aunque falte el tiempo de la regla: lo que no se sabe es cuándo termina
                    $row['start'] = $start;

                    if (! ($time['error'] ?? null)) {
                        $row['rule'] = $time['rule'];
                        $row['minutes'] = $time['minutes'];
                        $row['end'] = $end;
                    }

                    $row['error'] = $time['error'] ?? ($row['end']->gt($shift->endsAt()) ? 'No alcanza a terminar en el turno.' : null);
                    $row['capacities'] = $time['capacities'] ?? null;
                    $free[$item['key']] = $row['end'];

                    if ($row['end']) {
                        $ready = $row['end'];
                    }

                    $blocked = ! $row['end'];
                }

                $row['borrowed'] = $row['employees']->mapWithKeys(fn ($employee) => [$employee => collect($item['employees'])->contains('id', $employee) ? null : ($members[$employee] ?? null)])->filter();
                $rows[] = $row;
            }
        }

        // Sus personas deben ser del proceso y estar libres en esa franja (no se quita a nadie: queda el aviso)
        foreach ($rows as $i => $row) {
            if (! $row['start'] || $row['error']) {
                continue;
            }

            $busy = $external($row['start'], $until($row));

            foreach ($row['employees'] as $employee) {
                if (! $members->has($employee)) {
                    $rows[$i]['error'] ??= "Un empleado no pertenece a {$process->name}.";
                } elseif ($other = $busy->first(fn ($other) => $other->employees->contains('id', $employee))) {
                    $rows[$i]['error'] ??= $this->busyMessage($other, $shift, $employee);
                } elseif ($mate = collect($rows)->first(fn ($other, $j) => $j !== $i && $other['employees']->contains($employee) && $crosses($row, $other))) {
                    $label = collect([$mate['production_order']->technical_sheet?->product?->code, $mate['production_order']->cut])->filter()->implode('-');
                    $rows[$i]['error'] ??= "Un empleado también está en {$mate['workstation']->name} ({$label}) a esa hora.";
                }
            }
        }

        // Para guardar: el primer problema de cada referencia, con su puesto
        $errors = [];

        foreach ($rows as $row) {
            if ($row['error']) {
                $label = collect([$row['production_order']->technical_sheet?->product?->code, $row['production_order']->cut])->filter()->implode('-');
                $errors["production_orders.{$row['index']}"] ??= "{$label} · {$row['workstation']->name}: {$row['error']}";
            }
        }

        return ['rows' => $rows, 'errors' => $errors, 'removed' => $removed];
    }

    /**
     * Vista previa de la cadena sin guardar: lo ya iniciado del turno y las filas calculadas, con los mismos datos que
     * muestra la tabla de la programación; y los puestos que el líder quitó (`removed`), para volver a programarlos.
     */
    public function chainPreview(Productivity $shift, array $plan): array
    {
        $started = $shift->productivity_details()
            ->with(['employees', 'production_order' => ['technical_sheet' => ['product']]])
            ->whereNotNull('settings->scanned_at')
            ->get()
            ->map(fn (ProductivityDetail $row) => [
                'index' => null,
                'production_order' => $row->production_order,
                'model_type' => $row->model_type,
                'model_id' => $row->model_id,
                'start' => Carbon::parse($row->settings->scheduled->start ?? $row->start)->format('Y-m-d H:i:s'),
                'end' => Carbon::parse($row->settings->scheduled->end ?? $this->busyUntil($row))->format('Y-m-d H:i:s'),
                'minutes' => $row->settings->scheduled->minutes ?? null,
                'units' => $row->settings->scheduled->units ?? null,
                'employees' => $row->employees->pluck('id')->values(),
                'started' => true,
                'finished' => (bool) $row->end,
            ]);

        $planned = collect($plan['rows'])->map(fn ($row) => [
            'index' => $row['index'],
            'production_order' => $row['production_order'],
            'model_type' => $row['workstation']::class,
            'model_id' => $row['workstation']->id,
            'start' => $row['start']?->format('Y-m-d H:i:s'),
            'end' => $row['end']?->format('Y-m-d H:i:s'),
            'minutes' => $row['minutes'] === null ? null : round($row['minutes'], 2),
            'units' => $this->lotUnitsIn($row['production_order'], $shift->model),
            'employees' => $row['employees'],
            'started' => false,
            'finished' => false,
            'error' => $row['error'],
            'capacities' => $row['capacities'],
            'blocked' => $row['blocked'],
        ]);

        $removed = collect($plan['removed'])->map(fn ($row) => [
            'index' => $row['index'],
            'production_order' => $row['production_order'],
            'model_type' => $row['workstation']::class,
            'model_id' => $row['workstation']->id,
            'units' => $this->lotUnitsIn($row['production_order'], $shift->model),
            'employees' => [],
            'removed' => true,
        ]);

        return ['rows' => $started->concat($planned)->concat($removed)->values()->all(), 'errors' => $plan['errors']];
    }

    /**
     * ¿El puesto suma con el +1? Los programables que no digitan el total al finalizar.
     */
    public function countsWithPlus(Process $process, Model $workstation): bool
    {
        return $this->programmable($process, $workstation) && $this->unitsEntryOf($workstation) !== 'finish';
    }

    /**
     * ¿El puesto digita el total de unidades al finalizar (sin +1)? Se programa igual; el total va al contador del paso.
     */
    public function totalAtFinish(Process $process, Model $workstation): bool
    {
        return $this->programmable($process, $workstation) && $this->unitsEntryOf($workstation) === 'finish';
    }

    /**
     * Máximo que puede digitar al finalizar: lo que le falta al lote en su paso (su parte de la programación es una
     * meta, no un tope: puede hacer más y ayudar al otro puesto).
     */
    public function finishCap(ProductivityDetail $detail, Process $process): int
    {
        $units = $this->unitsToDo($detail->production_order, $detail->model, $process);
        $limit = $this->unitsLimitOf($detail, $process);

        return $limit ? min($units, $limit['available']) : $units;
    }

    /**
     * Reparte el total digitado al finalizar en los ingresos recibidos, en el orden del +1 (Nacional, Medellín,
     * Stara y por llegada), hasta lo que le falta a cada uno en el paso.
     */
    protected function addProducedTotal(ProductivityDetail $detail, Process $process, int $units): void
    {
        $settings = (array) $detail->refresh()->settings;
        $produced = (array) ($settings['produced'] ?? []);

        foreach ($this->counters($detail->production_order, $process, $detail->model)['details'] as $received) {
            if ($units < 1) {
                break;
            }

            $take = min($units, max(0, $received['received'] - $received['produced']));
            if ($take > 0) {
                $produced[$received['movement_detail_id']] = (int) ($produced[$received['movement_detail_id']] ?? 0) + $take;
                $units -= $take;
            }
        }

        $settings['produced'] = $produced;
        $detail->settings = $settings;
        $detail->save();
    }

    /**
     * ¿El puesto mide por unidades pero cuenta al finalizar (ETIQUETADO)? Solo marca inicio y fin, no necesita
     * programación y el lote se puede volver a iniciar mientras le falten unidades.
     */
    public function countsAtFinish(Process $process, Model $workstation): bool
    {
        return $this->measuredByUnits($workstation) && $this->createsIngress($process, $workstation);
    }

    /**
     * Curva que le falta registrar al lote en el puesto: la de la orden menos lo que ya registraron sus registros
     * (por destino y talla, sin bajar de cero). Sirve para precargar el finalizar de ETIQUETADO.
     */
    public function pendingCurve(ProductionOrder $production_order, Model $workstation): array
    {
        $registered = MovementDetailQuantity::whereHas('movement_detail', fn ($query) => $query
            ->where('model_type', ProductivityDetail::class)
            ->whereIn('model_id', ProductivityDetail::where('model_type', $workstation::class)
                ->where('model_id', $workstation->id)
                ->where('production_order_id', $production_order->id)
                ->pluck('id')))
            ->with(['movement_detail' => ['movement']])
            ->get()
            ->groupBy(fn ($quantity) => $quantity->movement_detail->movement->production_order_detail_id.'-'.$quantity->size_id)
            ->map(fn ($group) => (int) $group->sum('quantity'));

        return collect($this->destinations($production_order))
            ->map(fn ($destination) => array_merge($destination, [
                'sizes' => collect($destination['sizes'])->map(fn ($size) => array_merge($size, [
                    'quantity' => max(0, $size['quantity'] - ($registered[$destination['production_order_detail_id'].'-'.$size['size_id']] ?? 0)),
                ]))->all(),
            ]))
            ->all();
    }

    /**
     * Unidades que hizo un registro con el +1 (settings.produced: {detalle recibido: unidades}).
     */
    /**
     * Subprocesos y operaciones que marca la ficha de la orden («Clase:id»).
     */
    protected function markedInSheet(ProductionOrder $production_order): Collection
    {
        return TechnicalSheetDetail::where('technical_sheet_id', $production_order->technical_sheet_id)
            ->whereIn('model_type', [Subprocess::class, Operation::class])
            ->get(['model_type', 'model_id'])
            ->map(fn ($detail) => $detail->model_type.':'.$detail->model_id);
    }

    /**
     * Paso de la ficha que cubre el puesto, o null si la referencia no lo lleva (no se programa ni se trabaja ahí):
     * - Proceso con «cualquier puesto» (Confección): el proceso; todos sus puestos se reparten el lote.
     * - Subproceso: él mismo si la ficha lo marca o si no se elige en la ficha (no aplica a ficha técnica).
     * - Operación: ella misma si la ficha la marca; si la ficha no marca ninguna operación de su subproceso, el paso
     *   del subproceso (sus operaciones se lo reparten, como alternativas).
     * Cada paso hace el lote completo.
     */
    public function routeStep(ProductionOrder $production_order, Model $workstation, Process $process, ?Collection $marked = null): ?Model
    {
        if ($workstation instanceof Process || ($process->settings->any_workstation ?? false)) {
            return $process;
        }

        $marked ??= $this->markedInSheet($production_order);

        if ($workstation instanceof Operation) {
            if ($marked->contains(Operation::class.':'.$workstation->id)) {
                $subprocess = $this->subprocessOf($workstation);

                return ! $subprocess || ! ($subprocess->settings->only_after_previous ?? false) || $this->subprocessApplies($production_order, $subprocess, $marked)
                    ? $workstation
                    : null;
            }

            // La que abre una línea solo va si la ficha la marca
            if ($this->opensLine($workstation)) {
                return null;
            }

            // La ficha manda: si marca operaciones del subproceso, el lote pasa solo por esas, también si después se
            // desactivaron en el catálogo (fichas antiguas con LIJA RECARGADA o FIJADO RESINA). No se reemplazan por las
            // operaciones activas de hoy
            $subprocess = $this->subprocessOf($workstation);
            $marks_operations = $subprocess && $subprocess->operations()->withTrashed()->get()
                ->contains(fn (Operation $operation) => $marked->contains(Operation::class.':'.$operation->id));

            return $subprocess && ! $marks_operations ? $this->routeStep($production_order, $subprocess, $process, $marked) : null;
        }

        if (! $marked->contains(Subprocess::class.':'.$workstation->id) && ($workstation->settings->in_technical_sheet ?? false)) {
            return null;
        }

        return ! ($workstation->settings->only_after_previous ?? false) || $this->subprocessApplies($production_order, $workstation, $marked)
            ? $workstation
            : null;
    }

    /**
     * Línea de trabajo de la ficha técnica: por cada proceso de la orden de producción, si la ficha lo asocia y por qué
     * puestos pasa (los subprocesos y operaciones que la ficha le marca). La ruta sale solo de la ficha, así que se
     * pregunta con un lote sin guardar de ella.
     *
     * @return Collection<int, array{name: string, applies: bool, sequence: bool, any_workstation: bool, steps: array<int, array{name: string, group: ?string}>}>
     */
    public function sheetLine(TechnicalSheet $technical_sheet): Collection
    {
        $production_order = (new ProductionOrder)->forceFill(['technical_sheet_id' => $technical_sheet->id])->setRelation('technical_sheet', $technical_sheet);
        $associated = $technical_sheet->technical_sheet_details->where('model_type', Process::class)->pluck('model_id');
        $marked = $this->markedInSheet($production_order);

        return Process::where('settings->in_production_order', true)
            ->get()
            ->map(fn (Process $process) => [
                'name' => $process->name,
                'applies' => $associated->contains($process->id),
                'sequence' => ($process->settings->subprocess_flow ?? Process::PARALLEL) === Process::SEQUENCE,
                'any_workstation' => (bool) ($process->settings->any_workstation ?? false),
                'steps' => $associated->contains($process->id) ? $this->sheetLineSteps($production_order, $process, $marked) : [],
            ])
            ->values();
    }

    /**
     * Pasos de un proceso en la línea de trabajo de la ficha: sus subprocesos en el orden de la cadena (siguientes, sin
     * regresos) que la ruta del lote lleva (marcados en la ficha, automáticos y flechas por tono) y, de cada uno, las
     * operaciones que la ficha marca, también si se desactivaron en el catálogo después (la ficha las guarda por id). Si
     * no marca ninguna, las que la ruta toma; un subproceso sin operaciones es un paso por sí mismo.
     *
     * @return array<int, array{name: string, group: ?string}>
     */
    protected function sheetLineSteps(ProductionOrder $production_order, Process $process, Collection $marked): array
    {
        $subprocesses = $process->subprocesses()->get();

        if ($subprocesses->isEmpty()) {
            return [];
        }

        // Orden de la cadena: cada subproceso después de todos sus anteriores (sin contar los regresos)
        $ids = $subprocesses->pluck('id');
        $previous = $subprocesses->mapWithKeys(fn (Subprocess $subprocess) => [
            $subprocess->id => $this->beforeSubprocesses($subprocess, null)->pluck('id')->intersect($ids)->values(),
        ]);
        $ordered = collect();
        while ($ordered->count() < $subprocesses->count()) {
            $next = $subprocesses->first(fn (Subprocess $subprocess) => ! $ordered->contains('id', $subprocess->id)
                && $previous[$subprocess->id]->diff($ordered->pluck('id'))->isEmpty())
                ?? $subprocesses->first(fn (Subprocess $subprocess) => ! $ordered->contains('id', $subprocess->id));
            $ordered->push($next);
        }

        return $ordered
            ->filter(fn (Subprocess $subprocess) => $this->routeStep($production_order, $subprocess, $process, $marked))
            ->flatMap(function (Subprocess $subprocess) use ($production_order, $process, $marked) {
                $operations = $subprocess->operations()->withTrashed()->get();
                $chosen = $operations->filter(fn (Operation $operation) => $marked->contains(Operation::class.':'.$operation->id));

                if ($chosen->isEmpty()) {
                    $chosen = $operations->whereNull('deleted_at')
                        ->filter(fn (Operation $operation) => $this->routeStep($production_order, $operation, $process, $marked));
                }

                return $chosen->isEmpty()
                    ? [['name' => $subprocess->name, 'group' => null]]
                    : $chosen->map(fn (Operation $operation) => ['name' => $operation->name, 'group' => $subprocess->name])->all();
            })
            ->values()
            ->all();
    }

    /**
     * Mensaje si la ficha de la referencia no lleva el puesto o gerencia aún no lo admite (no se programa ni se trabaja
     * ahí), o null.
     */
    public function routeError(ProductionOrder $production_order, Model $workstation, Process $process): ?string
    {
        if (! $this->routeStep($production_order, $workstation, $process)) {
            return 'Esta referencia no está asociada a este puesto de trabajo.';
        }

        $pending = $this->pendingInSheet($production_order, $workstation);

        return $pending ? "{$pending->name} aún no está en revisión ni aprobado en la ficha técnica de esta referencia: no se puede producir." : null;
    }

    /**
     * El puesto o su subproceso, si la ficha lo asocia y sigue «Pendiente»: gerencia aún no admite que la referencia
     * pase por ahí («En revisión» y «Aprobado» sí dejan).
     */
    protected function pendingInSheet(ProductionOrder $production_order, Model $workstation): ?Model
    {
        $models = $workstation instanceof Operation ? [$workstation->subprocess()->first(), $workstation] : [$workstation];

        return collect($models)->filter()->first(fn (Model $model) => TechnicalSheetDetail::where('technical_sheet_id', $production_order->technical_sheet_id)
            ->where('model_type', $model::class)
            ->where('model_id', $model->id)
            ->where('status', TechnicalSheetDetail::PENDING)
            ->exists());
    }

    /**
     * Puestos que trabajan un paso: los del proceso, las operaciones del subproceso (o él mismo) o la operación.
     */
    public function stepWorkstations(Model $step, Process $process): Collection
    {
        return match (true) {
            $step instanceof Process => $this->workstationModels($process),
            $step instanceof Subprocess => $this->ownOperations($step)->whenEmpty(fn () => collect([$step])),
            default => collect([$step]),
        };
    }

    protected function workstationModels(Process $process): Collection
    {
        return $this->workstation_models[$process->id] ??= (function () use ($process) {
            $subprocesses = $process->subprocesses()->with('operations')->get();

            return $subprocesses->isEmpty()
                ? collect([$process])
                : $subprocesses->flatMap(fn ($subprocess) => $subprocess->operations->isEmpty() ? [$subprocess] : $subprocess->operations->all())->values();
        })();
    }

    /**
     * Lo que lleva hecho un paso con el +1, por ingreso recibido: la suma de lo que hicieron los registros del lote en
     * sus puestos. Cada paso cuenta el lote completo (EMBONAR 200 y PINZAS 150 son contadores distintos).
     *
     * @return array<int, int> [movement_detail_id => unidades]
     */
    public function stepProduced(ProductionOrder $production_order, Model $step, Process $process): array
    {
        $workstations = $this->stepWorkstations($step, $process);

        $produced = ProductivityDetail::where('production_order_id', $production_order->id)
            ->where(fn ($query) => $workstations->each(fn (Model $workstation) => $query->orWhere(fn ($sub) => $sub
                ->where('model_type', $workstation::class)
                ->where('model_id', $workstation->id))))
            ->get()
            ->flatMap(fn (ProductivityDetail $detail) => collect((array) ($detail->settings->produced ?? []))
                ->map(fn ($units, $id) => ['id' => (int) $id, 'units' => (int) $units]))
            ->groupBy('id')
            ->map(fn ($group) => (int) $group->sum('units'))
            ->all();

        // Reproceso: lo que se devuelve al paso deja de estar hecho ahí (se descuenta del último ingreso hacia atrás) y
        // el paso lo vuelve a hacer con el +1 o al finalizar
        $redo = $this->reprocessedUnits($production_order)[$step::class.':'.$step->id] ?? 0;
        krsort($produced);

        foreach ($produced as $id => $units) {
            $take = min($redo, $units);
            $produced[$id] = $units - $take;
            $redo -= $take;
        }

        ksort($produced);

        return $produced;
    }

    /**
     * Reprocesos del lote: [paso («Clase:id») => unidades que se vuelven a hacer en él]. Se guardan en el registro de
     * trabajo donde se detectaron (settings.reprocesses: units, steps, note, user_id, at).
     */
    public function reprocessedUnits(ProductionOrder $production_order): array
    {
        $units = [];

        ProductivityDetail::where('production_order_id', $production_order->id)
            ->whereNotNull('settings->reprocesses')
            ->get()
            ->each(function (ProductivityDetail $detail) use (&$units) {
                foreach ((array) ($detail->settings->reprocesses ?? []) as $reprocess) {
                    foreach ((array) ($reprocess->steps ?? []) as $key) {
                        $units[$key] = ($units[$key] ?? 0) + (int) ($reprocess->units ?? 0);
                    }
                }
            });

        return $units;
    }

    /**
     * Pasos del lote en el proceso a los que se puede devolver un reproceso, con lo que llevan hecho:
     * [key, model_type, model_id, name, produced].
     */
    public function reprocessSteps(ProductionOrder $production_order, Process $process): Collection
    {
        return $this->requiredSteps($production_order, $process)
            ->unique(fn (Model $step) => $step::class.':'.$step->id)
            ->map(fn (Model $step) => [
                'key' => $step::class.':'.$step->id,
                'model_type' => $step::class,
                'model_id' => $step->id,
                'name' => $step->name,
                'produced' => array_sum($this->stepProduced($production_order, $step, $process)),
            ])
            ->values();
    }

    /**
     * Unidades que le faltan al lote en el paso del puesto: lo recibido en el proceso − lo que lleva el paso.
     */
    public function unitsToDo(ProductionOrder $production_order, Model $workstation, Process $process): int
    {
        $counters = $this->counters($production_order, $process, $workstation);

        return max(0, $counters['received'] - $counters['produced']);
    }

    /**
     * Pasos que exige la ficha en el proceso: el proceso si es de «cualquier puesto» o no tiene subprocesos; si no, las
     * operaciones marcadas y los subprocesos que la referencia lleva sin operaciones marcadas.
     */
    public function requiredSteps(ProductionOrder $production_order, Process $process): Collection
    {
        return $this->requiredGroups($production_order, $process)->flatten(1)->values();
    }

    /**
     * Pasos exigidos agrupados (cada grupo se cumple con uno de sus pasos; hoy cada paso es su propio grupo).
     */
    public function requiredGroups(ProductionOrder $production_order, Process $process): Collection
    {
        $marked = $this->markedInSheet($production_order);

        return $this->workstationModels($process)
            ->map(fn (Model $workstation) => $this->routeStep($production_order, $workstation, $process, $marked))
            ->filter()
            ->unique(fn (Model $step) => $step::class.':'.$step->id)
            ->map(fn (Model $step) => collect([$step]))
            ->values();
    }

    /**
     * Cuánto se puede trasladar del movimiento (lote-destino en un proceso) a otro proceso: lo que ya pasó por todos los
     * pasos que exige la ficha (el menor de ellos) − lo ya trasladado. Por unidades, lo que lleva cada paso; por lote,
     * todo cuando el lote terminó en el paso; ETIQUETADO, lo que registró (ya son los ingresos). Los pasos sin
     * medición no cuentan; null si el proceso no mide nada (no hay tope).
     */
    public function transferable(Movement $movement): ?int
    {
        $process = $movement->model;
        $production_order = $movement->production_order_detail?->production_order;

        if (! $process instanceof Process || ! $production_order) {
            return null;
        }

        $received = $movement->movement_details_input()->where('status', MovementDetail::APPROVED)->with('movement_detail_quantities')->get();
        $received_units = (int) $received->sum(fn ($detail) => $detail->movement_detail_quantities->sum('quantity'));

        $units_of = function (Model $step) use ($process, $production_order, $received, $received_units) {
            $workstations = $this->stepWorkstations($step, $process);
            $workstation = $workstations->first();

            if (! $workstation || ! $this->measurementOf($workstation)) {
                return null;
            }

            if ($this->programmable($process, $workstation)) {
                $produced = $this->stepProduced($production_order, $step, $process);

                return (int) $received->sum(fn ($detail) => min($produced[$detail->id] ?? 0, $detail->movement_detail_quantities->sum('quantity')));
            }

            if ($this->countsAtFinish($process, $workstation)) {
                return $received_units;
            }

            $finished = ProductivityDetail::where('production_order_id', $production_order->id)
                ->whereNotNull('end')
                ->where(fn ($query) => $workstations->each(fn (Model $item) => $query->orWhere(fn ($sub) => $sub
                    ->where('model_type', $item::class)
                    ->where('model_id', $item->id))))
                ->exists();

            return $finished ? $received_units : 0;
        };

        // Cada grupo se cumple con uno de sus pasos (el que más lleva); lo trasladable es lo del grupo que menos lleva
        $done = $this->requiredGroups($production_order, $process)
            ->map(fn (Collection $group) => $group->map($units_of)->filter(fn ($units) => $units !== null)->max())
            ->filter(fn ($units) => $units !== null);

        if ($done->isEmpty()) {
            return null;
        }

        $transferred = (int) $movement->movement_details_output()
            ->whereIn('status', [MovementDetail::PENDING, MovementDetail::APPROVED])
            ->with('movement_detail_quantities')
            ->get()
            ->sum(fn ($detail) => $detail->movement_detail_quantities->sum('quantity'));

        return max(0, $done->min() - $transferred);
    }

    public function producedBy(ProductivityDetail $detail): int
    {
        return (int) array_sum((array) ($detail->settings->produced ?? []));
    }

    /**
     * Detalles recibidos del lote en el proceso (ingresos aprobados), en el orden en que se llenan con el +1:
     * Nacional, Medellín y Stara y, dentro de cada destino, por orden de llegada.
     */
    public function receivedDetails(ProductionOrder $production_order, Process $process): Collection
    {
        $order = array_flip(ProductionOrder::destinations());

        return MovementDetail::with(['movement' => ['production_order_detail'], 'movement_detail_quantities', 'counter'])
            ->where('status', MovementDetail::APPROVED)
            ->whereHas('movement', fn ($query) => $query
                ->where('model_type', Process::class)
                ->where('model_id', $process->id)
                ->whereIn('production_order_detail_id', $production_order->production_order_details_product()->select('id')))
            ->get()
            ->sortBy(fn ($detail) => sprintf('%02d-%012d', $order[$detail->movement?->production_order_detail?->destination] ?? 99, $detail->id))
            ->values();
    }

    /**
     * Contadores del lote en el paso del puesto: por detalle recibido, lo recibido (por talla) y lo que lleva el paso
     * con el +1. Cada paso de la ficha cuenta el lote completo; los puestos de un mismo paso se lo reparten.
     */
    public function counters(ProductionOrder $production_order, Process $process, Model $workstation): array
    {
        $step = $this->routeStep($production_order, $workstation, $process) ?? $workstation;
        $produced = $this->stepProduced($production_order, $step, $process);
        // Con regresos el paso hace el lote varias veces (con trapo, CENTRIFUGADO 1 dos veces)
        $passes = $this->passes($production_order, $step, $process);

        $details = $this->receivedDetails($production_order, $process)->map(fn ($detail) => [
            'movement_detail_id' => $detail->id,
            'destination' => $detail->movement?->production_order_detail?->destination,
            'received' => (int) $detail->movement_detail_quantities->sum('quantity') * $passes,
            'produced' => min($produced[$detail->id] ?? 0, (int) $detail->movement_detail_quantities->sum('quantity') * $passes),
        ])->values();

        $current = $details->first(fn ($detail) => $detail['produced'] < $detail['received']);

        return [
            'details' => $details->all(),
            'received' => (int) $details->sum('received'),
            'produced' => (int) $details->sum('produced'),
            // Detalle que se está llenando ahora (null si el lote está completo)
            'current' => $current['movement_detail_id'] ?? null,
        ];
    }

    /**
     * +1: suma una unidad al registro en el primer ingreso recibido que el paso del puesto no haya completado.
     */
    public function plus(ProductivityDetail $detail, Process $process): void
    {
        $counters = $this->counters($detail->production_order, $process, $detail->model);

        if (! $counters['current']) {
            throw new DomainException('El lote ya está completo: no quedan unidades por hacer.');
        }

        $this->addProduced($detail, $counters['current'], 1);
    }

    /**
     * −1: quita la última unidad de este mismo registro (por si se tocó de más); no toca lo de otros registros.
     */
    public function minus(ProductivityDetail $detail, Process $process): void
    {
        $received = $this->receivedDetails($detail->production_order, $process);
        $produced = (array) ($detail->refresh()->settings->produced ?? []);
        $target = $received->reverse()->first(fn ($item) => ($produced[$item->id] ?? 0) > 0);

        if (! $target) {
            throw new DomainException('Este trabajo no tiene unidades para quitar.');
        }

        $this->addProduced($detail, $target->id, -1);
    }

    /**
     * Minutos estándar de la capacidad con la que se inició (copiada en el registro; los registros anteriores toman la
     * de la regla). Por unidades es lo que tarda una unidad.
     */
    public function standardMinutes(ProductivityDetail $detail, ?int $people = null): float
    {
        $rule = $detail->rule;
        $people ??= max(1, $detail->employees()->count());
        $capacity = (array) ($detail->settings->capacity ?? []) ?: ($rule?->capacityFor($people) ?? $rule?->capacities()[0] ?? []);
        $time = (float) ($capacity['base_time'] ?? $capacity['time'] ?? 0) * $this->detailFactor($detail);

        return ($capacity['time_unit'] ?? 'min') === 'seg' ? $time / 60 : $time;
    }

    /**
     * Datos para la meta por hora (por unidades): horas de los +1 de las últimas dos horas, minutos estándar por unidad
     * y personas. La pantalla arma bloques de una hora desde el inicio oficial, con una meta fija por bloque
     * (60 min × personas ÷ minutos por unidad, menos las paradas del bloque), y muestra también la hora anterior.
     */
    public function hourStatus(ProductivityDetail $detail): array
    {
        // La pantalla arma bloques de una hora desde el inicio oficial: bastan los +1 de la hora actual y la anterior
        $from = now()->subHours(2)->format('Y-m-d H:i:s');

        return [
            'units_at' => collect((array) ($detail->settings->units_at ?? []))
                ->filter(fn ($at) => $at >= $from)
                ->values(),
            'standard_minutes' => round($this->standardMinutes($detail), 4),
            'people' => max(1, $detail->employees()->count()),
        ];
    }

    protected function addProduced(ProductivityDetail $detail, int $movement_detail_id, int $units): void
    {
        $settings = (array) $detail->refresh()->settings;
        $produced = (array) ($settings['produced'] ?? []);
        $produced[$movement_detail_id] = max(0, (int) ($produced[$movement_detail_id] ?? 0) + $units);
        $settings['produced'] = $produced;

        // Hora de cada +1 (el −1 quita la última), para el estado de la hora en curso
        $units_at = (array) ($settings['units_at'] ?? []);
        if ($units > 0) {
            $units_at[] = now()->format('Y-m-d H:i:s');
        } else {
            array_pop($units_at);
        }
        $settings['units_at'] = $units_at;
        $detail->settings = $settings;
        $detail->save();
    }

    /**
     * Orden, referencia y regla estándar que aplica al lote en el puesto (al escanear y en el registro en curso). Con
     * un registro en curso se pasa su regla, para mostrar con la que se inició aunque la configuración haya cambiado.
     */
    public function referenceData(ProductionOrder $production_order, Model $workstation, Process $process, ?Rule $rule = null, ?array $capacity = null): array
    {
        $production_order->loadMissing([
            'technical_sheet' => ['subgroup', 'collection', 'product' => ['trademark', 'subcategory']],
        ]);

        $product = $production_order->technical_sheet?->product;

        $best = app(StandardRuleService::class)->resolve($production_order, $workstation, $process);

        // Con un trabajo en curso se muestra la regla con la que se inició, aunque la configuración haya cambiado
        if ($rule && ($best['rule']->id ?? null) !== $rule->id) {
            $best = ['rule' => $rule, 'points' => null, 'criteria' => []];
        }

        // Capacidad según las personas del puesto (con un trabajo en curso, la que se copió al iniciar)
        $people = $this->eligibleEmployees($workstation)->count();
        $capacity ??= $best ? $this->capacity($best['rule'], $workstation, $people) : null;

        return [
            'production_order' => [
                'id' => $production_order->id,
                'uuid' => $production_order->uuid,
                'code' => $production_order->consecutive,
                'lot' => $production_order->cut,
            ],
            'reference' => [
                'code' => $product?->code,
                'trademark' => $product?->trademark?->name,
                'group' => $product?->trademark?->group()->first()?->name,
                'category' => $product?->subcategory?->category()->first()?->name,
                'subcategory' => $product?->subcategory?->name,
                'subgroup' => $production_order->technical_sheet?->subgroup?->name,
                // Código de la colección (settings.code) y su nombre: "C52026 - COLOMBIA MODA"
                'collection' => collect([
                    $production_order->technical_sheet?->collection?->settings?->code,
                    $production_order->technical_sheet?->collection?->name,
                ])->filter()->implode(' - ') ?: null,
            ],
            'rule' => $best ? [
                'id' => $best['rule']->id,
                'name' => $best['rule']->name,
                'time' => $capacity['time'] ?? null,
                'time_unit' => $capacity['time_unit'] ?? null,
                'people' => $capacity['people'] ?? null,
                'base_time' => $capacity['base_time'] ?? null,
                'capacities' => $best['rule']->capacities(),
                'capacity_error' => $capacity ? null : $this->capacityError($best['rule'], $workstation, $people),
                'points' => $best['points'],
                'criteria' => $best['criteria'],
            ] : null,
        ];
    }

    /**
     * Destinos de la orden con su curva actual (Nacional, Medellín, Stara, en ese orden; sin los que no tienen
     * unidades). Solo cuentan las tallas que hoy tiene la marca del producto de cada destino: las de una asociación
     * antigua no se trabajan (si son las únicas con unidades, el destino queda en cero).
     */
    public function destinations(ProductionOrder $production_order): array
    {
        $order = array_flip(ProductionOrder::destinations());
        $active = fn ($detail) => $detail->production_order_detail_quantities
            ->filter(fn ($quantity) => ! $detail->model?->trademark || $detail->model->trademark->sizes->contains('id', $quantity->size_id));

        return $production_order->production_order_details_product()
            ->with(['model' => ['trademark' => ['sizes']], 'production_order_detail_quantities' => fn ($query) => $query->with('size')->orderBy('id')])
            ->get()
            ->filter(fn ($detail) => $active($detail)->sum('quantity') > 0)
            ->sortBy(fn ($detail) => $order[$detail->destination] ?? 99)
            ->map(fn ($detail) => [
                'production_order_detail_id' => $detail->id,
                'destination' => $detail->destination,
                // Cada destino puede tener su propia referencia (producto)
                'reference' => $detail->model?->code,
                'sizes' => $active($detail)->map(fn ($quantity) => [
                    'size_id' => $quantity->size_id,
                    'name' => $quantity->size?->name,
                    'quantity' => (int) $quantity->quantity,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Registro del lote en este puesto (en curso o terminado, de cualquier turno), o null. En los procesos por lote se
     * trabaja una sola vez (en los por unidades puede trabajarse en varios registros, hasta completar lo recibido).
     */
    public function registeredDetail(ProductionOrder $production_order, Model $workstation): ?ProductivityDetail
    {
        return ProductivityDetail::where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->where('production_order_id', $production_order->id)
            ->whereNotNull('settings->scanned_at')
            ->latest('id')
            ->first();
    }

    /**
     * Puestos del proceso donde se registra el trabajo (sus operaciones, sus subprocesos sin operaciones o el proceso
     * si no tiene subprocesos), con sus empleados asociados.
     */
    public function workstations(Process $process): Collection
    {
        $subprocesses = $process->subprocesses()->with('operations')->get();

        $workstations = $subprocesses->isEmpty()
            ? collect([$process])
            : $subprocesses->flatMap(fn ($subprocess) => $subprocess->operations->isEmpty()
                ? [$subprocess]
                : $subprocess->operations->all());

        // Subproceso de cada operación, para agrupar los puestos al elegirlos en la programación
        $group_of = [];
        foreach ($subprocesses as $subprocess) {
            foreach ($subprocess->operations as $operation) {
                $group_of[$operation->id] = $subprocess->name;
            }
        }

        $employees = Employee::with('person')
            ->whereIn('workstation_id', $workstations->pluck('id'))
            ->get()
            ->groupBy('workstation_id');

        return $workstations->map(fn ($workstation) => [
            'model_type' => $workstation::class,
            'model_id' => $workstation->id,
            'name' => $workstation->name,
            'group' => $workstation instanceof Operation ? ($group_of[$workstation->id] ?? null) : null,
            'employees' => ($employees[$workstation->id] ?? collect())
                ->map(fn ($employee) => $this->employeePayload($employee))
                ->sortBy('name')
                ->values(),
        ])->values();
    }

    /**
     * Regla y tiempo por unidad (de una persona) de un lote en un puesto, o `error` si no se puede estimar.
     *
     * @return array{rule: ?Rule, unit_time: ?float, time_unit: ?string, error: ?string}
     */
    public function unitTime(ProductionOrder $production_order, Model $workstation, Process $process, mixed $manual = null): array
    {
        $rule = app(StandardRuleService::class)->resolve($production_order, $workstation, $process)['rule'] ?? null;

        if (! $rule) {
            return ['rule' => null, 'unit_time' => null, 'time_unit' => null, 'error' => "No hay una regla estándar que aplique a esta referencia en {$workstation->name}."];
        }

        $base = $rule->capacities()[0] ?? null;

        if (! $base || ! (float) $base['time']) {
            return ['rule' => $rule, 'unit_time' => null, 'time_unit' => null, 'error' => "La regla {$rule->name} no tiene el tiempo por unidad."];
        }

        // Por ojal o por golpe: el tiempo de la regla × botones de la ficha o la cantidad digitada
        return ['rule' => $rule, 'unit_time' => (float) $base['time'] * $this->factor($production_order, $workstation, $manual), 'time_unit' => $base['time_unit'], 'error' => null];
    }

    /**
     * Datos de un lote en un puesto para calcular su fila en la programación: lo que le falta a su paso, el paso (los
     * puestos de un mismo paso se reparten el lote) y el tiempo por unidad.
     */
    public function estimate(ProductionOrder $production_order, Model $workstation, Process $process): array
    {
        $step = $this->routeStep($production_order, $workstation, $process);

        if ($this->schedulesByLot($process, $workstation)) {
            return [
                'step' => $step ? $step::class.':'.$step->id : null,
                'units' => $this->lotUnitsIn($production_order, $process),
                'unit_time' => null,
                'time_unit' => null,
                'error' => null,
                'multiplier' => null,
                'last_multiplier' => null,
                'typed' => false,
            ];
        }

        $time = $step ? $this->unitTime($production_order, $workstation, $process) : null;

        return [
            'step' => $step ? $step::class.':'.$step->id : null,
            'units' => $step ? $this->unitsToDo($production_order, $workstation, $process) : 0,
            'unit_time' => $time['unit_time'] ?? null,
            'time_unit' => $time['time_unit'] ?? null,
            'error' => $time['error'] ?? null,
            // Botones: ya va en unit_time; cantidad que se digita: la pantalla multiplica por la de la fila
            'multiplier' => $this->multiplierOf($workstation),
            'last_multiplier' => $step ? $this->lastMultiplier($production_order, $workstation) : null,
            // Tandas: las unidades de cada fila las digita quien programa
            'typed' => $this->typedUnits($process, $workstation),
        ];
    }

    /**
     * Última cantidad digitada para el lote en el puesto (para precargarla al programar de nuevo).
     */
    public function lastMultiplier(ProductionOrder $production_order, Model $workstation): ?float
    {
        $value = ProductivityDetail::where('production_order_id', $production_order->id)
            ->where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->whereNotNull('settings->multiplier')
            ->latest('id')
            ->first()
            ?->settings->multiplier ?? null;

        return $value !== null ? (float) $value : null;
    }

    /**
     * Hasta cuándo una fila del turno ocupa a sus empleados: la hora real de fin si ya terminó; si está en curso, su
     * fin programado o ahora (lo que sea mayor); si no se ha iniciado, su fin programado.
     */
    public function busyUntil(ProductivityDetail $row): Carbon
    {
        if ($row->end) {
            return $row->end->copy();
        }

        $end = Carbon::parse($row->settings->scheduled->end ?? $row->start);

        return isset($row->settings->scanned_at) ? $end->max(now()) : $end;
    }

    /**
     * Registros de cualquier turno y proceso que ocupan a sus empleados en la franja [start, end): un empleado tiene que
     * terminar un trabajo para empezar otro. Se miran los que empezaron hasta un día antes (uno sin finalizar de días
     * atrás no bloquea la programación).
     */
    public function overlappingRows(Carbon $start, Carbon $end, ?int $except_id = null): Collection
    {
        return ProductivityDetail::with(['employees' => ['person'], 'model', 'productivity' => ['model'], 'production_order' => ['technical_sheet' => ['product']]])
            ->when($except_id, fn ($query) => $query->whereKeyNot($except_id))
            ->where('start', '<', $end)
            ->where('start', '>=', $start->copy()->subDay())
            ->where(fn ($query) => $query->whereNull('end')->orWhere('end', '>', $start))
            ->get()
            ->filter(fn (ProductivityDetail $row) => $this->busyUntil($row)->gt($start))
            ->values();
    }

    /**
     * Mensaje del cruce: quién (si se sabe), el puesto con la referencia, su proceso si es de otro turno, y la franja que ocupa.
     */
    public function busyMessage(ProductivityDetail $row, Productivity $shift, ?int $employee_id = null): string
    {
        $where = $row->model?->name ?? 'otro puesto';
        $lot = collect([$row->production_order?->technical_sheet?->product?->code, $row->production_order?->cut])->filter()->implode('-');
        $who = $employee_id && ($employee = $row->employees->firstWhere('id', $employee_id))
            ? $this->employeePayload($employee->loadMissing('person'))['name']
            : 'El empleado';

        if ($lot) {
            $where .= " ({$lot})";
        }

        if ($row->productivity_id !== $shift->id) {
            $where .= ' ('.($row->productivity?->model?->name ?? 'otro turno').')';
        }

        $until = $this->busyUntil($row);
        $day = $row->start->isSameDay($shift->date) ? '' : ' del '.$row->start->format('d/m');

        return sprintf('%s ya está programado en %s%s de %s a %s.', $who, $where, $day, $row->start->format('H:i'), $until->format('H:i'));
    }

    /**
     * Reparto equitativo en unidades completas: lo que sobra va de a una a los primeros (800 ÷ 3 = 267, 267, 266).
     */
    public function share(int $units, int $parts): array
    {
        if ($parts < 1) {
            return [];
        }

        $base = intdiv($units, $parts);
        $rest = $units % $parts;

        return array_map(fn ($index) => $base + ($index < $rest ? 1 : 0), range(0, $parts - 1));
    }

    /**
     * Rehace el reparto del lote en el paso del puesto que cambió (otro grupo entra o sale) entre sus registros sin
     * terminar del turno (orden de creación): lo que le falta al paso se reparte en partes iguales y a cada uno le quedan
     * sus unidades (meta: no es un tope), personas, minutos (tiempo por unidad × unidades ÷ personas) y fin estimado en
     * settings.scheduled. La meta de un registro iniciado es lo que ya hizo más su parte. Los demás pasos no cambian.
     */
    public function reshare(Productivity $shift, ProductionOrder $production_order, Model $workstation): void
    {
        $process = $shift->model;

        // Un puesto que se programa por lote no tiene unidades que repartir
        if ($this->schedulesByLot($process, $workstation)) {
            return;
        }

        $lot = $shift->productivity_details()
            ->with(['model', 'employees'])
            ->where('production_order_id', $production_order->id)
            ->whereNull('end')
            ->orderBy('id')
            ->get();

        $marked = $this->markedInSheet($production_order);
        $step_key = fn ($row) => ($step = $row->model ? ($this->routeStep($production_order, $row->model, $process, $marked) ?? $row->model) : null)
            ? $step::class.':'.$step->id
            : null;
        $changed = $this->routeStep($production_order, $workstation, $process, $marked) ?? $workstation;
        $rows = $lot->filter(fn ($row) => $step_key($row) === $changed::class.':'.$changed->id
            && (! isset($row->settings->scanned_at) || ! $this->typedUnits($process, $row->model)));

        // Cada paso de la ficha reparte lo que le falta entre sus filas (los módulos se reparten el lote; EMBONAR y
        // PINZAS hacen cada uno el lote completo)
        $parts = [];

        foreach ($rows->groupBy($step_key) as $key => $group) {
            if ($this->typedUnits($process, $group->first()->model)) {
                foreach ($group as $row) {
                    $parts[$row->id] = (int) ($row->settings->scheduled->units ?? 0);
                }

                continue;
            }

            $units = $this->unitsToDo($production_order, $group->first()->model, $process);
            $shares = $this->share($units, $group->count());

            foreach ($group->values() as $index => $row) {
                $parts[$row->id] = ($shares[$index] ?? 0) + $this->producedBy($row);
            }
        }

        foreach ($rows->values() as $row) {
            $time = $this->unitTime($production_order, $row->model, $process, $row->settings->multiplier ?? null);
            $people = $row->employees->count();
            $units = $parts[$row->id] ?? 0;
            $unit_minutes = ($time['time_unit'] ?? 'min') === 'seg' ? ($time['unit_time'] ?? 0) / 60 : ($time['unit_time'] ?? 0);
            $minutes = $people ? round($unit_minutes * $units / $people, 2) : null;

            $row->settings = array_merge((array) $row->settings, [
                // La meta del reparto, el inicio programado (la estación cambia la columna start por el real) y lo que
                // dura con sus personas (personas y regla ya están en el registro)
                'scheduled' => [
                    'start' => isset($row->settings->scanned_at) ? ($row->settings->scheduled->start ?? $row->start->format('Y-m-d H:i:s')) : $row->start->format('Y-m-d H:i:s'),
                    'units' => $units,
                    'minutes' => $minutes,
                    'end' => $minutes === null ? null : $row->start->copy()->addSeconds((int) ceil($minutes * 60))->format('Y-m-d H:i:s'),
                ],
            ]);
            $row->save();
        }
    }

    /**
     * Registros programados y sin iniciar del lote en el puesto dentro del turno. Puede haber varios: un grupo por fila.
     */
    public function scheduledDetails(Productivity $shift, Model $workstation, ProductionOrder $production_order): Collection
    {
        return $shift->productivity_details()
            ->with(['employees' => ['person']])
            ->where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->where('production_order_id', $production_order->id)
            ->scheduled()
            ->orderBy('id')
            ->get();
    }

    /**
     * Los registros programados que se pueden escanear ahora (ya llegó su hora) o, si ninguno, todos.
     */
    public function scheduledNow(Productivity $shift, Model $workstation, ProductionOrder $production_order): Collection
    {
        $rows = $this->scheduledDetails($shift, $workstation, $production_order);

        return $rows->filter(fn ($row) => $this->inScheduleWindow($row))->whenEmpty(fn () => $rows)->values();
    }

    /**
     * Registro programado del lote en el puesto, o null: de los que se pueden escanear ahora, el del grupo que inicia
     * (el que tiene más de esos empleados) o, si ninguno coincide o aún no se sabe quiénes son, el primero.
     */
    public function scheduledDetail(Productivity $shift, Model $workstation, ProductionOrder $production_order, ?Collection $employees = null): ?ProductivityDetail
    {
        return $this->scheduledNow($shift, $workstation, $production_order)
            ->sortByDesc(fn ($row) => $row->employees->pluck('id')->intersect($employees ?? [])->count())
            ->first();
    }

    /**
     * ¿El líder ya programó el puesto en este turno? Mientras no lo haga, el operario puede iniciar cualquier referencia
     * lista para no quedarse sin trabajo; en cuanto lo programe, solo se trabaja lo programado. Es por puesto, en todos los
     * procesos. Un trabajo iniciado sin programar no cuenta: no tiene settings.scheduled.
     */
    public function workstationProgrammed(Productivity $shift, Model $workstation): bool
    {
        return $shift->productivity_details()
            ->where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->whereNotNull('settings->scheduled')
            ->exists();
    }

    /**
     * Hora a la que se programó el registro (al iniciarlo, la columna start cambia por el inicio oficial).
     */
    public function scheduledStart(ProductivityDetail $row): Carbon
    {
        return Carbon::parse($row->settings->scheduled->start ?? $row->start);
    }

    /**
     * ¿Ya llegó la hora programada del registro? Después de su fin estimado se puede seguir escaneando hasta el fin del
     * turno: el inicio oficial sigue siendo la hora programada, así que el retraso se ve en la eficiencia.
     */
    public function inScheduleWindow(ProductivityDetail $row): bool
    {
        return now()->gte($this->scheduledStart($row));
    }

    /**
     * Mensaje si el lote tiene registros programados en el puesto, aún no llega la hora de ninguno y no se puede adelantar.
     * La programación es una guía (usuario, 2026-11-07): se puede adelantar en todos los procesos. En la cadena de Corte,
     * la siguiente del orden programado del puesto si el lote ya pasó por el puesto anterior; en los demás, si el grupo
     * programado está libre y es lo siguiente que ese grupo tiene en el puesto (los anteriores de la cadena se validan
     * aparte). Sin programación no aplica: esa validación es aparte.
     */
    public function scheduleWindowError(Productivity $shift, Model $workstation, ProductionOrder $production_order): ?string
    {
        $rows = $this->scheduledDetails($shift, $workstation, $production_order);

        if ($rows->isEmpty() || $rows->contains(fn ($row) => $this->inScheduleWindow($row))) {
            return null;
        }

        $row = $rows->first();
        $lot = fn (ProductionOrder $order) => collect([$order->loadMissing(['technical_sheet' => ['product']])->technical_sheet?->product?->code, $order->cut])->filter()->implode('-');

        if ($this->programsChain($shift->model)) {
            // Antes que la hora: si el lote no ha salido del puesto anterior, eso es lo que impide trabajarlo
            $sequence = $this->sequenceStatus($production_order, $workstation, $shift->model);

            if (! $sequence['ready']) {
                return $sequence['message'];
            }

            $before = $shift->productivity_details()
                ->with(['production_order' => ['technical_sheet' => ['product']]])
                ->where('model_type', $workstation::class)
                ->where('model_id', $workstation->id)
                ->where('production_order_id', '!=', $production_order->id)
                ->scheduled()
                ->get()
                ->filter(fn (ProductivityDetail $other) => $this->scheduledStart($other)->lt($this->scheduledStart($row)))
                ->sortBy(fn (ProductivityDetail $other) => $this->scheduledStart($other)->timestamp)
                ->first();

            if ($before) {
                return "Antes de esta, {$workstation->name} tiene programada {$lot($before->production_order)} ({$this->scheduledStart($before)->format('g:i A')}). Trabájala primero o pide al líder que reprograme.";
            }

            return null;
        }

        // La programación es una guía: se puede adelantar si el grupo está libre y es lo siguiente que tiene en el puesto
        $busy = $this->busyMap($workstation, $production_order->id);
        $free = $rows->first(fn ($candidate) => $candidate->employees->doesntContain(fn ($employee) => $busy->has($employee->id)));

        if (! $free) {
            $employee = $row->employees->first(fn ($employee) => $busy->has($employee->id));

            return "{$lot($production_order)} está programada en {$workstation->name} a las {$this->scheduledStart($row)->format('g:i A')}. Se puede adelantar cuando el grupo esté libre: ".$this->employeePayload($employee)['name']." está en {$busy->get($employee->id)}.";
        }

        $before = $shift->productivity_details()
            ->with(['employees', 'production_order' => ['technical_sheet' => ['product']]])
            ->where('model_type', $workstation::class)
            ->where('model_id', $workstation->id)
            ->where('production_order_id', '!=', $production_order->id)
            ->scheduled()
            ->get()
            ->filter(fn (ProductivityDetail $other) => $this->scheduledStart($other)->lt($this->scheduledStart($free))
                && $other->employees->pluck('id')->intersect($free->employees->pluck('id'))->isNotEmpty())
            ->sortBy(fn (ProductivityDetail $other) => $this->scheduledStart($other)->timestamp)
            ->first();

        return $before
            ? "Antes de esta, {$workstation->name} tiene programada {$lot($before->production_order)} ({$this->scheduledStart($before)->format('g:i A')}) con ese grupo. Trabájala primero o pide al líder que reprograme."
            : null;
    }

    /**
     * Inicio oficial de un lote programado por lote (Corte): no antes de su hora programada, salvo que se adelante (se
     * escanea antes de esa hora); adelantado empieza apenas quedó libre el puesto y el lote soltó el puesto anterior o, si
     * nada lo liberó (la primera del turno), a la hora real del escaneo.
     */
    public function lotStart(Carbon $start, ProductivityDetail $scheduled, Productivity $shift): Carbon
    {
        if (now()->gte($this->scheduledStart($scheduled))) {
            return $start->max($this->scheduledStart($scheduled));
        }

        return $start->eq($shift->startsAt()) ? now()->startOfMinute() : $start;
    }

    /**
     * Los demás registros del lote en el turno (otros módulos), con su puesto: la estación los muestra.
     */
    public function siblings(ProductivityDetail $detail): Collection
    {
        return ProductivityDetail::with('model')
            ->where('productivity_id', $detail->productivity_id)
            ->where('production_order_id', $detail->production_order_id)
            ->whereKeyNot($detail->id)
            ->orderBy('id')
            ->get();
    }

    protected function minutes(Carbon $from, Carbon $to): float
    {
        return $from->diffInSeconds($to) / 60;
    }
}
