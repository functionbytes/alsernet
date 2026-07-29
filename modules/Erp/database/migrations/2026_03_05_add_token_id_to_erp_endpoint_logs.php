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
        Schema::table('erp_endpoint_logs', function (Blueprint $table) {
            $table->foreignId('token_id')
                ->nullable()
                ->after('user_id')
                ->constrained('erp_endpoint_tokens')
                ->onDelete('set null');

            // Add index for better query performance
            $table->index('token_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_endpoint_logs', function (Blueprint $table) {
            if (Schema::hasColumn('erp_endpoint_logs', 'token_id')) {
                // Drop foreign key constraint first
                $table->dropConstrainedForeignId('token_id');
            }
        });
    }
};
