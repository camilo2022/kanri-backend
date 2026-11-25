<?php

namespace App\Http\Requests\Typification\SupplierType\Supplier;

use App\Models\AccountType;
use App\Models\Bank;
use App\Models\BloodType;
use App\Models\DocumentType;
use App\Models\FileSubtype;
use App\Models\Gender;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Services\BankAccountService;
use App\Services\PermissionService;
use App\Services\PersonService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplierStoreRequest extends FormRequest
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
        return ['typification.supplier_types.suppliers.store'];
    }

    public function rules(): array
    {
        $supplier_type = SupplierType::find($this->input('supplier_type_id'));

        $rules = [
            'supplier_type_id' => ['required', 'exists:subitems,id,item_id,'.SupplierType::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,'.Supplier::ITEM_ID],
            'description' => ['required', 'uppercase', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'numeric', 'digits_between:1,5'],
        ];

        if ($supplier_type?->settings?->has_person) {

            $rules['person'] = ['required', 'array'];
            $rules['person.document_type_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.DocumentType::ITEM_ID.',deleted_at,NULL'];
            $rules['person.document'] = ['required', 'string', 'max:30', 'unique:people,document'];
            $rules['person.email'] = ['nullable', 'string', 'email', 'max:255', 'unique:people,email'];
            $rules['person.names'] = ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'];
            $rules['person.last_names'] = ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'];
            $rules['person.gender_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.Gender::ITEM_ID.',deleted_at,NULL'];
            $rules['person.birth_date'] = ['required', 'date', 'date_format:Y-m-d', 'before:now'];
            $rules['person.blood_type_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.BloodType::ITEM_ID.',deleted_at,NULL'];
            $rules['person.location_type'] = ['required', 'string', 'in:'.implode(',', array_keys(PersonService::LOCATION_TYPES))];
            $rules['person.location_id'] = ['required', 'integer'];
            $rules['person.address'] = ['required', 'string', 'max:255'];
            $rules['person.neighborhood'] = ['nullable', 'string', 'uppercase', 'max:100'];
            $rules['person.phone_country_id'] = ['required', 'integer', 'exists:countries,id'];
            $rules['person.phone'] = ['required', 'string', 'max:20'];

        }

        if ($supplier_type?->settings?->has_account_bank) {

            $rules['account_bank'] = ['required', 'array'];
            $rules['account_bank.bank_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.Bank::ITEM_ID.',deleted_at,NULL'];
            $rules['account_bank.account_number'] = ['required', 'string', 'max:30'];
            $rules['account_bank.account_type_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.AccountType::ITEM_ID.',deleted_at,NULL'];
            $rules['account_bank.bank_certificate'] = ['required', 'array'];
            $rules['account_bank.bank_certificate.file'] = ['required', 'file', 'mimes:pdf', 'max:10240'];
            $rules['account_bank.bank_certificate.file_subtype_id'] = ['required', 'numeric', 'exists:subitems,id,item_id,'.FileSubtype::ITEM_ID.',deleted_at,NULL'];

        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
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
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'settings.code',
                        'El código ya se encuentra registrado.'
                    );
                }
            }

            // Persona de contacto: mismas reglas que una persona (documento según su tipo, teléfono según su país, ubicación al último nivel)
            if ($this->has('person')) {
                $service = app(PersonService::class);

                if (! $validator->errors()->has('person.document_type_id') && ! $validator->errors()->has('person.document')
                    && ($error = $service->documentError($this->integer('person.document_type_id'), (string) $this->input('person.document')))) {
                    $validator->errors()->add('person.document', $error);
                }

                if (! $validator->errors()->has('person.phone_country_id') && ! $validator->errors()->has('person.phone')
                    && ($error = $service->phoneError($this->integer('person.phone_country_id'), (string) $this->input('person.phone')))) {
                    $validator->errors()->add('person.phone', $error);
                }

                if (! $validator->errors()->has('person.location_type') && ! $validator->errors()->has('person.location_id')
                    && ($error = $service->locationError((string) $this->input('person.location_type'), $this->integer('person.location_id')))) {
                    $validator->errors()->add('person.location_id', $error);
                }
            }

            $supplier_type = SupplierType::find(
                $this->input('supplier_type_id')
            );

            if (! $supplier_type?->settings?->has_account_bank) {
                return;
            }

            // Número de cuenta según la regla de su banco para ese tipo de cuenta
            if (! $validator->errors()->hasAny(['account_bank.bank_id', 'account_bank.account_type_id', 'account_bank.account_number'])
                && ($error = app(BankAccountService::class)->accountError($this->integer('account_bank.bank_id'), $this->integer('account_bank.account_type_id'), (string) $this->input('account_bank.account_number')))) {
                $validator->errors()->add('account_bank.'.$error[0], $error[1]);
            }

            $file = $this->file('account_bank.bank_certificate.file');

            if (! $file) {
                return;
            }

            $file_name = pathinfo(
                $file->getClientOriginalName(),
                PATHINFO_FILENAME
            );

            $normalize = function ($value) {
                return mb_strtoupper(
                    preg_replace(
                        '/[^A-Z0-9]/u',
                        '',
                        iconv(
                            'UTF-8',
                            'ASCII//TRANSLIT',
                            trim($value)
                        )
                    )
                );
            };

            if ($normalize($this->input('name')) !== $normalize($file_name)) {
                $validator->errors()->add(
                    'account_bank.bank_certificate',
                    'El nombre del certificado bancario debe coincidir con el nombre del proveedor.'
                );
            }
        });
    }

    protected function passedValidation()
    {
        if ($this->has('person')) {
            $this->merge(['person' => ['location_type' => PersonService::LOCATION_TYPES[$this->input('person.location_type')]] + $this->input('person')]);
        }
    }
}
