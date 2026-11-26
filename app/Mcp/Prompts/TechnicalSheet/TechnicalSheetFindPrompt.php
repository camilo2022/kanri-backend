<?php

namespace App\Mcp\Prompts\TechnicalSheet;

use App\Mcp\Prompts\RoutePrompt;

class TechnicalSheetFindPrompt extends RoutePrompt
{
    protected string $name = 'technical_sheet_find';

    protected string $title = 'Obtener una ficha técnica';

    protected string $description = 'Obtener una ficha técnica';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/find';

    protected string $business = 'Busca una ficha técnica, que es el documento de diseño y fabricación de una referencia (una por producto). Recibe exactamente UNO de estos parámetros de consulta: id (de la ficha), product_id (del producto) o uuid (código público de la ficha); enviar ninguno o más de uno es un error. Úsala cuando la persona diga «muéstrame la ficha de la referencia BO001» (con el product_id) o ya tenga el id de la ficha. Devuelve la ficha con colección, subgrupo, color, pretina, tipo de prenda, tono de lavado, bota, cotilla, trasero, patronista (con persona y cargo), insumos con su tipo, producto con marca, grupo, tallas, subcategoría y categoría, productos STARA, los detalles por subproceso u operación (con su tipología y estado) y los detalles por proceso, además de los catálogos de estados. El estado «En revision» se escribe sin tilde. No trae los lotes: para eso se consultan las órdenes de producción de la ficha. Una ficha desactivada no se encuentra.';
}
