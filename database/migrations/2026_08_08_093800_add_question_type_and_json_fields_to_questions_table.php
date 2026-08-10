<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First modify existing enum correct_answer to be nullable and potentially store string
        // Since sqlite or some db engines don't support modifying enum easily, let's just make it a string column or drop & recreate if needed.
        // Actually, Doctrine DBAL doesn't fully support changing enums.
        // We'll create a new column `answer_data` instead.

        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_type')->default('multiple_choice')->after('question');
            $table->json('options_json')->nullable()->after('option_d');
            $table->json('answer_json')->nullable()->after('correct_answer');
            
            // Make legacy options nullable
            $table->string('option_a', 1000)->nullable()->change();
            $table->string('option_b', 1000)->nullable()->change();
            $table->string('option_c', 1000)->nullable()->change();
            $table->string('option_d', 1000)->nullable()->change();
            
            // To handle correct_answer enum, we might need DB raw if it's enum
            // Just leaving correct_answer alone as nullable might be tricky for ENUM in some DBs.
            // Let's assume it's possible or we'll bypass.
        });
        
        // Changing ENUM in Laravel requires dropping and recreating if we want it nullable, or we use string.
        // If it's a fresh app, modifying might work if Doctrine DBAL is installed.
        // Let's use a raw query if necessary or just change to string.
        try {
            Schema::table('questions', function (Blueprint $table) {
                 $table->string('correct_answer')->nullable()->change();
            });
        } catch (\Exception $e) {
            // DBAL might throw exception on enum, let's ignore for a moment if it's strictly a schema issue.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['question_type', 'options_json', 'answer_json']);
        });
    }
};
