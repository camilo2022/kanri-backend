<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckAssistantReadonlyCommand extends Command
{
    protected $signature = 'mcp:readonly-check';

    protected $description = 'Comprueba que el usuario de base de datos de solo lectura del asistente está bien configurado y protegido (no muestra contraseñas)';

    private int $failures = 0;

    public function handle(): int
    {
        $name = config('assistant.sql_connection');
        $username = config("database.connections.{$name}.username");

        if (! $username) {
            $this->error('Falta DB_ASSISTANT_USERNAME (y DB_ASSISTANT_PASSWORD) en el .env. Si cambiaste el .env, corre php artisan optimize:clear.');

            return self::FAILURE;
        }

        $this->line("Conexión del asistente: usuario «{$username}» en la base «".config("database.connections.{$name}.database").'».');

        $app_user = config('database.connections.'.config('database.default').'.username');
        $this->report('es un usuario distinto al de la aplicación', $username !== $app_user);

        try {
            $connection = DB::connection($name);
            $connection->getPdo();
        } catch (Throwable $e) {
            $this->report('se conecta a la base de datos', false, 'no se pudo conectar: revisa la contraseña, el host del usuario (localhost, 127.0.0.1 o %) y corre php artisan optimize:clear');

            return self::FAILURE;
        }

        $this->report('se conecta a la base de datos', true);

        $tables = array_merge(
            array_keys(config('assistant_tables.tables', [])),
            [config('assistant_tables.catalog_table'), config('assistant_tables.catalog_items_table')]
        );

        $missing = [];

        foreach ($tables as $table) {
            try {
                $connection->select("SELECT 1 FROM `{$table}` LIMIT 1");
            } catch (Throwable $e) {
                $missing[] = $table;
            }
        }

        $this->report('puede leer las '.count($tables).' tablas habilitadas', ! $missing, $missing ? 'sin acceso a: '.implode(', ', $missing).' (vuelve a correr los GRANT)' : null);

        $open = [];

        foreach (config('assistant_tables.forbidden', []) as $table) {
            try {
                $connection->select("SELECT 1 FROM `{$table}` LIMIT 1");
                $open[] = $table;
            } catch (Throwable $e) {
                // lo esperado: sin acceso
            }
        }

        $this->report('NO puede leer las tablas prohibidas (tokens, OAuth, sesiones, cachés, jobs, migraciones, cuentas bancarias)', ! $open, $open ? 'puede leer: '.implode(', ', $open) : null);

        foreach (config('assistant_tables.hidden_columns', []) as $table => $columns) {
            foreach ($columns as $column) {
                try {
                    $connection->select("SELECT `{$column}` FROM `{$table}` LIMIT 1");
                    $this->report("NO puede leer la columna {$table}.{$column}", false, 'puede leerla');
                } catch (Throwable $e) {
                    $this->report("NO puede leer la columna {$table}.{$column}", true);
                }
            }
        }

        $writes = [
            'INSERT' => "INSERT INTO `subitems` (`item_id`, `name`) VALUES (0, 'prueba-solo-lectura')",
            'UPDATE' => 'UPDATE `subitems` SET `name` = `name` WHERE `id` = 0',
            'DELETE' => 'DELETE FROM `subitems` WHERE `id` = 0',
        ];

        $written = [];

        foreach ($writes as $kind => $sql) {
            try {
                $connection->unprepared($sql);
                $written[] = $kind;
            } catch (Throwable $e) {
                // lo esperado: rechazado
            }
        }

        $this->report('NO puede escribir (INSERT, UPDATE, DELETE)', ! $written, $written ? 'pudo ejecutar: '.implode(', ', $written).'. Revisa los permisos del usuario' : null);

        if ($written) {
            DB::table('subitems')->where('item_id', 0)->where('name', 'prueba-solo-lectura')->delete();
        }

        $this->newLine();

        if ($this->failures) {
            $this->error("Hay {$this->failures} problema(s). Corrígelos antes de usar el asistente.");

            return self::FAILURE;
        }

        $this->info('Todo bien: el asistente consulta con un usuario que solo puede leer las tablas habilitadas.');

        return self::SUCCESS;
    }

    private function report(string $what, bool $ok, ?string $detail = null): void
    {
        if (! $ok) {
            $this->failures++;
        }

        $this->line(($ok ? '<info>  OK  </info>' : '<error> FALLA </error>').' '.$what.(! $ok && $detail ? " — {$detail}" : ''));
    }
}
