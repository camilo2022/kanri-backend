<?php

namespace App\Mcp\Prompts\Person;

use App\Mcp\Prompts\RoutePrompt;

class PersonUpdatePrompt extends RoutePrompt
{
    protected string $name = 'person_update';

    protected string $title = 'Editar una persona';

    protected string $description = 'Editar una persona';

    protected string $method = 'PUT';

    protected string $path = '/people/update/{id}';

    protected string $business = 'Actualiza todos los datos de una persona; se reemplazan con lo enviado, por lo que hay que mandar el registro completo. Aplican las mismas condiciones del alta: el documento debe cumplir las reglas de su tipo, el teléfono las del país, y la ubicación debe estar en el último nivel disponible. Se rechaza si el documento o el correo ya pertenecen a otra persona. Si se envía una foto, reemplaza la anterior del mismo tipo y subtipo (el archivo viejo se borra); no se pudo confirmar que el tipo de foto se valide como parte del catálogo de archivos. Corregir el documento o los nombres afecta a todo lo que use a esa persona (por ejemplo, su empleado). Queda en la auditoría. La IA debe mostrar qué cambia y confirmar. La edición se puede revertir, pero una foto reemplazada no se recupera.';
}
