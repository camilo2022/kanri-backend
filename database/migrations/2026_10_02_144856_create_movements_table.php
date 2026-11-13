<?php

use App\Models\ProductionOrderDetail;
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
        Schema::create('movements', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->string('model_type')->comment('Tipo de registro asociado');
            $table->unsignedBigInteger('model_id')->comment('Registro asociado');
            $table->index(['model_type', 'model_id']);
            $table->foreignIdFor(ProductionOrderDetail::class)->comment('Detalle de la orden')->constrained()->cascadeOnUpdate();
            $table->enum('status', ['Ingreso', 'Movimiento', 'Salida'])->default('Ingreso')->comment('Estado');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
