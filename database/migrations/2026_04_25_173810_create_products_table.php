<?php

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Trademark;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->string('code')->unique()->comment('Código');
            $table->foreignIdFor(Trademark::class)->comment('Marca')->constrained()->onUpdate('cascade');
            $table->foreignIdFor(Subcategory::class)->comment('Subcategoría')->constrained()->onUpdate('cascade');
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
        Schema::dropIfExists('products');
    }
};
