<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MakeAssistantReadonlyUserCommand extends Command
{
    protected $signature = 'mcp:readonly-user
        {--user=kanri_readonly : Nombre del usuario de base de datos}
        {--host=localhost : Desde dónde se conecta (localhost, 127.0.0.1 o %)}
        {--password= : Contraseña; si no se da, se genera una}
        {--execute : Crea el usuario y sus permisos en esta base (si no, solo muestra el SQL)}
        {--grants-only : Solo los permisos (quita los de toda la base y da SELECT tabla por tabla); no crea el usuario ni toca su contraseña. Sirve para corregir un usuario creado en cPanel}
        {--reveal : En la simulación, muestra la contraseña en el SQL (para copiarlo y correrlo en otra herramienta, como phpMyAdmin)}';

    protected $description = 'Crea el usuario de base de datos de solo lectura del asistente, con SELECT solo en las tablas habilitadas';

    public function handle(): int
    {
        $database = DB::getDatabaseName();
        $user = $this->option('user');
        $host = $this->option('host');
        $generated = $this->option('password') === null || $this->option('password') === '';
        $password = $generated ? Str::password(32, symbols: false) : $this->option('password');

        foreach (['user' => $user, 'host' => $host] as $name => $value) {
            if (! preg_match('/^[A-Za-z0-9_.%\-]+$/', $value)) {
                $this->error("El valor de --{$name} no es válido.");

                return self::FAILURE;
            }
        }

        $account = "'{$user}'@'{$host}'";
        $quoted = DB::getPdo()->quote($password);
        $grants_only = (bool) $this->option('grants-only');

        $statements = $grants_only
            ? ["REVOKE ALL PRIVILEGES ON `{$database}`.* FROM {$account}"]
            : [
                "CREATE USER IF NOT EXISTS {$account} IDENTIFIED BY {$quoted}",
                "ALTER USER {$account} IDENTIFIED BY {$quoted}",
                "REVOKE ALL PRIVILEGES, GRANT OPTION FROM {$account}",
            ];

        $tables = array_merge(
            array_keys(config('assistant_tables.tables', [])),
            [config('assistant_tables.catalog_table'), config('assistant_tables.catalog_items_table')]
        );

        sort($tables);

        foreach ($tables as $table) {
            $hidden = config("assistant_tables.hidden_columns.{$table}", []);

            if ($hidden) {
                $columns = collect(DB::select(
                    'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
                    [$database, $table]
                ))->pluck('name')->reject(fn ($column) => in_array($column, $hidden, true))->map(fn ($column) => "`{$column}`")->implode(', ');

                $statements[] = "GRANT SELECT ({$columns}) ON `{$database}`.`{$table}` TO {$account}";

                continue;
            }

            $statements[] = "GRANT SELECT ON `{$database}`.`{$table}` TO {$account}";
        }

        $statements[] = 'FLUSH PRIVILEGES';

        $this->info("Usuario {$account} sobre la base {$database}: SELECT en ".count($tables).' tablas (nunca tokens, OAuth, sesiones, cachés, jobs, migraciones ni cuentas bancarias; sin las columnas ocultas).');

        if (! $this->option('execute')) {
            $this->line('Simulación: no se ejecutó nada. Este es el SQL'.($grants_only || $this->option('reveal') ? ':' : ' (la contraseña se oculta; usa --reveal para verla):'));

            foreach ($statements as $statement) {
                $this->line($this->option('reveal') ? $statement.';' : str_replace($quoted, "'********'", $statement).';');
            }

            return self::SUCCESS;
        }

        foreach ($statements as $statement) {
            DB::unprepared($statement);
        }

        $this->info($grants_only ? 'Listo. Permisos aplicados.' : 'Listo. Usuario y permisos creados.');

        if ($generated && ! $grants_only) {
            $this->warn("Contraseña generada (guárdala en DB_ASSISTANT_PASSWORD del .env; no se vuelve a mostrar): {$password}");
        }

        if (! $grants_only) {
            $this->line("En el .env: DB_ASSISTANT_USERNAME={$user}");
        }

        return self::SUCCESS;
    }
}
