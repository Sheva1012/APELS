<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * APELS-EXECUTION-SPEC §3:
 * Tambahkan kolom set_used dan answers ke diagnostic_results.
 * Buat tabel learning_paths baru (terpisah dari user_learning_paths yang ada).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 3a. Tambah kolom ke diagnostic_results
        Schema::table('diagnostic_results', function (Blueprint $table) {
            // set_used: SET yang dipakai saat tes (A/B/C)
            $table->enum('set_used', ['A', 'B', 'C'])->nullable()->after('user_id');
            // attempt_number: nomor urut attempt (1, 2, 3, ...)
            $table->unsignedInteger('attempt_number')->default(1)->after('set_used');
            // answers: JSON jawaban mentah mahasiswa [{question_id: x, answer: 'a'}, ...]
            $table->json('answers')->nullable()->after('is_speaking_processed');
        });

        // 3b. Buat tabel learning_paths baru (sesuai spec §3)
        // Tabel user_learning_paths (yang lama) tetap ada untuk backward-compat.
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path_name');
            $table->json('modules_unlocked')->nullable();
            $table->json('modules_locked')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            $table->dropColumn(['set_used', 'attempt_number', 'answers']);
        });

        Schema::dropIfExists('learning_paths');
    }
};
