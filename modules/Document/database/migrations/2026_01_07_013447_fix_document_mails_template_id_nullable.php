<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop and recreate the foreign key for template_id to properly allow NULL values
     */
    public function up(): void
    {
        // SQLite doesn't support dropping foreign keys by name
        if (config('database.default') !== 'sqlite') {
            Schema::table('document_mails', function (Blueprint $table) {
                // Drop the existing foreign key constraint
                $table->dropForeign('document_mails_template_id_foreign');
            });

            Schema::table('document_mails', function (Blueprint $table) {
                // Recreate the foreign key with proper NULL handling
                $table->foreign('template_id')
                    ->references('id')
                    ->on('mail_templates')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite doesn't support dropping foreign keys by name
        if (config('database.default') !== 'sqlite') {
            Schema::table('document_mails', function (Blueprint $table) {
                $table->dropForeign('document_mails_template_id_foreign');
            });

            Schema::table('document_mails', function (Blueprint $table) {
                $table->foreign('template_id')
                    ->references('id')
                    ->on('mail_templates')
                    ->nullOnDelete();
            });
        }
    }
};
