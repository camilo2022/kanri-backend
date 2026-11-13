<?php

use App\Models\TechnicalSheet;
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
        Schema::create('technical_sheet_details', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->foreignIdFor(TechnicalSheet::class)->comment('Ficha técnica')->constrained()->cascadeOnUpdate();
            $table->string('model_type')->comment('Tipo de registro asociado');
            $table->unsignedBigInteger('model_id')->comment('Registro asociado');
            $table->index(['model_type', 'model_id']);
            $table->enum('status', ['Pendiente', 'En revision', 'Aprobado'])->default('En revision')->comment('Estado');
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
            $table->unique(
                ['technical_sheet_id', 'model_type', 'model_id'],
                'tsd_tsi_mt_mi_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_sheet_details');
    }
};
