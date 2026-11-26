<?php

namespace App\Console\Commands;

use App\Mcp\Support\RouteCatalog;
use Illuminate\Console\Command;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;

class MakeMcpPromptsCommand extends Command
{
    protected $signature = 'mcp:make-prompts
        {--only=read : read, write o all}
        {--dry : Solo muestra lo que crearía, sin escribir archivos}
        {--force : Reemplaza los archivos que ya existen}
        {--texts= : Archivo JSON con textos de negocio propios, por «MÉTODO /ruta»}';

    protected $description = 'Crea un prompt del MCP por cada ruta del API, en la carpeta de su recurso';

    public function handle(RouteCatalog $catalog): int
    {
        $only = $this->option('only');

        $entries = $catalog->entries()->filter(fn (array $entry) => match ($only) {
            'read' => $entry['usable'] === 'read_data',
            'write' => $entry['usable'] !== 'read_data',
            default => true,
        });

        $plan = $this->plan($catalog, $entries, $this->customTexts());

        if ($duplicates = $this->duplicatedNames($plan)) {
            $this->error('Hay nombres de prompt repetidos: '.implode(', ', $duplicates));

            return self::FAILURE;
        }

        $this->summary($plan);

        if ($this->option('dry')) {
            $this->info('Simulación: no se escribió ningún archivo.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($plan as $item) {
            $file = app_path("Mcp/Prompts/{$item['entity']}/{$item['class']}.php");

            if (is_file($file) && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            if (! is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }

            file_put_contents($file, $this->render($item));
            $created++;
        }

        $this->writeIndex();

        $this->info("Prompts creados: {$created}. Ya existían y no se tocaron: {$skipped}.");

        return self::SUCCESS;
    }

    private function customTexts(): array
    {
        $file = $this->option('texts');

        if (! $file) {
            return [];
        }

        $texts = json_decode((string) file_get_contents($file), true);

        if (! is_array($texts)) {
            $this->error("No se pudo leer el archivo de textos: {$file}");
            exit(self::FAILURE);
        }

        return $texts;
    }

    private function plan(RouteCatalog $catalog, Collection $entries, array $texts): array
    {
        $items = [];

        foreach ($entries as $entry) {
            $route = $catalog->routeByUri($entry['method'], $entry['uri']);

            if (! $route) {
                continue;
            }

            $entity = $this->entity($route);

            $items[] = [
                'entry' => $entry,
                'route' => $route,
                'entity' => $entity,
                'action' => $this->action($route),
            ];
        }

        $groups = collect($items)->groupBy(fn (array $item) => $item['entity'].'|'.$item['action']);

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            foreach ($group as $item) {
                $key = array_search($item, $items, true);
                $items[$key]['action'] = $this->actionFromUri($item['entry']['uri']);
            }
        }

        $groups = collect($items)->groupBy(fn (array $item) => $item['entity'].'|'.$item['action']);

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            foreach ($group as $item) {
                $key = array_search($item, $items, true);
                $items[$key]['action'] .= Str::studly(strtolower($item['entry']['method']));
            }
        }

        $labels = $this->labels($items);

        return array_map(function (array $item) use ($labels, $texts) {
            $entry = $item['entry'];
            $title = $entry['summary'] ?: Str::headline($item['entity'].' '.$item['action']);
            $key = $entry['method'].' '.$entry['path'];

            return [
                'entity' => $item['entity'],
                'class' => $item['entity'].$item['action'].'Prompt',
                'name' => Str::snake($item['entity']).'_'.Str::snake($item['action']),
                'title' => $title,
                'description' => $title,
                'method' => $entry['method'],
                'path' => $entry['path'],
                'business' => $texts[$key] ?? $this->business($item, $labels[$item['entity']] ?? null, $title),
            ];
        }, $items);
    }

    /**
     * @return array<string, array{plural: string, singular: string}>
     */
    private function labels(array $items): array
    {
        $labels = [];

        foreach ($items as $item) {
            $summary = (string) $item['entry']['summary'];

            if ($item['action'] === 'All' && preg_match('/^Listar (?:los |las |el |la )?(.+)$/u', $summary, $match)) {
                $labels[$item['entity']]['plural'] = $match[1];
            }

            if ($item['action'] === 'Find' && preg_match('/^Obtener (?:un |una |el |la )?(.+)$/u', $summary, $match)) {
                $labels[$item['entity']]['singular'] = $match[1];
            }
        }

        return $labels;
    }

    private function business(array $item, ?array $labels, string $title): string
    {
        $plural = $labels['plural'] ?? Str::lower(Str::headline($item['entity']));
        $singular = $labels['singular'] ?? $plural;
        $notes = $item['entry']['description'];
        $suffix = $notes ? "

Notas: {$notes}" : '';

        return match ($item['action']) {
            'All' => "Devuelve la lista de {$plural} registrados en Kanri. Solo consulta: no cambia nada. Admite buscar, ordenar y paginar con los datos que se listan abajo; si no se envía per_page puede devolver todos los registros, y si son demasiados conviene usar per_page y page. Úsala cuando la persona pida ver, listar o buscar {$plural}, o cuando necesites el id de uno para otra acción.".$suffix,
            'Find' => "Trae un solo registro de {$singular} por su id, con su detalle. Solo consulta: no cambia nada. Úsala cuando la persona pida ver un registro concreto o cuando ya tengas su id, por ejemplo tomado de la lista. Si no existe responde not_found.".$suffix,
            'Audit' => "Muestra la línea de tiempo de un registro de {$singular}: cada creación, cambio, eliminación o restauración, con la persona que lo hizo, la fecha, el origen (web, mobile, mcp, api o system) y los valores que cambiaron. Solo consulta. Úsala cuando la persona pregunte quién cambió algo, cuándo o qué pasó con ese registro.".$suffix,
            'Store' => "Crea un registro nuevo de {$singular}. Los datos que acepta y sus reglas de validación se listan abajo y son las reales de la ruta: si alguno no se cumple se rechaza con type=validation. Úsala cuando la persona pida crear o agregar {$singular}. Antes de ejecutarla muéstrale los datos que vas a enviar y pide su confirmación expresa; si faltan ids de catálogos, búscalos con los permisos de la persona.".$suffix,
            'Update' => "Modifica un registro existente de {$singular}, identificado por su id. Se rechaza si el registro no existe o si los datos no cumplen las reglas que se listan abajo. Úsala cuando la persona pida cambiar o corregir un {$singular}. Primero consulta el registro actual con la ruta de consulta de uno, muéstrale qué cambiaría y pide su confirmación expresa antes de ejecutarla.".$suffix,
            'Delete' => "Elimina un registro de {$singular}, identificado por su id, con borrado lógico: deja de aparecer entre los activos y se puede reactivar con la ruta de restaurar. Úsala solo cuando la persona pida eliminar o desactivar un {$singular}. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa; no la uses por iniciativa propia.".$suffix,
            'Restore' => "Reactiva un registro de {$singular} que estaba eliminado, identificado por su id. Se rechaza si el registro no existe o no está eliminado, o si al reactivarlo repetiría un dato que debe ser único. Úsala cuando la persona pida recuperar o reactivar un {$singular}. Antes de ejecutarla confirma con ella cuál es (nombre e id) y pide su confirmación expresa.".$suffix,
            default => $notes ?: $title,
        };
    }

    private function entity(LaravelRoute $route): string
    {
        $model = $route->defaults['model'] ?? null;

        if ($model) {
            return class_basename($model);
        }

        [$controller] = explode('@', $route->getActionName());

        return Str::before(class_basename($controller), 'Controller');
    }

    private function action(LaravelRoute $route): string
    {
        $method = explode('@', $route->getActionName())[1] ?? '';

        return $method === 'timeline' ? 'Audit' : Str::studly($method);
    }

    private function actionFromUri(string $uri): string
    {
        $segments = array_filter(explode('/', $uri), fn (string $segment) => $segment !== '' && ! str_starts_with($segment, '{'));

        return Str::studly(end($segments));
    }

    private function duplicatedNames(array $plan): array
    {
        return collect($plan)->pluck('name')->duplicates()->unique()->values()->all();
    }

    private function summary(array $plan): void
    {
        $byEntity = collect($plan)->groupBy('entity')->map->count()->sortDesc();

        $this->info('Prompts a crear: '.count($plan).' en '.$byEntity->count().' carpetas.');
        $this->table(['Carpeta', 'Prompts'], $byEntity->map(fn ($count, $entity) => [$entity, $count])->values()->all());
    }

    private function render(array $item): string
    {
        $export = fn (string $value) => var_export($value, true);

        return "<?php\n\n"
            ."namespace App\\Mcp\\Prompts\\{$item['entity']};\n\n"
            ."use App\\Mcp\\Prompts\\RoutePrompt;\n\n"
            ."class {$item['class']} extends RoutePrompt\n{\n"
            .'    protected string $name = '.$export($item['name']).";\n\n"
            .'    protected string $title = '.$export($item['title']).";\n\n"
            .'    protected string $description = '.$export($item['description']).";\n\n"
            .'    protected string $method = '.$export($item['method']).";\n\n"
            .'    protected string $path = '.$export($item['path']).";\n\n"
            .'    protected string $business = '.$export($item['business']).";\n"
            ."}\n";
    }

    private function writeIndex(): void
    {
        $index = [];

        foreach (glob(app_path('Mcp/Prompts/*/*Prompt.php')) as $file) {
            $class = 'App\\Mcp\\Prompts\\'.basename(dirname($file)).'\\'.basename($file, '.php');
            $defaults = (new ReflectionClass($class))->getDefaultProperties();
            $index["{$defaults['method']} {$defaults['path']}"] = $class;
        }

        ksort($index);

        $lines = ["<?php\n", 'return ['];

        foreach ($index as $key => $class) {
            $lines[] = '    '.var_export($key, true).' => \\'.$class.'::class,';
        }

        $lines[] = "];\n";

        file_put_contents(app_path('Mcp/Prompts/prompts.php'), implode("\n", $lines));
    }
}
