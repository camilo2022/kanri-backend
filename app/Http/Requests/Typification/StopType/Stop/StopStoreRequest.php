<?php

namespace App\Http\Requests\Typification\StopType\Stop;

use App\Models\Stop;
use App\Models\StopType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StopStoreRequest extends FormRequest
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
            'stop_type_id' => ['required', 'exists:subitems,id,item_id,'.StopType::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,'.Stop::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'settings.range_time' => ['nullable', 'numeric', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'stop_type_id' => 'Tipo de parada',
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
}
