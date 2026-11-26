<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailReferencePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_reference';

    protected string $title = 'Resolver la ficha técnica escaneada en un puesto de trabajo';

    protected string $description = 'Resolver la ficha técnica escaneada en un puesto de trabajo';

    protected string $method = 'GET';

    protected string $path = '/productivity/details/workstation/{token}/reference/{uuid}';

    protected string $business = 'Resuelve la ficha técnica escaneada en un puesto: recibe el token del puesto (uuid de settings.uuid) y el uuid de la orden de producción que sale del QR de la ficha impresa. Valida el puesto y el turno abierto, y que el proceso del puesto esté en revisión o aprobado en la ficha de esa referencia; si no, o si la orden no pertenece a la ruta de ese puesto, responde error de validación con el motivo. Devuelve la orden (código y lote), la referencia y la regla estándar que aplica: la del proceso del puesto que coincide con su subproceso u operación y con todos sus maestros, y que suma más puntos según los pesos configurados. Si ninguna aplica, la regla viene en null. Úsala para «¿qué regla aplica a este lote en este puesto?». Solo consulta; no inicia el trabajo.';
}
