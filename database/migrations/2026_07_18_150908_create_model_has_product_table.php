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
            $table->string('model_type')->comment('Tipo de registro asociado');
            $table->unsignedBigInteger('model_id')->comment('Registro asociado');
            $table->index(['model_type', 'model_id']);
            $table->foreignIdFor(Product::class)->comment('Producto')->constrained()->onUpdate('cascade')->onDelete('cascade');
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
