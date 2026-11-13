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
            $table->id()->comment('Identificador');
            $table->foreignIdFor(MovementDetail::class)->comment('Detalle de movimiento')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Size::class)->nullable()->comment('Talla')->constrained()->cascadeOnUpdate();
            $table->unsignedInteger('quantity')->comment('Cantidad');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
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
