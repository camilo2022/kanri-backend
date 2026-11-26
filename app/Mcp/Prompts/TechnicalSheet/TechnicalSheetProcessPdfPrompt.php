<?php

namespace App\Mcp\Prompts\TechnicalSheet;

use App\Mcp\Prompts\RoutePrompt;

class TechnicalSheetProcessPdfPrompt extends RoutePrompt
{
    protected string $name = 'technical_sheet_process_pdf';

    protected string $title = 'Pedir el PDF de la ficha de procesos';

    protected string $description = 'Pedir el PDF de la ficha de procesos';

    protected string $method = 'GET';

    protected string $path = '/technical_sheets/download_process_pdf/{uuid}';

    protected string $business = 'Prepara el PDF de la ficha de procesos de una ficha técnica: la ficha sin lote, sin curva y sin destinos, con sus procesos, subprocesos y operaciones, y al final la línea de trabajo por la que pasa la referencia. No entrega el archivo sino una dirección temporal firmada que vence a los 5 minutos y termina en el nombre WS seguido de la referencia. Úsala cuando la persona pida «la ficha de procesos de la referencia X» o «el PDF de operaciones de esta ficha». Aquí el uuid es el de la ficha técnica (no el de un lote), y la ficha no debe estar desactivada. Para el PDF de la ficha de un lote concreto se usa la otra ruta de PDF. No modifica nada.';
}
