<?php

use App\Models\Continent;
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
        Schema::create('regions', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->unsignedInteger('source_id')->unique()->comment('Identificador en el dataset de ubicaciones');
            $table->foreignIdFor(Continent::class)->comment('Continente')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 70)->comment('Nombre');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
