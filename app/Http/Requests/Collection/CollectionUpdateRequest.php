<?php

namespace App\Http\Requests\Collection;

use App\Models\Collection;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CollectionUpdateRequest extends FormRequest
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
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Collection::ITEM_ID],
            'name' => ['required', 'regex:/^C\d(19|20)\d{2}$/', 'string', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . Collection::ITEM_ID],
            'description' => ['required', 'string', 'max:255', 'uppercase'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'numeric', 'digits_between:2,5'],
            'settings.start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'settings.end_date' => ['required', 'date', 'date_format:Y-m-d', 'after:settings.start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => 'Formato inválido. Debe iniciar con C y contener 5 números (los últimos 4 corresponden al año).',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la colección',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings.code' => 'Código',
            'settings.start_date' => 'Fecha de Inicio',
            'settings.end_date' => 'Fecha de Fin',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $settings = $this->input('settings', []);

            $code = $settings['code'] ?? null;
            $startDate = $settings['start_date'] ?? null;
            $endDate = $settings['end_date'] ?? null;

            $existsCode = Collection::withTrashed()
                ->where('item_id', Collection::ITEM_ID)
                ->where('id', '!=', $this->route('id'))
                ->where('settings->code', $code)
                ->exists();

            if ($existsCode) {

                $validator->errors()->add(
                    'settings.code',
                    'El código ya está registrado.'
                );
            }
            if ($startDate && $endDate) {

                $crossingCollection = Collection::withTrashed()
                    ->where('id', '!=', $this->route('id'))
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(settings, '$.start_date')) <= ?", [$endDate])
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(settings, '$.end_date')) >= ?", [$startDate]);
                    })
                    ->exists();

                if ($crossingCollection) {

                    $validator->errors()->add(
                        'settings.start_date',
                        'Ya existe una colección en ese rango de fechas.'
                    );
                }
            }
        });
    }
}
