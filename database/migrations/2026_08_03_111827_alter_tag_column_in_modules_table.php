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
        // First convert to string to avoid truncate errors with existing data
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE modules MODIFY COLUMN tag VARCHAR(50) NOT NULL DEFAULT 'grammar'");
        
        // Update existing invalid tags
        \Illuminate\Support\Facades\DB::statement("UPDATE modules SET tag = 'grammar' WHERE tag NOT IN ('grammar', 'vocabulary')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back (note: this might fail if there's data, but for completeness)
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE modules MODIFY COLUMN tag ENUM('basic', 'intermediate', 'advanced') NOT NULL DEFAULT 'basic'");
    }
};
