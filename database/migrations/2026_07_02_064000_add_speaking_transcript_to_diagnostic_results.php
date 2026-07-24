<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            // Store the raw Whisper transcript text for pronunciation feedback display
            $table->text('speaking_transcript')->nullable()->after('audio_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            $table->dropColumn('speaking_transcript');
        });
    }
};
