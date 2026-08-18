<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengubah kolom `set` di tabel questions dan `set_used` di diagnostic_results
     * dari ENUM('A', 'B', 'C') menjadi VARCHAR(50) agar mendukung set baru seperti 'Set D', 'Set E', dst.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE questions MODIFY COLUMN `set` VARCHAR(50) NOT NULL DEFAULT 'A'");
            if (Schema::hasTable('diagnostic_results') && Schema::hasColumn('diagnostic_results', 'set_used')) {
                DB::statement("ALTER TABLE diagnostic_results MODIFY COLUMN `set_used` VARCHAR(50) NULL");
            }
        } else {
            Schema::table('questions', function (Blueprint $table) {
                $table->string('set', 50)->default('A')->change();
            });
            if (Schema::hasTable('diagnostic_results') && Schema::hasColumn('diagnostic_results', 'set_used')) {
                Schema::table('diagnostic_results', function (Blueprint $table) {
                    $table->string('set_used', 50)->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE questions MODIFY COLUMN `set` ENUM('A', 'B', 'C') NOT NULL DEFAULT 'A'");
            if (Schema::hasTable('diagnostic_results') && Schema::hasColumn('diagnostic_results', 'set_used')) {
                DB::statement("ALTER TABLE diagnostic_results MODIFY COLUMN `set_used` ENUM('A', 'B', 'C') NULL");
            }
        }
    }
};
