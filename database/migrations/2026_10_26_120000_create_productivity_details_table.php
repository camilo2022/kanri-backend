<?php

use App\Models\ProductionOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Registros de trabajo: un lote (orden de producción) trabajado en un puesto (proceso, subproceso u operación)
     * dentro de un turno. Sin `end` el registro está en curso.
     */
    public function up(): void
    {
        Schema::create('productivity_details', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->foreignId('productivity_id')->comment('Turno')->constrained('productivity')->cascadeOnUpdate();
            $table->string('model_type')->comment('Tipo de puesto de trabajo'); // Puesto: proceso, subproceso u operación
            $table->unsignedBigInteger('model_id')->comment('Puesto de trabajo');
            $table->index(['model_type', 'model_id']);
            $table->foreignIdFor(ProductionOrder::class)->comment('Lote trabajado')->constrained()->cascadeOnUpdate();
            $table->foreignId('rule_id')->comment('Regla estándar aplicada')->constrained('subitems')->cascadeOnUpdate();
            $table->dateTime('start')->comment('Inicio oficial');
            $table->dateTime('end')->nullable()->comment('Cierre oficial');
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
        Schema::dropIfExists('productivity_details');
    }
};
