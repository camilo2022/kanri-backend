<?php

namespace App\Http\Requests\Typification\SupplierType\Supplier;

use App\Models\AccountType;
use App\Models\Bank;
use App\Models\BloodType;
use App\Models\DocumentType;
use App\Models\Gender;
use App\Models\Supplier;
use App\Models\SupplierType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplierUpdateRequest extends FormRequest
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

        $supplier_type = SupplierType::find($this->input('supplier_type_id'));

        $rules = [
            'id' => ['required', 'exists:subitems,id,item_id,'.Supplier::ITEM_ID],
            'supplier_type_id' => ['required', 'exists:subitems,id,item_id,'.SupplierType::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,'.$this->route('id').',id,item_id,'.Supplier::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'numeric', 'digits_between:2,5'],
        ];

        if ($supplier_type?->settings?->has_person) {

            $rules['person'] = ['required', 'array'];
            $rules['person.id'] = ['nullable', 'exists:people,id'];
            $rules['person.document_type_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.DocumentType::ITEM_ID.',deleted_at,NULL'];
            $rules['person.document'] = ['required', 'numeric', 'digits_between:5,14', 'unique:people,document,'.$this->input('person.id')];
            $rules['person.names'] = ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'];
            $rules['person.last_names'] = ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'];
            $rules['person.gender_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.Gender::ITEM_ID.',deleted_at,NULL'];
            $rules['person.birth_date'] = ['required', 'date', 'date_format:Y-m-d', 'before:now'];
            $rules['person.blood_type_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.BloodType::ITEM_ID.',deleted_at,NULL'];
            $rules['person.address'] = ['required', 'string', 'max:255'];
            $rules['person.phone'] = ['required', 'numeric', 'digits:10'];

        }

        if ($supplier_type?->settings?->has_account_bank) {
            $rules['account_bank'] = ['required', 'array'];
            $rules['account_bank.bank_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.Bank::ITEM_ID.',deleted_at,NULL'];
            $rules['account_bank.account_number'] = ['required', 'numeric', 'digits:10'];
            $rules['account_bank.account_type_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.AccountType::ITEM_ID.',deleted_at,NULL'];
            $rules['account_bank.bank_certificate'] = ['nullable', 'array'];
            $rules['account_bank.bank_certificate.file'] = ['nullable', 'file', 'mimes:pdf', 'max:10240'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un número.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'exists' => 'No está registrado.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'digits_between' => 'Debe tener entre 2 y 5 dígitos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del tipo de proveedor',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuraciones',
            'settings.code' => 'Código',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if ($this->filled('settings.code')) {

                $exists = Supplier::query()
                    ->where('settings->code', $this->input('settings.code'))
                    ->whereKeyNot($this->route('id'))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'settings.code',
                        'El código ya se encuentra registrado.'
                    );
                }
            }

            $supplier_type = SupplierType::find(
                $this->input('supplier_type_id')
            );

            if (! $supplier_type?->settings?->has_account_bank) {
                return;
            }

            $supplier = Supplier::find($this->route('id'));

            if (! $supplier) {
                return;
            }

            $bank_account = $supplier->bank_account()->first();

            $has_new_certificate = $this->hasFile(
                'account_bank.new_bank_certificate.file'
            );

            if (! $bank_account) {

                if (! $has_new_certificate) {

                    $validator->errors()->add(
                        'account_bank.bank_certificate',
                        'El certificado bancario es obligatorio.'
                    );
                }

                return;
            }

            $has_certificate = $bank_account->bank_certificate !== null;

            if (! $has_certificate && ! $has_new_certificate) {

                $validator->errors()->add(
                    'account_bank.bank_certificate',
                    'El certificado bancario es obligatorio.'
                );
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
