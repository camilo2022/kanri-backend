<?php

use App\Models\Supplier;
use App\Models\TechnicalSheet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TechnicalSheet::class)->constrained()->cascadeOnUpdate();
            $table->string('consecutive', 11)->unique();
            $table->enum('production_place', ['BLESS', 'SATELITE'])->default('BLESS');
            $table->foreignIdFor(Supplier::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->float('efficiency', 8.3)->nullable();
            $table->string('cut', 2);
            $table->decimal('width', 8, 2)->nullable();
            $table->boolean('pocket_fabric')->default(false);
            $table->enum('priority', ['1', '2', '3', '4', '5'])->default('3');
            $table->text('observation')->nullable();
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'))->comment('Configuraciones');
            $table->enum('status', ['Pendiente', 'Cancelado', 'Aprobado'])->default('Pendiente');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::unprepared('DROP PROCEDURE IF EXISTS generate_production_order_consecutive');

        DB::unprepared("
            CREATE PROCEDURE generate_production_order_consecutive()
                BEGIN

                    DECLARE date_consecutive CHAR(6);
                    DECLARE last_consecutive VARCHAR(11);
                    DECLARE next_consecutive INT;

                    SET date_consecutive = DATE_FORMAT(CURDATE(), '%y%m%d');

                    SELECT consecutive INTO last_consecutive FROM production_orders
                    WHERE consecutive LIKE CONCAT('PO', date_consecutive , '%') ORDER BY consecutive DESC LIMIT 1;

                    IF last_consecutive IS NULL THEN
                        SELECT CONCAT('PO', date_consecutive , '001') AS consecutive;
                    ELSE
                        SET next_consecutive = CAST(RIGHT(last_consecutive, 3) AS UNSIGNED) + 1;

                        SELECT CONCAT('PO', date_consecutive , LPAD(next_consecutive, 3, '0')) AS consecutive;
                    END IF;

                END
            ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS generate_production_order_consecutive');

        Schema::dropIfExists('production_orders');
    }
};
