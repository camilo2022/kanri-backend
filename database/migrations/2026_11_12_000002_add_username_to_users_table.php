<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El usuario entra con un nombre de usuario generado (procedimiento generate_username) y el correo pasa a la
     * persona (2026-11-12). En una instalación nueva ya viene así en create_users_table; aquí se pasa la base actual:
     * primero se copia cada correo a su persona y se genera el nombre de usuario, y solo después se quita users.email.
     */
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS generate_username');
        DB::unprepared(file_get_contents(database_path('procedures/generate_username.sql')));

        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 100)->nullable()->unique()->after('employee_id')->comment('Nombre de usuario (generado por generate_username)');
            });
        }

        if (Schema::hasColumn('users', 'email')) {
            DB::statement('
                UPDATE people p
                JOIN employees e ON e.person_id = p.id
                JOIN users u ON u.employee_id = e.id
                SET p.email = u.email
                WHERE p.email IS NULL
            ');
        }

        foreach (DB::table('users')->join('employees', 'employees.id', '=', 'users.employee_id')->whereNull('users.username')->orderBy('users.id')->get(['users.id', 'employees.person_id']) as $user) {
            $username = DB::select('CALL generate_username(?)', [$user->person_id])[0]->username;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable(false)->comment('Nombre de usuario (generado por generate_username)')->change();

            if (Schema::hasColumn('users', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
        });

        Schema::dropIfExists('password_reset_tokens');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->unique()->after('employee_id')->comment('Correo');
                $table->timestamp('email_verified_at')->nullable()->after('email')->comment('Fecha de verificación del correo');
            }
        });

        DB::statement('
            UPDATE users u
            JOIN employees e ON e.id = u.employee_id
            JOIN people p ON p.id = e.person_id
            SET u.email = p.email
        ');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
        });

        DB::unprepared('DROP PROCEDURE IF EXISTS generate_username');
    }
};
