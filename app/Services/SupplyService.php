<?php

namespace App\Services;

use App\Models\FileSubtype;
use App\Models\Size;
use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SupplyService
{
    // Campos del formulario que no son valores sueltos ni relaciones directas: fotos y campos «asociado a»
    public const SPECIAL_TYPES = ['photo', 'associated'];

    /**
     * Campos del formulario del tipo de insumo que son foto: el campo Foto (general) y el «asociado a» sin catálogo.
     */
    public function photoFields(iterable $form): array
    {
        return collect($form)
            ->filter(fn ($column) => $column->type === 'photo' || ($column->type === 'associated' && ! isset($column->model)))
            ->values()
            ->all();
    }

    /**
     * Campos «asociado a» que son lista (tienen catálogo): se guardan en settings.values como [{origin_id, ids}].
     */
    public function associatedListFields(iterable $form): array
    {
        return collect($form)
            ->filter(fn ($column) => $column->type === 'associated' && isset($column->model))
            ->values()
            ->all();
    }

    /**
     * Lista asociada normalizada: una fila por elemento del origen, con ids enteros.
     */
    public function associatedRows(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->map(fn ($row) => [
                'origin_id' => $this->originKey(data_get($row, 'origin_id')),
                'ids' => collect(Arr::wrap(data_get($row, 'ids')))->filter(fn ($id) => $id !== null && $id !== '')->map(fn ($id) => (int) $id)->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Valida las listas asociadas, las fotos, los archivos a borrar y el filtro de tallas. Las fotos que el insumo ya
     * tiene (al editar) cuentan para lo obligatorio, salvo las que se piden borrar.
     */
    public function validate($validator, Request $request, iterable $form, ?Supply $supply = null): void
    {
        $form = collect($form);
        $selected = fn ($column) => $this->originValues($form->firstWhere('field', $column->associated_to), $request->input('class', []), $request->input('values', []));
        $label = fn ($field) => data_get($form->firstWhere('field', $field), 'label', $field);

        foreach ($form->filter(fn ($column) => $column->type === 'selectdinamic' && isset($column->finished_product)) as $column) {
            foreach (Arr::wrap($request->input("class.$column->field")) as $key => $id) {
                if ($this->wrongSize($column, [$id])) {
                    $validator->errors()->add(
                        ($column->cardinality ?? 'single') === 'multiple' ? "class.$column->field.$key" : "class.$column->field",
                        'No es una talla permitida para este campo.'
                    );
                }
            }
        }

        foreach ($this->associatedListFields($form) as $column) {
            $origins = $selected($column);
            $rows = $this->associatedRows($request->input("values.$column->field"));
            $exists = collect($column->rules ?? [])->first(fn ($rule) => str_starts_with($rule, 'exists'));

            foreach ($rows as $index => $row) {
                if (! $origins->contains($row['origin_id'])) {
                    $validator->errors()->add("values.$column->field.$index.origin_id", 'No está elegido en '.$label($column->associated_to).'.');

                    continue;
                }

                if (collect($rows)->where('origin_id', $row['origin_id'])->count() > 1) {
                    $validator->errors()->add("values.$column->field.$index.origin_id", 'Está repetido.');
                }

                if (in_array('required', $column->rules ?? []) && empty($row['ids'])) {
                    $validator->errors()->add("values.$column->field.$index.ids", 'Es obligatorio.');
                }

                if ($exists && Validator::make(['ids' => $row['ids']], ['ids.*' => [$this->resolveRule($exists)]])->fails()) {
                    $validator->errors()->add("values.$column->field.$index.ids", 'Tiene elementos que no existen.');
                }

                if ($this->wrongSize($column, $row['ids'])) {
                    $validator->errors()->add("values.$column->field.$index.ids", 'Tiene tallas que no están permitidas para este campo.');
                }
            }

            if (in_array('required', $column->rules ?? [])) {
                foreach ($origins as $origin_id) {
                    if (! collect($rows)->contains('origin_id', $origin_id)) {
                        $validator->errors()->add("values.$column->field", 'Falta elegir los de cada elemento de '.$label($column->associated_to).'.');

                        break;
                    }
                }
            }
        }

        $deleted = collect(Arr::wrap($request->input('deleted_files')))->filter()->map(fn ($id) => (int) $id);
        $files = $supply?->files ?? collect();

        if ($deleted->diff($files->pluck('id'))->isNotEmpty()) {
            $validator->errors()->add('deleted_files', 'Hay archivos que no son de este insumo.');
        }

        foreach ($this->photoFields($form) as $column) {
            $slots = $column->type === 'photo' ? collect([null]) : $selected($column);

            foreach ($slots as $origin_id) {
                $key = $origin_id === null ? "photos.$column->field" : "photos.$column->field.$origin_id";
                $uploads = Arr::wrap($request->file($key));
                $kept = $files
                    ->filter(fn ($file) => data_get($file->settings, 'field') === $column->field && data_get($file->settings, 'origin_id') === $origin_id)
                    ->reject(fn ($file) => $deleted->contains($file->id));

                foreach ($uploads as $upload) {
                    if (Validator::make(['photo' => $upload], ['photo' => ['file', 'mimes:jpg,jpeg,png', 'max:2048']])->fails()) {
                        $validator->errors()->add($key, 'Debe ser una imagen JPG o PNG de máximo 2 MB.');

                        break;
                    }
                }

                if (($column->cardinality ?? 'single') === 'single' && count($uploads) > 1) {
                    $validator->errors()->add($key, 'Solo se permite una foto.');
                }

                if (in_array('required', $column->rules ?? []) && empty($uploads) && $kept->isEmpty()) {
                    $validator->errors()->add($key, 'Es obligatorio.');
                }
            }
        }
    }

    /**
     * Guarda las fotos nuevas, borra las que se pidieron y las de elementos del origen que ya no están elegidos. Con
     * «una sola foto», la nueva reemplaza la anterior. Se llama después de sincronizar las relaciones del insumo.
     */
    public function syncFiles(Supply $supply, Request $request, iterable $form): void
    {
        $file_service = app(FileService::class);
        $subtype = FileSubtype::with(['file_type'])->find(Supply::PHOTO_FILE_SUBTYPE_ID);
        $files = $supply->files()->get();

        foreach ($files->whereIn('id', Arr::wrap($request->input('deleted_files'))) as $file) {
            $file_service->remove($file);
        }

        $files = $supply->files()->get();

        foreach ($this->photoFields($form) as $column) {
            $field_files = $files->filter(fn ($file) => data_get($file->settings, 'field') === $column->field);
            $slots = collect([null]);

            if ($column->type === 'associated') {
                $origin = collect($form)->firstWhere('field', $column->associated_to);
                $slots = isset($origin->model)
                    ? $supply->related($origin->model)->withTrashed()->get()->modelKeys()
                    : $this->originValues($origin, [], (array) data_get($supply->settings, 'values', []))->all();

                foreach ($field_files->reject(fn ($file) => in_array(data_get($file->settings, 'origin_id'), $slots, true)) as $file) {
                    $file_service->remove($file);
                }
            }

            foreach ($slots as $origin_id) {
                $uploads = Arr::wrap($request->file($origin_id === null ? "photos.$column->field" : "photos.$column->field.$origin_id"));

                if (empty($uploads)) {
                    continue;
                }

                if (($column->cardinality ?? 'single') === 'single') {
                    foreach ($field_files->filter(fn ($file) => data_get($file->settings, 'origin_id') === $origin_id) as $file) {
                        $file_service->remove($file);
                    }
                }

                $prefix = $this->photoPrefix($supply, $origin_id === null ? null : collect($form)->firstWhere('field', $column->associated_to)->model ?? null, $origin_id);
                $number = $supply->files()->get()
                    ->map(fn ($file) => preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $file->name, $match) ? (int) $match[1] : 0)
                    ->max() ?? 0;

                foreach ($uploads as $upload) {
                    $file_service->add(
                        $supply,
                        $upload,
                        $subtype->file_type->first()->id,
                        $subtype->id,
                        $origin_id === null ? ['field' => $column->field] : ['field' => $column->field, 'origin_id' => $origin_id],
                        'public',
                        'supply',
                        $prefix.'-'.(++$number)
                    );
                }
            }
        }
    }

    /**
     * Nombre de las fotos: el insumo y, si es asociada, el código del elemento del origen (su id si no tiene código),
     * por ejemplo 15.02.000.001-10204. El número se agrega al guardar.
     */
    private function photoPrefix(Supply $supply, ?string $origin_model, int|string|null $origin_id): string
    {
        $name = preg_replace('/[^A-Z0-9.\-]+/', '_', Str::upper($supply->name));

        if ($origin_id === null) {
            return $name;
        }

        $code = $origin_model ? data_get($origin_model::withTrashed()->find($origin_id), 'settings.code') : null;

        return $name.'-'.preg_replace('/[^A-Z0-9.\-]+/', '_', Str::upper((string) ($code ?: $origin_id)));
    }

    /**
     * Valores elegidos en el campo de origen: ids si es de catálogo (class), o lo escrito o elegido si no (values). Un
     * campo de un solo valor da una lista de uno.
     */
    private function originValues(?object $origin, mixed $class, mixed $values)
    {
        $raw = isset($origin->model) ? data_get($class, $origin->field ?? '') : data_get($values, $origin->field ?? '');

        return collect(Arr::wrap($raw))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => $this->originKey($value))
            ->unique()
            ->values();
    }

    private function originKey(mixed $value): int|string
    {
        return is_numeric($value) ? (int) $value : (string) $value;
    }

    private function wrongSize(object $column, array $ids): bool
    {
        if (($column->model ?? null) !== Size::class || ! isset($column->finished_product) || empty($ids)) {
            return false;
        }

        return Size::whereIn('id', $ids)->where('settings->finished_product', ! $column->finished_product)->exists();
    }

    private function resolveRule(string $rule): string
    {
        preg_match_all('/([A-Za-z\\\\]+)::([A-Z_]+)/', $rule, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (defined("{$match[1]}::{$match[2]}")) {
                $rule = str_replace("{$match[1]}::{$match[2]}", constant("{$match[1]}::{$match[2]}"), $rule);
            }
        }

        return $rule;
    }
}
