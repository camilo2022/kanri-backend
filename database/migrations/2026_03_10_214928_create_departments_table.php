<?php

use App\Models\Country;
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
        Schema::create('departments', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->unsignedInteger('source_id')->unique()->comment('Identificador en el dataset de ubicaciones');
            $table->foreignIdFor(Country::class)->comment('País')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 100)->comment('Nombre');
            $table->string('state_code', 10)->nullable()->comment('Código del departamento en el dataset');
            $table->string('type', 50)->nullable()->comment('Tipo (departamento, distrito, estado, provincia…)');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
            $table->index(['country_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
