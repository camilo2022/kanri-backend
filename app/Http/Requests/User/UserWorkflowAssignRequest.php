<?php

namespace App\Http\Requests\User;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserWorkflowAssignRequest extends FormRequest
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
        logger($this);

        return [
            'id' => ['required', 'numeric', 'exists:users,id'],
            'model_type' => ['required', 'string', 'in:'.implode(',', [Process::class, Subprocess::class, Operation::class])],
            'model_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.$this->input('model_type')::ITEM_ID, 'unique:model_has_subitems,model_id,NULL,NULL,subitem_id,'.$this->route('id').',model_type,'.$this->input('model_type')],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'numeric' => 'Debe ser un número.',
            'exists' => 'No hay ningún registro.',
            'unique' => 'Ya registrado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del usuario',
            'model_type' => 'Tipo de modelo',
            'model_id' => 'Identificador del elemento',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
