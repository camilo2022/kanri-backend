<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productivity', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->string('model_type')->comment('Tipo de proceso');
            $table->unsignedBigInteger('model_id')->comment('Proceso');
            $table->index(['model_type', 'model_id']);
            $table->date('date')->comment('Fecha');
            $table->time('start_time')->comment('Hora de inicio');
            $table->time('end_time')->comment('Hora de fin');
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
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
        Schema::dropIfExists('productivity');
    }
};
