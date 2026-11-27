<?php

use App\Models\RiskManager;
use App\Models\CompensationFund;
use App\Models\HealthEntity;
use App\Models\PensionFund;
use App\Models\Person;
use App\Models\Position;
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
        Schema::create('employees', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->foreignIdFor(Person::class)->unique()->comment('Persona')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(Position::class)->comment('Cargo')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(RiskManager::class)->comment('Administradora de riesgos')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(HealthEntity::class)->comment('Entidad de salud')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(PensionFund::class)->comment('Fondo de pensión')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(CompensationFund::class)->comment('Caja de compensación')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('workstation_id')->nullable()->comment('Puesto de trabajo')->constrained('subitems')->onUpdate('cascade')->nullOnDelete();
            $table->date('start_date')->comment('Fecha de ingreso');
            $table->date('end_date')->nullable()->comment('Fecha de retiro');
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
        Schema::dropIfExists('employees');
    }
};
