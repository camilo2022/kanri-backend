<?php

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submodules', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->string('name')->unique()->comment('Nombre');
            $table->string('url')->unique()->comment('Dirección');
            $table->string('icon')->comment('Icono');
            $table->foreignIdFor(Module::class)->comment('Módulo')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(Permission::class)->unique()->comment('Permiso')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
            $table->softDeletes()->comment('Fecha de desactivación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submodules');
    }
};
