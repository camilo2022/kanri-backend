<?php

use App\Models\BloodType;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Gender;
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
        Schema::create('people', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->foreignIdFor(DocumentType::class)->comment('Tipo de documento')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('document')->unique()->comment('Documento');
            $table->string('names')->comment('Nombres');
            $table->string('last_names')->comment('Apellidos');
            $table->foreignIdFor(Gender::class)->comment('Género')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->date('birth_date')->nullable()->comment('Fecha de nacimiento');
            $table->foreignIdFor(BloodType::class)->comment('Tipo de sangre')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('email')->nullable()->unique()->comment('Correo');
            $table->nullableMorphs('location');
            $table->string('address')->nullable()->comment('Dirección');
            $table->string('neighborhood', 100)->nullable()->comment('Barrio');
            $table->foreignIdFor(Country::class, 'phone_country_id')->nullable()->comment('País del teléfono')->constrained('countries')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('phone')->nullable()->comment('Teléfono');
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
        Schema::dropIfExists('people');
    }
};
