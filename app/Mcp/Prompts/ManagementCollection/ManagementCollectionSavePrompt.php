<?php

namespace App\Mcp\Prompts\ManagementCollection;

use App\Mcp\Prompts\RoutePrompt;

class ManagementCollectionSavePrompt extends RoutePrompt
{
    protected string $name = 'management_collection_save';

    protected string $title = 'Crear o actualizar una ficha técnica de la colección';

    protected string $description = 'Crear o actualizar una ficha técnica de la colección';

    protected string $method = 'POST';

    protected string $path = '/management/collections/save';

    protected string $business = 'Pantalla de Gestión de colecciones: crea o actualiza en una sola llamada una ficha técnica con su producto (referencia), detalles por proceso/subproceso/operación, insumos y fotos. Si se envía id actualiza esa ficha; si no, la crea con un consecutivo generado por procedimiento almacenado. El producto se crea o actualiza según product.id. Reglas de negocio: una ficha «Pendiente» es una muestra: sus procesos deben seguir «Pendiente», el código del producto debe tener el formato código-de-marca + guion + 6 números y el código de ficha no es obligatorio; en otros estados el código del producto debe cumplir las validaciones configuradas en la marca (se omite si no cambia el código ni la marca). Para quedar «Aprobado», todos sus procesos (los enviados y los ya guardados) deben estar «Aprobado». Debe llevar un insumo de cada tipo marcado como obligatorio en ficha y no más de uno por tipo; fotos delantera y trasera nombradas «código-D» y «código-T». Lo que va en secuencia exige alguno de sus anteriores en la ficha. Hallazgos: este endpoint no conserva lo ya Aprobado (sobrescribe el estado de los detalles que lleguen) y nunca elimina detalles. Confirmar con la persona estado, código y procesos. No hay deshacer directo.';
}
