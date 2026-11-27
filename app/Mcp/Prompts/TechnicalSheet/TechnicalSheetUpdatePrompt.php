<?php

namespace App\Mcp\Prompts\TechnicalSheet;

use App\Mcp\Prompts\RoutePrompt;

class TechnicalSheetUpdatePrompt extends RoutePrompt
{
    protected string $name = 'technical_sheet_update';

    protected string $title = 'Editar una ficha técnica';

    protected string $description = 'Editar una ficha técnica';

    protected string $method = 'PUT';

    protected string $path = '/technical_sheets/update/{id}';

    protected string $business = 'Edita una ficha técnica: se envían solo los campos que cambian (por ejemplo, únicamente la colección) y el resto de la ficha queda como está; no hay que enviar fotos ni insumos para cambiar un dato. Lo que se envíe puede ser datos, insumos (se reemplazan por los enviados), fotos nuevas, detalles por proceso y, opcionalmente, quitar subprocesos u operaciones (removed_details). Rechazos: no se puede quitar algo que esté Aprobado, un subproceso automático, ni algo donde algún lote de la ficha ya se trabajó; no se puede agregar un subproceso u operación cuyo proceso o subproceso padre ya está Aprobado; la secuencia debe cumplirse contando lo que queda tras quitar; el código y el producto no pueden repetirse en otra ficha; fecha no posterior a hoy; si se envían insumos, uno por cada tipo requerido; fotos nombradas «código-D» y «código-T»; tablas dinámicas o estáticas con las reglas de sus columnas. Efectos: lo que ya está Aprobado y llega Aprobado se conserva sin cambios; al quitar un subproceso se quitan también sus operaciones; los subprocesos automáticos que ya no aplican se eliminan. Este mismo Request lo usa la edición de transformaciones, por lo que acepta el permiso de fichas o de transformaciones. Hallazgo: la validación de un insumo repetido por tipo no llega a detectar duplicados. Confirmar cambios de estado y lo que se quitará. Lo quitado no se restaura desde la API.';
}
