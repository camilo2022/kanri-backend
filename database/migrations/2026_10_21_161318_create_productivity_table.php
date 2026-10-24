<?php

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
        Schema::create('productivity', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productivity');
    }
};
