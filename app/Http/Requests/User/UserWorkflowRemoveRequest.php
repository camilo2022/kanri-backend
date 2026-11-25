<?php

namespace App\Http\Requests\User;

use App\Models\Operation;
use App\Models\Process;
use App\Models\Subprocess;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserWorkflowRemoveRequest extends FormRequest
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
        return ['users.workflow.remove'];
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric', 'exists:users,id'],
            'model_type' => ['required', 'string', 'in:'.implode(',', [Process::class, Subprocess::class, Operation::class])],
            'model_id' => ['required', 'numeric'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Si los datos básicos ya fallaron no hay nada coherente que validar a fondo (evita errores 500 con datos vacíos)
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $exists = $this->input('model_type')::whereKey($this->input('model_id'))->exists();

            if (! $exists) {
                $validator->errors()->add(
                    'model_id',
                    'El elemento seleccionado no existe.'
                );

                return;
            }

            $user = User::with(['processes', 'subprocesses', 'operations'])->findOrFail($this->input('id'));

            if ($this->input('model_type') === Process::class) {

                $assigned = $user->processes()->where('id', $this->input('model_id'))
                    ->exists();

            } elseif ($this->input('model_type') === Subprocess::class) {

                $assigned = $user->subprocesses()->where('id', $this->input('model_id'))
                    ->exists();

            } elseif ($this->input('model_type') === Operation::class) {

                $assigned = $user->operations()->where('id', $this->input('model_id'))
                    ->exists();

            } else {
                return;
            }

            if (! $assigned) {
                $validator->errors()->add(
                    'model_id',
                    'El elemento no está asignado al usuario seleccionado.'
                );
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
