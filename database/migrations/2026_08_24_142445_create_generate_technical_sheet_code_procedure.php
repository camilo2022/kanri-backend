<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technical_sheets', function (Blueprint $table) {
            $table->string('consecutive', 11)
                ->unique()
                ->nullable()
                ->after('code');
        });

        DB::unprepared('
            DROP PROCEDURE IF EXISTS generate_technical_sheet_code
        ');

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

                    SELECT CONCAT(
                        'TS',
                        date_consecutive,
                        '001'
                    ) AS consecutive;

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

    public function down(): void
    {
        DB::unprepared('
            DROP PROCEDURE IF EXISTS generate_technical_sheet_consecutive
        ');

        Schema::table('technical_sheets', function (Blueprint $table) {
            $table->dropColumn('consecutive');
        });
    }
};
