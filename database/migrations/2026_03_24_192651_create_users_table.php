<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->foreignIdFor(Employee::class)->unique()->comment('Empleado')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('username', 100)->unique()->comment('Nombre de usuario (generado por generate_username)');
            $table->string('password')->comment('Contraseña');
            $table->rememberToken()->comment('Token de sesión');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
            $table->softDeletes()->comment('Fecha de desactivación');
        });

        DB::unprepared('DROP PROCEDURE IF EXISTS generate_username');
        DB::unprepared(file_get_contents(database_path('procedures/generate_username.sql')));

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS generate_username');
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
