<?php

namespace App\Http\Requests\Person;

use App\Models\BloodType;
use App\Models\DocumentType;
use App\Models\File;
use App\Models\Gender;
use App\Services\PersonService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PersonStoreRequest extends FormRequest
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
            'document_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.DocumentType::ITEM_ID.',deleted_at,NULL'],
            'document' => ['required', 'string', 'max:30', 'unique:people,document'],
            'names' => ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'],
            'last_names' => ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'],
            'gender_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.Gender::ITEM_ID.',deleted_at,NULL'],
            'birth_date' => ['required', 'date', 'date_format:Y-m-d', 'before:now'],
            'blood_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,'.BloodType::ITEM_ID.',deleted_at,NULL'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:people,email'],
            'location_type' => ['required', 'string', 'in:'.implode(',', array_keys(PersonService::LOCATION_TYPES))],
            'location_id' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'uppercase', 'max:100'],
            'phone_country_id' => ['required', 'integer', 'exists:countries,id'],
            'phone' => ['required', 'string', 'max:20'],
            'photo' => ['sometimes', 'mimes:jpg,jpeg,png,webp,avif', 'max:10240', 'dimensions:max_width='.File::MAX_IMAGE_SIDE.',max_height='.File::MAX_IMAGE_SIDE],
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => "Formato inválido:\n- Solo letras.",
            'photo.max' => 'Se permite máximo :max KB.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $service = app(PersonService::class);

            // Documento según las reglas de su tipo (un tipo sin reglas no se puede usar)
            if (! $validator->errors()->has('document_type_id') && ! $validator->errors()->has('document')) {
                if ($error = $service->documentError($this->integer('document_type_id'), (string) $this->input('document'))) {
                    $validator->errors()->add('document', $error);
                }
            }

            // Teléfono según las reglas de su país (un país sin reglas no se puede usar)
            if (! $validator->errors()->has('phone_country_id') && ! $validator->errors()->has('phone')) {
                if ($error = $service->phoneError($this->integer('phone_country_id'), (string) $this->input('phone'))) {
                    $validator->errors()->add('phone', $error);
                }
            }

            // Ubicación en el último nivel disponible (país, departamento o ciudad)
            if (! $validator->errors()->has('location_type') && ! $validator->errors()->has('location_id')) {
                if ($error = $service->locationError((string) $this->input('location_type'), $this->integer('location_id'))) {
                    $validator->errors()->add('location_id', $error);
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'document' => 'Número de documento',
            'names' => 'Nombres',
            'last_names' => 'Apellidos',
            'gender_id' => 'Genero',
            'birth_date' => 'Fecha de nacimiento',
            'document_type_id' => 'Tipo de Documento',
            'blood_type_id' => 'Tipo de sangre',
            'address' => 'Dirección',
            'email' => 'Correo',
            'location_type' => 'Nivel de la ubicación',
            'location_id' => 'Ubicación',
            'neighborhood' => 'Barrio',
            'phone_country_id' => 'País del teléfono',
            'phone' => 'Número de teléfono',
            'photo' => 'Foto',
        ];
    }

    protected function passedValidation()
    {
        $this->merge(['location_type' => PersonService::LOCATION_TYPES[$this->input('location_type')]]);
    }
}
