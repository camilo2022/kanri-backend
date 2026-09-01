<?php

use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->enum('production_place', ['BLESS', 'SATELITE'])
                ->default('BLESS')
                ->after('consecutive')
                ->comment('Lugar de producción');

            $table->foreignIdFor(Supplier::class)
                ->nullable()
                ->after('production_place')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'production_place']);
        });
    }
};
