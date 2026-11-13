<?php

use App\Models\Region;
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
        Schema::create('countries', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->unsignedInteger('source_id')->unique()->comment('Identificador en el dataset de ubicaciones');
            $table->foreignIdFor(Region::class)->nullable()->comment('Región')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 70)->comment('Nombre');
            $table->string('iso2', 2)->unique()->comment('Código ISO de 2 letras');
            $table->string('iso3', 3)->unique()->comment('Código ISO de 3 letras');
            $table->string('phone_code', 10)->comment('Indicativo telefónico');
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
        Schema::dropIfExists('countries');
    }
};
