<?php

use App\Models\MovementDetail;
use App\Models\Size;
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
        Schema::create('movement_detail_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MovementDetail::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Size::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_detail_quantities');
    }
};
