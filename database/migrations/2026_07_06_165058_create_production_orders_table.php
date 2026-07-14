<?php

use App\Models\TechnicalSheet;
use App\Models\Variant;
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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->foreignIdFor(TechnicalSheet::class)->unique()->constrained()->cascadeOnUpdate();
            $table->nullableMorphs('model');
            $table->unsignedInteger('consecutive');
            $table->float('efficiency', 8.3)->nullable();
            $table->string('cut', 2);
            $table->foreignIdFor(Variant::class, 'fabric_id')->nullable()->constrained()->cascadeOnUpdate();
            $table->decimal('width', 8, 2);
            $table->boolean('pocket_fabric')->default(false);
            $table->text('observation')->nullable();
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->enum('status', ['Pendiente', 'Cancelado', 'Aprobado'])->default('Pendiente');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
