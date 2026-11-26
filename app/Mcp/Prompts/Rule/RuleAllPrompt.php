<?php

namespace App\Mcp\Prompts\Rule;

use App\Mcp\Prompts\RoutePrompt;

class RuleAllPrompt extends RoutePrompt
{
    protected string $name = 'rule_all';

    protected string $title = 'Listar las reglas de estandar';

    protected string $description = 'Listar las reglas de estandar';

    protected string $method = 'GET';

    protected string $path = '/rules/all';

    protected string $business = 'Lista las reglas estándar, que definen cuánto tarda (y con cuántas personas) un proceso, subproceso u operación según la combinación de elementos a los que se ligan. Cada regla trae su nombre, descripción, configuración (las capacidades: personas, tiempo y unidad, minutos o segundos) y los elementos con los que está relacionada, junto con el catálogo de tipos de modelo. Úsala cuando la persona pregunte «¿cuánto demora esta operación con tres personas?» o «¿qué reglas hay para el proceso X?». Admite búsqueda, orden y paginación; sin per_page devuelve todas, y with_trashed es obligatorio (verdadero incluye las desactivadas). Por regla de negocio no puede haber dos reglas activas con la misma combinación. No calcula tiempos de un lote: solo describe las reglas.';
}
