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
     */
    public function up(): void
    {
        Schema::create('production_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProductionOrder::class)->constrained()->cascadeOnUpdate();
            $table->morphs('model');
            $table->enum('destination', ['NACIONAL', 'MEDELLIN', 'STARA'])->nullable();
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->timestamps();
            $table->unique(['production_order_id', 'model_type', 'model_id', 'destination'], 'pod_poi_mt_mi_dest_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_details');
    }
};
