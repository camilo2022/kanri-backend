<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailReprocessAllPrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_reprocess_all';

    protected string $title = 'Reprocesos de un registro de trabajo';

    protected string $description = 'Reprocesos de un registro de trabajo';

    protected string $method = 'GET';

    protected string $path = '/productivity/details/reprocess/{id}';

    protected string $business = 'Muestra los reprocesos de un registro de trabajo (el trabajo de un lote en un puesto), es decir las unidades que se devolvieron a un paso anterior, y los pasos del lote en ese proceso a los que se pueden devolver unidades, con lo que lleva hecho cada uno. Úsala con «¿qué reprocesos tiene este trabajo?» o «¿a qué pasos puedo devolver unidades?». El id es entero y corresponde al registro de trabajo, no al turno. Solo aplica a procesos que permiten reprocesos y a trabajos que ya se iniciaron; si el proceso no los permite o el trabajo está solo programado, responde error de validación. No registra ni borra reprocesos, solo los consulta.';
}
