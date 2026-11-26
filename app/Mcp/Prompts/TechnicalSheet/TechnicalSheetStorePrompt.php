<?php

namespace App\Mcp\Prompts\TechnicalSheet;

use App\Mcp\Prompts\RoutePrompt;

class TechnicalSheetStorePrompt extends RoutePrompt
{
    protected string $name = 'technical_sheet_store';

    protected string $title = 'Crear una ficha técnica';

    protected string $description = 'Crear una ficha técnica';

    protected string $method = 'POST';

    protected string $path = '/technical_sheets/store';

    protected string $business = 'Crea una ficha técnica de un producto ya existente. Condiciones de rechazo: el producto no puede tener otra ficha (product_id único); el código de ficha es único y de máximo 11 caracteres; la fecha no puede ser posterior a hoy; todos los catálogos (colección, tipo de prenda, subgrupo, tono, color, pretina, bota, cotilla, trasero) deben existir y estar activos; patronista debe ser un empleado activo; las fotos delantera y trasera son obligatorias, deben pesar hasta 2 MB y llamarse «código-del-producto-D» y «código-del-producto-T»; debe haber un insumo de cada tipo obligatorio en ficha; y lo que va en secuencia (operaciones/subprocesos) exige que esté alguno de sus anteriores. Efectos: genera el consecutivo con un procedimiento almacenado, crea un uuid, sincroniza insumos (auditado), guarda las fotos y los detalles por proceso (con su tipología y tablas). Los subprocesos automáticos que no aplican al tono no se guardan. Hallazgo: el Request no exige model_type, model_id ni status en cada detalle; si faltan, el guardado falla. El estado de la ficha no se envía: queda el valor por defecto de la tabla («Pendiente»). Confirmar datos y fotos. No hay deshacer directo.';
}
