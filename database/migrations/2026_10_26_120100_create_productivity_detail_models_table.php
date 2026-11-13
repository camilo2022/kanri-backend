<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lo asociado a un registro de trabajo: empleados (Employee) y paradas programadas y no programadas (Stop, con
     * su tipo y horas en settings). Sin llave única: la misma parada puede ocurrir varias veces.
     */
    public function up(): void
    {
        Schema::create('productivity_detail_models', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->foreignId('productivity_detail_id')->comment('Registro de trabajo')->constrained('productivity_details')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('model_type')->comment('Tipo de participante'); // Employee o Stop
            $table->unsignedBigInteger('model_id')->comment('Empleado o parada');
            $table->index(['model_type', 'model_id']);
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productivity_detail_models');
    }
};
