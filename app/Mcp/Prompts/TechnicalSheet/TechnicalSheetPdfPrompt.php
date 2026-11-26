<?php

namespace App\Mcp\Prompts\TechnicalSheet;

use App\Mcp\Prompts\RoutePrompt;

class TechnicalSheetPdfPrompt extends RoutePrompt
{
    protected string $name = 'technical_sheet_pdf';

    protected string $title = 'Pedir el PDF de la ficha técnica de un lote';

    protected string $description = 'Pedir el PDF de la ficha técnica de un lote';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/download_pdf/{uuid}';

    protected string $business = 'Prepara el PDF de la ficha técnica de un lote (la ficha con la curva, la tela, los rollos y los destinos de esa orden de producción). No entrega el archivo sino una dirección temporal firmada que vence a los 5 minutos y que termina en el nombre TS seguido de la referencia y el lote; al abrir esa dirección se descarga el PDF. Úsala cuando la persona pida «el PDF de la ficha del lote BO001-A» o «imprímeme la ficha técnica de ese lote». Ojo: el uuid que recibe es el de la orden de producción (el lote), no el de la ficha técnica. Para la ficha de procesos sin lote existe otra ruta. Es una consulta: no modifica la ficha ni el lote.';
}
