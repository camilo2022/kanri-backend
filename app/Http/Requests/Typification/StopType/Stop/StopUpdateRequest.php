<?php

namespace App\Http\Requests\Typification\StopType\Stop;

use App\Models\Stop;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StopUpdateRequest extends FormRequest
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
        return ['stop_types.stops.update'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,'.Stop::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Stop::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'settings.range_time' => ['nullable', 'numeric', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la parada',
            'name' => 'Nombre',
            'description' => 'Descripción',
        ];
    }

    public function withValidator($validator)
    {
        // El tipo es el que la parada ya tiene: editar no lo cambia
        $stop_type = Stop::with(['stop_type'])->find($this->route('id'))?->stop_type->first();

        $has_defined_time_range = (bool) data_get($stop_type?->settings, 'has_defined_time_range', false);

        $validator->sometimes(
            'settings.range_time',
            'required',
            function () use ($has_defined_time_range) {
                return $has_defined_time_range;
            }
        );
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }

    protected function passedValidation()
    {
        // waiting_units la pone el sistema (parada de espera automática del registro de trabajo): editar no la quita
        if (data_get(Stop::find($this->route('id'))?->settings, 'waiting_units')) {
            $this->merge(['settings' => array_merge($this->input('settings') ?? [], ['waiting_units' => true])]);
        }
    }
}
