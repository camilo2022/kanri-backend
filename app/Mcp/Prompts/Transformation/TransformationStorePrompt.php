<?php

namespace App\Mcp\Prompts\Transformation;

use App\Mcp\Prompts\RoutePrompt;

class TransformationStorePrompt extends RoutePrompt
{
    protected string $name = 'transformation_store';

    protected string $title = 'Crear una transformación';

    protected string $description = 'Crear una transformación';

    protected string $method = 'POST';

    protected string $path = '/technical_sheets/transformations/store';

    protected string $business = 'Crea una transformación: a partir de una ficha técnica existente (technical_sheet_id) genera, en una sola acción, un producto nuevo, una ficha técnica nueva ligada a la original (model_id) con sus detalles, fotos e insumos, opcionalmente un producto STARA, y una orden de producción nueva con su corte, estado y curva (máximo 5 detalles). Se rechaza si el código del producto ya existe o no cumple las validaciones de la marca (en transformaciones se escribe sin guion y el código de ficha debe ser el mismo con el guion en su lugar, por ejemplo prefijo-números), si el producto STARA repite códigos o tiene otra subcategoría, si falta un insumo obligatorio, si la secuencia de procesos queda incompleta, si las fotos no se llaman «código-D»/«código-T», o si con reasigned_curve el lote de origen ya se empezó o está programado. Las fotos se suben o se copian de una existente (id). No se encontró validación de que el corte sea consecutivo ni único. Efectos: consecutivos nuevos de ficha y de orden; con reasignación se descuenta curva de las órdenes origen. Confirmar ficha base, códigos y curva. No hay deshacer directo.';
}
