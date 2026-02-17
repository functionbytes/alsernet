<?php

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
        // Add FULLTEXT index to pages table
        // Note: FULLTEXT indexes require InnoDB engine and are available in MySQL 5.6+
        DB::statement('ALTER TABLE pages ADD FULLTEXT fulltext_search(title, content, description)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FULLTEXT index
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('fulltext_search');
        });
    }
};
