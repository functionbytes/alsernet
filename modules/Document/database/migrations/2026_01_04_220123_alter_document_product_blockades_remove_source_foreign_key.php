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
        // SQLite doesn't support dropping foreign keys
        if (config('database.default') !== 'sqlite') {
            Schema::table('document_product_blockades', function (Blueprint $table) {
                // Drop the foreign key constraint if it exists
                if (Schema::hasColumn('document_product_blockades', 'source_id')) {
                    $table->dropForeign(['source_id']);
                }
            });
        }
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
