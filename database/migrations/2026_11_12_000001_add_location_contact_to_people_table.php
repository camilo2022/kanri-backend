<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubicación (morph al último nivel elegido), barrio, correo y país del teléfono en personas (2026-11-12).
     * En una instalación nueva ya vienen en create_people_table; aquí solo se agregan si faltan.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            if (! Schema::hasColumn('people', 'email')) {
                $table->string('email')->nullable()->unique()->after('last_names')->comment('Correo');
            }
            if (! Schema::hasColumn('people', 'location_type')) {
                $table->nullableMorphs('location', after: 'email');
            }
            if (! Schema::hasColumn('people', 'neighborhood')) {
                $table->string('neighborhood', 100)->nullable()->after('address')->comment('Barrio');
            }
            if (! Schema::hasColumn('people', 'phone_country_id')) {
                $table->foreignIdFor(Country::class, 'phone_country_id')->nullable()->after('neighborhood')->comment('País del teléfono')->constrained('countries')->cascadeOnUpdate()->restrictOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            if (Schema::hasColumn('people', 'phone_country_id')) {
                $table->dropConstrainedForeignId('phone_country_id');
            }
            if (Schema::hasColumn('people', 'neighborhood')) {
                $table->dropColumn('neighborhood');
            }
            if (Schema::hasColumn('people', 'location_type')) {
                $table->dropMorphs('location');
            }
            if (Schema::hasColumn('people', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }
        });
    }
};
