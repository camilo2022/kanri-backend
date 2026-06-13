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
            $table->id();
            $table->foreignIdFor(TechnicalSheet::class)->constrained()->cascadeOnUpdate();
            $table->morphs('model');
            $table->enum('status', ['Pendiente', 'En revision', 'Aprobado'])->default('En revision');
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->timestamps();
            $table->unique(
                ['technical_sheet_id', 'model_type', 'model_id'],
                'tsd_unique_model_idx'
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
