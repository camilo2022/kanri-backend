<?php

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Employee;
use App\Models\GarmentType;
use App\Models\Product;
use App\Models\Subgroup;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
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
        Schema::create('technical_sheets', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->nullableMorphs('model');
            $table->string('code')->nullable();
            $table->date('date')->nullable();
            $table->foreignIdFor(Product::class)->unique()->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Collection::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(GarmentType::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Subgroup::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(WashTone::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Color::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(WaistbandType::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->unsignedBigInteger('measure_of_waistband')->default(0);
            $table->foreignIdFor(BootType::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(YokeType::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(BackType::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Employee::class, 'pattern_maker_id')->nullable()->constrained()->cascadeOnUpdate();
            $table->boolean('physical_sample')->default(0);
            $table->unsignedBigInteger('number_of_buttons')->default(0);
            $table->text('description')->nullable();
            $table->text('observation')->nullable();
            $table->enum('status', ['Pendiente', 'En revision', 'Aprobado', 'Cancelado'])->default('Pendiente');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_sheets');
    }
};
