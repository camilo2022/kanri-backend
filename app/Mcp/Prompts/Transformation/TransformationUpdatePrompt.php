<?php

namespace App\Mcp\Prompts\Transformation;

use App\Mcp\Prompts\RoutePrompt;

class TransformationUpdatePrompt extends RoutePrompt
{
    protected string $name = 'transformation_update';

    protected string $title = 'Editar la ficha técnica de una transformación';

    protected string $description = 'Editar la ficha técnica de una transformación';

    protected string $method = 'PUT';

    protected string $path = '/technical_sheets/transformations/update/{id}';

    protected string $business = 'Edita la ficha técnica de una transformación. Usa el mismo Request y las mismas reglas que la edición normal de ficha (acepta el permiso technical_sheets.update o technical_sheets.transformations.update): fecha no posterior a hoy, código y producto sin repetir en otra ficha, catálogos activos, insumos obligatorios, fotos «código-D» y «código-T» y tablas con sus reglas. Efectos: actualiza los datos de la ficha, sincroniza los insumos con los enviados, guarda fotos nuevas y crea o actualiza los detalles por proceso con su estado y tipología. Diferencias frente a la edición normal: aquí el guardado de detalles no conserva lo ya Aprobado y no elimina los subprocesos automáticos que dejan de aplicar; además, aunque el Request valida removed_details, este controlador no los quita. No toca la orden de producción ni los productos creados con la transformación. Confirmar con la persona qué ficha edita y qué estados cambia. Es reversible editando de nuevo.';
}
