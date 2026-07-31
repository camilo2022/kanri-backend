<?php

use App\Models\ProductionOrderDetail;
use App\Models\Size;
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
        Schema::create('production_order_detail_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_detail_id');
            $table->foreign('production_order_detail_id', 'fk_prod_order_detail_qty')->references('id')->on('production_order_details')->cascadeOnUpdate();
            $table->foreignIdFor(Size::class)->constrained()->cascadeOnUpdate();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_detail_quantities');
    }
};
