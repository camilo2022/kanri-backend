<?php

use App\Models\BackType;
use App\Models\BootType;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Employee;
use App\Models\GarmentType;
use App\Models\Product;
use App\Models\Subgroup;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Illuminate\Database\Migrations\Migration;
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
        Schema::create('technical_sheets', function (Blueprint $table) {
            $table->id()->comment('Identificador');
            $table->uuid()->comment('Código único');
            $table->string('model_type')->nullable()->comment('Tipo de registro asociado');
            $table->unsignedBigInteger('model_id')->nullable()->comment('Registro asociado');
            $table->index(['model_type', 'model_id']);
            $table->string('code', 11)->unique()->nullable()->comment('Código');
            $table->string('consecutive', 11)->unique()->nullable()->comment('Consecutivo');
            $table->date('date')->nullable()->comment('Fecha');
            $table->foreignIdFor(Product::class)->unique()->comment('Producto')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Collection::class)->comment('Colección')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(GarmentType::class)->comment('Tipo de prenda')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Subgroup::class)->nullable()->comment('Subgrupo')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(WashTone::class)->comment('Tono de lavado')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Color::class)->nullable()->comment('Color')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(WaistbandType::class)->nullable()->comment('Tipo de pretina')->constrained()->cascadeOnUpdate();
            $table->unsignedBigInteger('measure_of_waistband')->default(0)->comment('Medida de la pretina');
            $table->foreignIdFor(BootType::class)->comment('Tipo de bota')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(YokeType::class)->nullable()->comment('Tipo de cotilla')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(BackType::class)->nullable()->comment('Tipo de trasero')->constrained()->cascadeOnUpdate();
            $table->foreignIdFor(Employee::class, 'pattern_maker_id')->nullable()->comment('Patronista')->constrained()->cascadeOnUpdate();
            $table->boolean('physical_sample')->default(0)->comment('Muestra física');
            $table->unsignedBigInteger('number_of_buttons')->default(0)->comment('Número de botones');
            $table->text('description')->nullable()->comment('Descripción');
            $table->text('observation')->nullable()->comment('Observación');
            $table->enum('status', ['Pendiente', 'En revision', 'Aprobado', 'Cancelado'])->default('Pendiente')->comment('Estado');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de actualización');
            $table->softDeletes()->comment('Fecha de desactivación');
        });

        DB::unprepared('DROP PROCEDURE IF EXISTS generate_technical_sheet_consecutive');

        DB::unprepared("
            CREATE PROCEDURE generate_technical_sheet_consecutive()
            BEGIN

                DECLARE date_consecutive CHAR(6);
                DECLARE last_consecutive VARCHAR(11);
                DECLARE next_consecutive INT;

                SET date_consecutive = DATE_FORMAT(CURDATE(), '%y%m%d');

                SELECT consecutive
                INTO last_consecutive
                FROM technical_sheets
                WHERE consecutive LIKE CONCAT('TS', date_consecutive, '%')
                ORDER BY consecutive DESC
                LIMIT 1;

                IF last_consecutive IS NULL THEN

                    SELECT CONCAT( 'TS', date_consecutive, '001') AS consecutive;

                ELSE

                    SET next_consecutive =
                        CAST(RIGHT(last_consecutive, 3) AS UNSIGNED) + 1;

                    SELECT CONCAT(
                        'TS',
                        date_consecutive,
                        LPAD(next_consecutive, 3, '0')
                    ) AS consecutive;

                END IF;

            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS generate_technical_sheet_consecutive');
        Schema::dropIfExists('technical_sheets');
    }
};
