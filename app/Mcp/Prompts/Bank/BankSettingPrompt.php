<?php

namespace App\Mcp\Prompts\Bank;

use App\Mcp\Prompts\RoutePrompt;

class BankSettingPrompt extends RoutePrompt
{
    protected string $name = 'bank_setting';

    protected string $title = 'Configurar las reglas de las cuentas del banco';

    protected string $description = 'Configurar las reglas de las cuentas del banco';

    protected string $method = 'PUT';

    protected string $path = '/banks/setting/{id}';

    protected string $business = 'Reemplaza por completo la configuración (settings) de un banco. La única clave es validations: una lista con una fila por cada tipo de cuenta que el banco maneja (account_type_id), con la expresión regular que debe cumplir el número de cuenta y el mensaje de error que verá quien no la cumpla. El sistema usa estas filas al registrar cuentas bancarias: un tipo de cuenta sin regla se considera no manejado y un banco sin reglas no recibe cuentas. Se RECHAZA si un tipo de cuenta no existe o está eliminado, se repite, o si la regex no es válida (debe incluir delimitadores, por ejemplo /^\\d{11}$/). Se prueba cada regex antes de guardar. Confirma con la persona la lista completa, porque lo enviado sustituye lo anterior; enviar la lista vacía deja al banco sin cuentas. Es reversible reenviando la configuración previa.';
}
