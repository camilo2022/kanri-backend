<?php

namespace App\Services;

use App\Models\Bank;

class BankAccountService
{
    /**
     * Error de la cuenta según la regla de su banco para ese tipo de cuenta, como [campo, mensaje], o null si la
     * cumple. Un banco sin reglas no recibe cuentas, y un tipo de cuenta sin regla es uno que el banco no maneja.
     */
    public function accountError(int $bank_id, int $account_type_id, string $account_number): ?array
    {
        $validations = collect(data_get(Bank::find($bank_id)?->settings, 'validations', []));

        if ($validations->isEmpty()) {
            return ['bank_id', 'El banco no tiene reglas de validación de cuentas configuradas.'];
        }

        $validation = $validations->first(fn ($v) => (int) data_get($v, 'account_type_id') === $account_type_id);

        if (! $validation) {
            return ['account_type_id', 'El banco no maneja este tipo de cuenta.'];
        }

        if (@preg_match((string) data_get($validation, 'regex'), $account_number) !== 1) {
            return ['account_number', (string) data_get($validation, 'message')];
        }

        return null;
    }
}
