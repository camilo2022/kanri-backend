<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailTechnicalSheetPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_technical_sheet';

    protected string $title = 'Productividad de una referencia';

    protected string $description = 'Productividad de una referencia';

    protected string $method = 'GET';

    protected string $path = '/productivity/details/technical_sheet/{technical_sheet_id}';

    protected string $business = 'Trae la productividad de una referencia: los registros de trabajo ya iniciados de todos los lotes (órdenes de producción) de una ficha técnica, de todos los turnos y procesos, ordenados por hora de inicio. Cada registro viene con su turno y su proceso, el puesto, el lote, la regla estándar, los empleados, los paros y los traslados con sus cantidades por talla. Sirve para preguntas como «¿cuánto tardó la referencia X en cada proceso?» o «¿cómo le fue a esta ficha en producción?». El id de la ficha es entero y debe existir sin estar eliminada. Los tiempos y la eficiencia por lote y proceso los calcula quien muestra la pantalla; aquí vienen los registros tal como están guardados. Se puede paginar con per_page. Solo consulta; no incluye registros solo programados.';
}
