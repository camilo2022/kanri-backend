<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Carga o actualiza continentes, regiones, países, departamentos y ciudades desde el dataset abierto
 * «countries-states-cities» (licencia ODbL). Los JSON van en storage/app/private/locations (fuera de public).
 *
 * Es repetible: cada registro se busca por source_id (el id del dataset), así que una versión nueva del dataset
 * actualiza nombres y agrega lo que falte sin duplicar. Nunca toca countries.settings (las validaciones de teléfono
 * se configuran en Kanri). Las ciudades solo se cargan de los países indicados (por defecto Colombia).
 */
class ImportLocations extends Command
{
    protected $signature = 'locations:import
        {--country=* : Código ISO2 de los países cuyas ciudades se cargan (por defecto CO)}
        {--path= : Carpeta de los JSON (por defecto storage/app/private/locations)}';

    protected $description = 'Carga o actualiza países, departamentos y ciudades desde el dataset de ubicaciones';

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');

        $path = rtrim($this->option('path') ?: storage_path('app/private/locations'), '/\\');
        $countries = array_map('strtoupper', $this->option('country') ?: ['CO']);

        foreach (['continents', 'regions', 'countries', 'departments', 'cities'] as $file) {
            if (! is_file("$path/$file.json")) {
                $this->error("Falta el archivo $path/$file.json");

                return self::FAILURE;
            }
        }

        $continents = $this->sync('continents', $this->read($path, 'continents'), fn ($row) => [
            'name' => $this->name($row['translations']['es'] ?? $row['name']),
        ]);

        $regions = $this->sync('regions', $this->read($path, 'regions'), fn ($row) => [
            'continent_id' => $continents[$row['region_id']],
            'name' => $this->name($row['translations']['es'] ?? $row['name']),
        ]);

        $country_rows = $this->read($path, 'countries');
        $country_ids = $this->sync('countries', $country_rows, fn ($row) => [
            'region_id' => $regions[$row['subregion_id']] ?? null,
            'name' => $this->name($row['translations']['es'] ?? $row['name']),
            'iso2' => $row['iso2'],
            'iso3' => $row['iso3'],
            'phone_code' => ltrim((string) $row['phonecode'], '+'),
        ]);

        $departments = $this->sync('departments', $this->read($path, 'departments'), fn ($row) => [
            'country_id' => $country_ids[$row['country_id']],
            'name' => $this->name($row['name']),
            'state_code' => $row['state_code'] ?: null,
            'type' => $row['type'] ?: null,
        ]);

        $source_countries = collect($country_rows)->whereIn('iso2', $countries)->pluck('id')->all();
        $cities = collect($this->read($path, 'cities'))
            ->filter(fn ($row) => in_array($row['country_id'], $source_countries, true) && isset($departments[$row['state_id']]))
            ->values()
            ->all();

        $this->sync('cities', $cities, fn ($row) => [
            'department_id' => $departments[$row['state_id']],
            'name' => $this->name($row['name']),
        ]);

        $this->info('Ciudades cargadas de: '.implode(', ', $countries).'.');

        return self::SUCCESS;
    }

    private function read(string $path, string $file): array
    {
        return json_decode(file_get_contents("$path/$file.json"), true, 512, JSON_THROW_ON_ERROR);
    }

    private function name(string $name): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($name)), 'UTF-8');
    }

    /**
     * Inserta o actualiza por source_id y devuelve el mapa source_id => id de Kanri.
     */
    private function sync(string $table, array $rows, callable $columns): array
    {
        $now = now();

        foreach (array_chunk($rows, 1000) as $chunk) {
            $values = array_map(fn ($row) => ['source_id' => $row['id']] + $columns($row) + ['created_at' => $now, 'updated_at' => $now], $chunk);
            $update = array_values(array_diff(array_keys($values[0]), ['source_id', 'created_at']));

            DB::table($table)->upsert($values, ['source_id'], $update);
        }

        $this->line("$table: ".count($rows));

        return DB::table($table)->pluck('id', 'source_id')->all();
    }
}
