<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            // Optional reference text the user typed before speaking — used for word-diff pronunciation feedback
            $table->text('speaking_reference')->nullable()->after('speaking_transcript');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            $table->dropColumn('speaking_reference');
        });
    }
};
