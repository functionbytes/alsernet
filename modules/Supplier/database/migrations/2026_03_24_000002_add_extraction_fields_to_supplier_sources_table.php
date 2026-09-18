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
        Schema::table('supplier_sources', function (Blueprint $table) {
            $table->enum('extraction_mode', ['manual', 'ai'])->default('ai')->after('is_active');
            $table->timestamp('last_processed_at')->nullable()->after('last_accessed_at');
            $table->string('last_batch_status', 20)->nullable()->after('last_processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_sources', function (Blueprint $table) {
            $table->dropColumn(['extraction_mode', 'last_processed_at', 'last_batch_status']);
        });
    }
};
