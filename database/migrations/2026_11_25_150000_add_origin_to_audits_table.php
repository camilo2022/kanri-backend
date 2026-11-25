<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        if (Schema::connection($connection)->hasColumn($table, 'origin')) {
            return;
        }

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            $table->string('origin', 20)->nullable()->after('tags')->index()->comment('Desde dónde se hizo: web, mobile, ai, api o system');
        });
    }

    public function down(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        if (! Schema::connection($connection)->hasColumn($table, 'origin')) {
            return;
        }

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            $table->dropIndex(['origin']);
            $table->dropColumn('origin');
        });
    }
};
