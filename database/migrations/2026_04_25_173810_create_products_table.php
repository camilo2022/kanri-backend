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
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(Trademark::class)->constrained()->onUpdate('cascade')->comment('Marca');
            $table->foreignIdFor(Category::class)->constrained()->onUpdate('cascade')->comment('Item');
            $table->foreignIdFor(Subcategory::class)->constrained()->onUpdate('cascade')->comment('Item');
            $table->timestamps();
            $table->softDeletes();
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
