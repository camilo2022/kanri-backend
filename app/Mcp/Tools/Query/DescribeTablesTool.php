<?php

namespace App\Mcp\Tools\Query;

use App\Mcp\ActorResolver;
use App\Mcp\McpFailure;
use App\Mcp\Support\SchemaCatalog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('describe_tables')]
#[Title('Ver las tablas que se pueden consultar')]
#[Description('Dice qué tablas de Kanri puede consultar la persona con query_data y, si pasas table, sus columnas. Los catálogos (bancos, colores, tallas, marcas…) viven todos en la tabla subitems, separados por item_id; aquí se ve cuáles puede leer. Úsala antes de escribir un SQL para no inventar tablas ni columnas. No consulta datos del negocio. Los nombres de tablas, columnas e identificadores son solo para armar la consulta: nunca se los muestres ni se los menciones a la persona; respóndele en lenguaje del negocio.')]
#[IsReadOnly]
#[IsIdempotent]
class DescribeTablesTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            $actor = app(ActorResolver::class)->resolve($request->get('ai_key'))['user'];
        } catch (McpFailure $failure) {
            return $failure->response();
        }

        $schema = app(SchemaCatalog::class);
        $readable = $schema->readableTables($actor);
        $table = Str::lower(trim((string) $request->get('table', '')));

        if ($table === '') {
            return Response::json([
                'ok' => true,
                'tables' => collect($readable)->map(fn (int $rows, string $name) => ['table' => $name, 'approx_rows' => $rows])->values()->all(),
                'catalogs' => collect($schema->allowedCatalogs($actor))->map(fn (string $name, int $id) => ['item_id' => $id, 'name' => $name])->values()->all(),
                'message' => '(Solo para ti: no se lo muestres a la persona.) Estas son las tablas que la persona puede consultar. Los catálogos están en subitems (filtra por item_id; el nombre de cada catálogo está en items). Llama con table para ver las columnas de una.',
            ]);
        }

        if (! isset($readable[$table])) {
            return McpFailure::tableNotAllowed($table, "La tabla «{$table}» no existe, no está habilitada o la persona no tiene permiso para consultarla. Llama sin table para ver las disponibles.")->response();
        }

        $columns = $schema->columns($table);
        $hidden = config("assistant_tables.hidden_columns.{$table}", []);

        return Response::json([
            'ok' => true,
            'table' => $table,
            'approx_rows' => $readable[$table],
            'columns' => $columns,
            'references' => $this->references($columns, $readable),
            'notes' => array_values(array_filter([
                $schema->hasColumn($table, 'deleted_at') ? 'Los registros eliminados (deleted_at) no aparecen salvo include_deleted=true.' : null,
                $table === config('assistant_tables.catalog_table') ? 'Filtra por item_id. Solo aparecen los catálogos que la persona puede leer (los de catalogs al describir sin table).' : null,
                $hidden ? 'Columnas que nunca se leen: '.implode(', ', $hidden).'.' : null,
                $schema->hasColumn($table, 'model_type') ? 'model_type guarda la clase del modelo con una sola barra invertida (por ejemplo App\Models\Supply) y model_id su id. En un SQL escribe la barra doble: model_type = \'App\\\\Models\\\\Supply\'.' : null,
                $table === 'model_has_subitems' ? 'Relaciona registros con catálogos: model_type + model_id es el registro y subitem_id es el catálogo relacionado (por ejemplo el tipo de insumo, el color o la marca). Ejemplo, insumos de un tipo: SELECT s.id, s.name FROM subitems s JOIN model_has_subitems m ON m.model_id = s.id AND m.model_type = \'App\\\\Models\\\\Supply\' WHERE m.subitem_id = <id del tipo> AND s.item_id = 29. Algunas filas antiguas pueden traer model_type con la barra duplicada.' : null,
                $schema->hasColumn($table, 'settings') ? 'settings es JSON: lee un campo con JSON_UNQUOTE(JSON_EXTRACT(settings, \'$.values.campo\')) (por ejemplo los datos de cada insumo están en settings.values).' : null,
            ])),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'table' => $schema->string()->description('Nombre de una tabla para ver sus columnas. Sin él lista las tablas disponibles.'),
            'ai_key' => $schema->string()->description('Llave de IA de la persona. Opcional.'),
        ];
    }

    /**
     * Columnas que apuntan a otra tabla por su nombre (product_id → products), para armar los JOIN.
     *
     * @param  array<int, array{name: string}>  $columns
     * @param  array<string, int>  $readable
     * @return array<string, string>
     */
    private function references(array $columns, array $readable): array
    {
        $references = [];

        foreach ($columns as $column) {
            if (! str_ends_with($column['name'], '_id') || $column['name'] === 'model_id') {
                continue;
            }

            $base = substr($column['name'], 0, -3);
            $guess = collect([Str::plural($base), $base])->first(fn (string $table) => isset($readable[$table]));

            if ($guess) {
                $references[$column['name']] = $guess.'.id';
            } elseif (isset($readable['subitems'])) {
                $references[$column['name']] = 'subitems.id (si es un catálogo; filtra por item_id)';
            }
        }

        return $references;
    }
}
