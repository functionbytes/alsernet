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
        Schema::table('subscribers', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['list_id']);

            // Make list_id nullable
            $table->unsignedBigInteger('list_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('list_id')->references('id')->on('lists')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['list_id']);

            // Make list_id NOT NULL again
            $table->unsignedBigInteger('list_id')->nullable(false)->change();

            // Re-add the foreign key constraint with cascade
            $table->foreign('list_id')->references('id')->on('lists')->onDelete('cascade');
        });
    }
};
