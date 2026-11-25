<?php

namespace App\Http\Requests\Productivity;

use App\Models\Productivity;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductivityAllRequest extends FormRequest
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
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['productivity.all'];
    }

    public function rules(): array
    {
        return [
            'model_type' => ['required', 'string', 'in:'.implode(',', [Process::class, Subprocess::class, Operation::class])],
            'model_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.match ($this->input('model_type')) {
                Process::class => Process::ITEM_ID,
                Subprocess::class => Subprocess::ITEM_ID,
                Operation::class => Operation::ITEM_ID,
                default => 0,
            }],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            // Turnos de un día (tableros)
            'date' => ['nullable', 'date_format:Y-m-d'],
            // Rango de fechas del listado de turnos
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['nullable', 'numeric'],
            'column' => ['nullable', 'string', 'in:'.implode(',', array_keys(Productivity::sortable_columns()))],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function attributes(): array
    {
        return [
            'model_type' => 'Tipo de elemento (proceso, subproceso u operación).',
            'model_id' => 'Identificador del elemento.',
            'per_page' => 'Registros por página.',
            'page' => 'N° de página.',
            'date' => 'Fecha del turno.',
            'date_from' => 'Fecha desde.',
            'date_to' => 'Fecha hasta.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',
        ];
    }
}
