<?php

use App\Models\AccountType;
use App\Models\Bank;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->string('model_type')->nullable()->comment('Tipo de registro asociado');
            $table->unsignedBigInteger('model_id')->nullable()->comment('Registro asociado');
            $table->index(['model_type', 'model_id']);
            $table->foreignIdFor(Bank::class)->comment('Banco')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(AccountType::class)->comment('Tipo de cuenta')->constrained()->cascadeOnUpdate();
            $table->string('account_number', 30)->comment('Número de cuenta');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
            $table->softDeletes()->comment('Fecha de desactivación');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
