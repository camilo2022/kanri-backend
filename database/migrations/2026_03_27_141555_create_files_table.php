<?php

use App\Models\FileSubtype;
use App\Models\FileType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->string('model_type')->comment('Tipo de registro asociado');
            $table->unsignedBigInteger('model_id')->comment('Registro asociado');
            $table->index(['model_type', 'model_id']);
            $table->string('name')->comment('Nombre del archivo');
            $table->foreignIdFor(FileType::class)->comment('Tipo de archivo')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(FileSubtype::class)->comment('Subtipo de archivo')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('path')->comment('Ubicación');
            $table->string('mime')->nullable()->comment('Formato');
            $table->string('extension')->nullable()->comment('Extensión');
            $table->float('size')->nullable()->comment('Tamaño del archivo');
            $table->json('metadata')->default(new Expression('(JSON_OBJECT())'))->comment('Metadatos');
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
