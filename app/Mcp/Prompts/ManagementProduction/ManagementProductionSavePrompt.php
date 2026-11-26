<?php

namespace App\Mcp\Prompts\ManagementProduction;

use App\Mcp\Prompts\RoutePrompt;

class ManagementProductionSavePrompt extends RoutePrompt
{
    protected string $name = 'management_production_save';

    protected string $title = 'Crear o actualizar una orden de producción';

    protected string $description = 'Crear o actualizar una orden de producción';

    protected string $method = 'POST';

    protected string $path = '/management/production/save';

    protected string $business = 'Pantalla de Gestión de producción: crea o actualiza una orden de producción (lote) con su color, curva por destino, tela, procesos y fechas de programación. Con id edita; sin id crea con consecutivo y uuid nuevos. Al CREAR, el corte debe ser consecutivo respecto del último de la ficha (A para el primero, luego B, C…); al editar no se revisa. Debe haber una tela (Supply) y al menos una curva (Product) con destino NACIONAL, MEDELLIN o STARA; el producto debe corresponder al destino (la ficha para NACIONAL/MEDELLIN, uno de sus productos STARA para STARA). El color debe estar asociado a la tela. Si el lugar de producción es SATELITE se exige proveedor. Fechas de proceso: el proceso debe estar «En revisión» o «Aprobado» en la ficha, la fecha no puede haber pasado, hoy solo antes de terminar el turno, no puede ser anterior a la de un proceso previo ni posterior a la de uno siguiente, y no se cambia si el lote ya se trabajó allí. Con reasigned_curve se descuentan unidades por talla de otras órdenes de origen (que no deben haberse empezado ni estar programadas). Hallazgo: un destino con curva en cero no se actualiza. Confirmar corte, curva, tela, color y orígenes. No hay deshacer directo.';
}
