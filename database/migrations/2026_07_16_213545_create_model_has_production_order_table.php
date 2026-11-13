<?php

use App\Models\ProductionOrder;
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
        Schema::create('model_has_production_order', function (Blueprint $table) {
            $table->foreignIdFor(ProductionOrder::class)->comment('Orden de producción')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('model_type')->comment('Tipo de registro asociado');
            $table->unsignedBigInteger('model_id')->comment('Registro asociado');
            $table->index(['model_type', 'model_id']);
            $table->unsignedInteger('quantity')->comment('Cantidad');
            $table->unique(['production_order_id', 'model_id', 'model_type'], 'mhpo_poi_mi_mt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_production_order');
    }
};
