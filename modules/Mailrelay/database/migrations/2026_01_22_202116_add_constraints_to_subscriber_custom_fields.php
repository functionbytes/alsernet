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
        Schema::table('subscriber_custom_fields', function (Blueprint $table) {
            // Add foreign key constraints if they don't exist
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('subscribers')
                ->onDelete('cascade');

            $table->foreign('custom_field_id')
                ->references('id')
                ->on('custom_fields')
                ->onDelete('cascade');

            // Add unique constraint
            $table->unique(['subscriber_id', 'custom_field_id'], 'subscriber_custom_field_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriber_custom_fields', function (Blueprint $table) {
            $table->dropForeign(['subscriber_id']);
            $table->dropForeign(['custom_field_id']);
            $table->dropUnique('subscriber_custom_field_unique');
        });
    }
};
