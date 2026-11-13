<?php

namespace App\Traits;

use App\Models\Process;
use App\Models\Subprocess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Valida los siguientes de un subproceso u operación: solo cuando el padre tiene sus hijos en secuencia, solo
 * hermanos (hijos del mismo padre), nunca él mismo y sin formar ciclos (A → B → A).
 */
trait ValidatesWorkflowChain
{
    /**
     * @param  Model|null  $parent  Proceso o subproceso padre
     * @param  string  $flowKey  Clave del flujo en settings del padre (subprocess_flow u operation_flow)
     * @param  string  $children  Relación del padre con sus hijos (subprocesses u operations)
     * @param  string  $after  Relación de los hijos con sus siguientes (after_subprocesses u after_operations)
     */
    protected function validateChain(Validator $validator, ?Model $parent, string $flowKey, string $children, string $after, ?int $selfId = null): void
    {
        $next = collect($this->input($after, []))->map(fn ($id) => (int) $id);

        if ($next->isEmpty() || ! $parent) {
            return;
        }

        if (($parent->settings->{$flowKey} ?? Process::PARALLEL) !== Process::SEQUENCE) {
            $children_label = $children === 'subprocesses' ? 'subprocesos' : 'operaciones';
            $validator->errors()->add($after, "{$parent->name} no tiene sus {$children_label} en secuencia: no se pueden definir siguientes.");

            return;
        }

        /** @var MorphToMany $relation */
        $relation = $parent->{$children}();
        $siblings = $relation->with($after)->get()->keyBy('id');

        foreach ($next as $index => $id) {
            if ($selfId && $id === $selfId) {
                $validator->errors()->add("{$after}.{$index}", 'No puede ser su propio siguiente.');
            } elseif (! $siblings->has($id)) {
                $validator->errors()->add("{$after}.{$index}", "No pertenece a {$parent->name}.");
            }
        }

        if (! $selfId || $validator->errors()->isNotEmpty()) {
            return;
        }

        // Cada flecha con su condición de tono (solo subprocesos); la propia, la que llega en el formulario
        $edges = $siblings->map(fn ($sibling) => $sibling->{$after}->pluck('id')
            ->mapWithKeys(function ($id) use ($sibling) {
                $conditions = (array) ($sibling->settings->next_conditions ?? []);
                $condition = $conditions[$id] ?? null;

                return [$id => $condition === null ? null : json_decode(json_encode($condition), true)];
            })
            ->all())->all();
        $own_conditions = (array) $this->input('settings.next_conditions', []);
        $edges[$selfId] = $next->mapWithKeys(fn ($id) => [$id => $own_conditions[$id] ?? null])->all();

        // Un regreso (LAVADO ADICIONAL ↩ CENTRIFUGADO 1) debe volver a un paso anterior y no cuenta como ciclo
        foreach ($edges[$selfId] as $id => $condition) {
            if (($condition['returns'] ?? false) && ! $this->reaches($edges, $id, $selfId)) {
                $name = $siblings->get($id)?->name ?? $id;
                $validator->errors()->add($after, "{$name} no está antes de este elemento en la cadena: no se puede regresar ahí.");

                return;
            }
        }
        $edges = array_map(fn ($targets) => array_filter($targets, fn ($condition) => ! ($condition['returns'] ?? false)), $edges);

        // Ciclo: desde los siguientes nuevos se vuelve a él mismo. Con condiciones de tono se revisa tono por tono (los
        // nombrados en alguna condición y «cualquier otro»): SEDA → REVISIÓN → PLANCHA → SEDA vale si ningún tono lo
        // recorre completo
        $tones = collect($edges)->flatten(1)->filter()->flatMap(fn ($condition) => (array) ($condition['wash_tone_ids'] ?? []))
            ->map(fn ($id) => (int) $id)->unique()->push(null);

        foreach ($tones as $tone) {
            $pending = collect($edges[$selfId])->filter(fn ($condition) => $this->toneApplies($condition, $tone))->keys()->all();
            $seen = [];

            while ($pending) {
                $id = array_pop($pending);
                if ($id === $selfId) {
                    $validator->errors()->add($after, 'Los siguientes forman un ciclo: la cadena vuelve a este mismo elemento.');

                    return;
                }
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                array_push($pending, ...collect($edges[$id] ?? [])->filter(fn ($condition) => $this->toneApplies($condition, $tone))->keys()->all());
            }
        }
    }

    /**
     * Valida «Depende de» de una operación en un subproceso con sus operaciones en paralelo: solo hermanas, nunca ella
     * misma y sin ciclos (PARCHE depende de RELOJERA y RELOJERA de PARCHE). En secuencia no aplica: la relación es la
     * cadena («Siguientes operaciones»).
     */
    protected function validateDependencies(Validator $validator, ?Model $parent, ?int $selfId = null): void
    {
        $before = collect($this->input('before_operations', []))->map(fn ($id) => (int) $id);

        if ($before->isEmpty() || ! $parent) {
            return;
        }

        if (($parent->settings->operation_flow ?? Process::PARALLEL) === Process::SEQUENCE) {
            $validator->errors()->add('before_operations', "{$parent->name} tiene sus operaciones en secuencia: define la cadena con «Siguientes operaciones».");

            return;
        }

        $siblings = $parent->operations()->with('after_operations')->get()->keyBy('id');

        foreach ($before as $index => $id) {
            if ($selfId && $id === $selfId) {
                $validator->errors()->add("before_operations.{$index}", 'No puede depender de sí misma.');
            } elseif (! $siblings->has($id)) {
                $validator->errors()->add("before_operations.{$index}", "No pertenece a {$parent->name}.");
            }
        }

        if (! $selfId || $validator->errors()->isNotEmpty()) {
            return;
        }

        // Flechas «de quién depende → quién depende», con las de esta operación reemplazadas por las del formulario: hay
        // ciclo si desde ella se vuelve a ella
        $edges = $siblings->map(fn ($sibling) => array_values(array_diff($sibling->after_operations->pluck('id')->all(), [$selfId])))->all();

        foreach ($before as $id) {
            $edges[$id][] = $selfId;
        }

        $pending = $edges[$selfId] ?? [];
        $seen = [];

        while ($pending) {
            $id = array_pop($pending);

            if ($id === $selfId) {
                $validator->errors()->add('before_operations', 'Las dependencias forman un ciclo: una operación terminaría esperándose a sí misma.');

                return;
            }

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            array_push($pending, ...($edges[$id] ?? []));
        }
    }

    /**
     * ¿Desde $from, siguiendo las flechas que no son regresos, se llega a $to?
     */
    protected function reaches(array $edges, int $from, int $to): bool
    {
        $pending = [$from];
        $seen = [];

        while ($pending) {
            $id = array_pop($pending);
            if ($id === $to) {
                return true;
            }
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            foreach ($edges[$id] ?? [] as $next => $condition) {
                if (! ($condition['returns'] ?? false)) {
                    $pending[] = (int) $next;
                }
            }
        }

        return false;
    }

    /**
     * ¿La flecha vale para el tono? Sin condición, para todos; «solo estos tonos» o, con except, «todos menos estos».
     * El tono null es uno que ninguna condición nombra.
     */
    protected function toneApplies(?array $condition, ?int $tone): bool
    {
        $tones = array_map('intval', (array) ($condition['wash_tone_ids'] ?? []));

        if (! $tones) {
            return true;
        }

        $listed = $tone !== null && in_array($tone, $tones, true);

        return ($condition['except'] ?? false) ? ! $listed : $listed;
    }

    /**
     * Subprocesos siguientes de una operación (sale de su subproceso y abre una línea): el proceso debe tener sus
     * subprocesos en secuencia y deben ser del mismo proceso, distintos del subproceso de la operación.
     */
    protected function validateOperationExits(Validator $validator, ?Subprocess $subprocess): void
    {
        $next = collect($this->input('after_subprocesses', []))->map(fn ($id) => (int) $id);
        $process = $subprocess?->process()->first();

        if ($next->isEmpty() || ! $process) {
            return;
        }

        if (($process->settings->subprocess_flow ?? Process::PARALLEL) !== Process::SEQUENCE) {
            $validator->errors()->add('after_subprocesses', "{$process->name} no tiene sus subprocesos en secuencia: no se pueden definir subprocesos siguientes.");

            return;
        }

        $siblings = $process->subprocesses()->pluck('subitems.id')->map(fn ($id) => (int) $id);

        foreach ($next as $index => $id) {
            if ($id === $subprocess->id) {
                $validator->errors()->add("after_subprocesses.{$index}", 'No puede ser su propio subproceso.');
            } elseif (! $siblings->contains($id)) {
                $validator->errors()->add("after_subprocesses.{$index}", "No pertenece a {$process->name}.");
            }
        }
    }
}
