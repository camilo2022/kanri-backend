<?php

use App\Models\TechnicalSheet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->foreignIdFor(TechnicalSheet::class)->constrained()->cascadeOnUpdate();
            $table->nullableMorphs('model');
            $table->string('consecutive', 11)->unique();
            $table->float('efficiency', 8.3)->nullable();
            $table->string('cut', 2);
            $table->decimal('width', 8, 2)->nullable();
            $table->boolean('pocket_fabric')->default(false);
            $table->text('observation')->nullable();
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->enum('status', ['Pendiente', 'Cancelado', 'Aprobado'])->default('Pendiente');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::unprepared("
            CREATE PROCEDURE generate_production_order_code()
                BEGIN

                    DECLARE date_code  CHAR(6);
                    DECLARE last_consecutive VARCHAR(11);
                    DECLARE next_consecutive INT;

                    SET date_code  = DATE_FORMAT(CURDATE(), '%y%m%d');

                    SELECT consecutive INTO last_consecutive FROM production_orders
                    WHERE consecutive LIKE CONCAT('PO', date_code , '%') ORDER BY consecutive DESC LIMIT 1;

                    IF last_consecutive IS NULL THEN
                        SELECT CONCAT('PO', date_code , '001') AS consecutive;
                    ELSE
                        SET next_consecutive = CAST(RIGHT(last_consecutive, 3) AS UNSIGNED) + 1;

                        SELECT CONCAT('PO', date_code , LPAD(next_consecutive, 3, '0')) AS consecutive;
                    END IF;

                END
            ");
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS generate_production_order_code");

        Schema::dropIfExists('production_orders');
    }
};
