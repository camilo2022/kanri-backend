<?php

namespace App\Mcp\Prompts\Person;

use App\Mcp\Prompts\RoutePrompt;

class PersonStorePrompt extends RoutePrompt
{
    protected string $name = 'person_store';

    protected string $title = 'Crear una persona';

    protected string $description = 'Crear una persona';

    protected string $method = 'POST';

    protected string $path = '/people/store';

    protected string $business = 'Registra a una persona (datos personales y de contacto), base para empleados y contactos de proveedores. Campos clave: tipo y número de documento (el número debe cumplir las reglas del tipo; un tipo de documento sin reglas configuradas no se puede usar); ubicación como nivel («country», «department» o «city») más el identificador, y debe ser el último nivel disponible; teléfono según las reglas del país indicado (un país sin reglas no se puede usar); correo (opcional, único); barrio (opcional, mayúsculas); y foto opcional, que reemplaza a la anterior del mismo tipo. Se rechaza si el documento o el correo ya existen, o si la fecha de nacimiento no es pasada. Queda en la auditoría. La IA debe confirmar nombres, documento y contacto con la persona antes de ejecutar. Es reversible: se puede editar o desactivar.';
}
