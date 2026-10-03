<?php

use App\Models\Product;
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
        Schema::create('model_has_product', function (Blueprint $table) {
            $table->morphs('model');
            $table->foreignIdFor(Product::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Product');
            $table->unique(['product_id', 'model_id', 'model_type'], 'mhp_pi_mi_mt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_product');
    }
};
