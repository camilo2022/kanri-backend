<?php

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Collection;
use App\Models\Color;
use App\Models\GarmentType;
use App\Models\Group;
use App\Models\Product;
use App\Models\Silhouette;
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
            $table->date('date');
            $table->foreignIdFor(Product::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Collection::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Silhouette::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Group::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Subgroup::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(WashTone::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Color::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(WaistbandType::class)->constrained()->cascadeOnUpdate();
            $table->unsignedBigInteger('measure_of_waistband')->default(0);
            $table->foreignIdFor(BootType::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(YokeType::class)->constrained()->cascadeOnUpdate();
            $table->boolean('yoke')->default(0);
            $table->foreignIdFor(BackType::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(GarmentType::class)->constrained()->cascadeOnUpdate();
            $table->boolean('physical_sample')->default(0);
            $table->unsignedBigInteger('number_of_buttons')->default(0);
            $table->text('description');
            $table->text('observation')->nullable();
            $table->text('specification')->nullable();
            $table->enum('status', ['Pendiente', 'En revision', 'Aprobado', 'Cancelado']);
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
