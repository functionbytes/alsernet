<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deduplicates page_auto_saves keeping the newest row per (page_id, user_id),
     * then adds a unique constraint to prevent future duplicate rows from race conditions.
     */
    public function up(): void
    {
        // Remove duplicate rows, keeping only the newest per (page_id, user_id).
        // Use database-specific syntax for MySQL/MariaDB and SQLite.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: subquery syntax
            DB::statement('
                DELETE FROM page_auto_saves
                WHERE id NOT IN (
                    SELECT MAX(id)
                    FROM page_auto_saves
                    GROUP BY page_id, user_id
                )
            ');
        } else {
            // MySQL/MariaDB: DELETE with INNER JOIN
            DB::statement('
                DELETE pas1 FROM page_auto_saves pas1
                INNER JOIN page_auto_saves pas2
                    ON pas1.page_id = pas2.page_id
                    AND pas1.user_id = pas2.user_id
                    AND pas1.id < pas2.id
            ');
        }

        Schema::table('page_auto_saves', function (Blueprint $table) {
            $table->unique(['page_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_auto_saves', function (Blueprint $table) {
            $table->dropUnique(['page_id', 'user_id']);
        });
    }
};
