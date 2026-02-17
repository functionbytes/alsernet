<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to use raw SQL to modify ENUM
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft', 'sent', 'queued') DEFAULT 'draft'");
    }
};
