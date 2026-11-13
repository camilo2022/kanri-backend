<?php

use App\Models\Movement;
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
        Schema::create('movement_details', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->foreignIdFor(Movement::class)->comment('Movimiento')->constrained()->cascadeOnUpdate();
            $table->string('model_type')->nullable()->comment('Tipo de registro asociado');
            $table->unsignedBigInteger('model_id')->nullable()->comment('Registro asociado');
            $table->index(['model_type', 'model_id']);
            $table->enum('status', ['Pendiente', 'Aprobado', 'Rechazado', 'Cancelado'])->default('Pendiente')->comment('Estado');
            $table->text('observation')->nullable()->comment('Observación');
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->timestamp('created_at')->useCurrent()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->default(null)->useCurrentOnUpdate()->comment('Fecha de actualización');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_details');
    }
};
