<?php

namespace App\Http\Requests\Movement;

use App\Models\MovementDetail;
use App\Services\PermissionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MovementDeclineRequest extends FormRequest
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
        return ['movements.decline'];
    }

    public function rules(): array
    {
        return [
            'movement_detail_id' => ['required', 'integer', 'exists:movement_details,id'],
            'observation' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'movement_detail_id' => 'Traslado',
            'observation' => 'Observación',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Rechazar no crea ni mueve unidades: el traslado ya se validó al guardarse, solo debe seguir pendiente
            $movement_detail = MovementDetail::find($this->input('movement_detail_id'));

            if ($movement_detail && $movement_detail->status !== MovementDetail::PENDING) {
                $validator->errors()->add(
                    'movement_detail_id',
                    'Solo se pueden rechazar movimientos que estén pendientes.'
                );
            }
        });
    }
}
