<?php

namespace App\Http\Requests\Cache\Builder;

use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;

class BuilderDeleteRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize(): bool
    {
        return app(PermissionService::class)->authorize($this->user(), $this->permissions());
    }

    public function permissions(): array
    {
        return ['cache.builders.delete'];
    }

    public function rules(): array
    {
        return [
            'uuid' => ['required']
        ];
    }

    public function attributes(): array
    {
        return [
            'uuid' => 'Constructor',
            'builder' => 'Constructor'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $builder_id = $this->route('uuid');

            if (!Cache::has("curve_builder:{$builder_id}")) {
                $validator->errors()->add(
                    'builder',
                    'El constructor no existe.'
                );
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['uuid' => $this->route('uuid')]);
    }
}
