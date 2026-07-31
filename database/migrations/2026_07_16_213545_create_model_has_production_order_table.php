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
            $table->morphs('model');
            $table->unsignedInteger('quantity');
            $table->foreignIdFor(ProductionOrder::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('ProductionOrder');
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
