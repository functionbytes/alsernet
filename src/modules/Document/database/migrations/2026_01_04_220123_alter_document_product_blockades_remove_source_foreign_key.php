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
        // SQLite doesn't support dropping foreign keys
        if (config('database.default') === 'sqlite') {
            return;
        }

        if (! Schema::hasTable('document_product_blockades')) {
            return;
        }

        $foreignKeyExists = collect(
            DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL',
                ['document_product_blockades', 'source_id']
            )
        )->isNotEmpty();

        if (! $foreignKeyExists) {
            return;
        }

        Schema::table('document_product_blockades', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_product_blockades', function (Blueprint $table) {
            // Restore the foreign key if rolling back
            $table->foreign('source_id')
                ->references('id')
                ->on('document_sources')
                ->nullOnDelete();
        });
    }
};
