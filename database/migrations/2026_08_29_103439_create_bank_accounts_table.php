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
            $table->id();
            $table->nullableMorphs('model');
            $table->foreignIdFor(Bank::class)->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(AccountType::class)->constrained()->cascadeOnUpdate();
            $table->string('account_number', 30);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
