<?php

use App\Models\Item;
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
        Schema::create('subitems', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->foreignIdFor(Item::class)->comment('Catálogo')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->text('name')->comment('Nombre');
            $table->text('description')->nullable()->comment('Descripción');
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
        Schema::dropIfExists('subitems');
    }
};
