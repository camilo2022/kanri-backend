<?php

namespace App\Http\Requests\Typification\StopType\Stop;

use App\Models\Stop;
use App\Models\StopType;
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
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'exists:subitems,id,item_id,'.Stop::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Stop::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'settings.range_time' => ['sometimes', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'exists' => 'No está registrado.',
            'uppercase' => 'Debe de ingresar mayúsculas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del subtipo de arhivo',
            'name' => 'Nombre',
            'description' => 'Descripción',
        ];
    }

    public function withValidator($validator)
    {
        $stop_type = StopType::find($this->input('stop_type_id'));

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
}
