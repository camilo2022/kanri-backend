<?php

namespace App\Mcp\Prompts\ProductivityDetail;

use App\Mcp\Prompts\RoutePrompt;

class ProductivityDetailReprocessStorePrompt extends RoutePrompt
{
    protected string $name = 'productivity_detail_reprocess_store';

    protected string $title = 'Registrar un reproceso';

    protected string $description = 'Registrar un reproceso';

    protected string $method = 'POST';

    protected string $path = '/productivity/details/reprocess/{id}';

    protected string $business = 'Registra un reproceso desde un trabajo ya iniciado (revisión, secado, etc.): N unidades del lote vuelven a unos pasos para hacerse otra vez. Se rechaza si el proceso no permite reprocesos (settings.reprocesses, Lavandería), si el trabajo aún no se inició, si algún paso no es del lote en ese proceso o si ese paso lleva menos unidades hechas que las que se devuelven. Efecto: añade a settings.reprocesses del registro un elemento con unidades, pasos, nota, usuario y hora; el paso afectado descuenta esas unidades de lo producido (desde el último ingreso hacia atrás) y debe volver a hacerlas con +1 o al finalizar. La IA debe confirmar unidades, pasos y motivo. Reversible con la ruta de anular reproceso.';
}
