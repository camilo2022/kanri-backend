<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'operation_center')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('operation_center');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'operation_center')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('operation_center')->nullable()->after('person_id')->comment('Centro de operación');
            });
        }
    }
};
