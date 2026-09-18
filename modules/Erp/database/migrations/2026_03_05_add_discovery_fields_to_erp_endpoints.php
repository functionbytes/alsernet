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
        Schema::table('erp_endpoints', function (Blueprint $table) {
            $table->string('controller')->nullable()->after('method');
            $table->string('action')->nullable()->after('controller');
            $table->text('description')->nullable()->after('action');
            $table->json('parameters')->nullable()->after('description');
            $table->json('response_example')->nullable()->after('parameters');
            $table->boolean('is_auto_discovered')->default(false)->after('is_active');
            $table->timestamp('deprecated_at')->nullable()->after('is_auto_discovered');

            // Add indexes for better query performance
            $table->index('is_auto_discovered');
            $table->index('deprecated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_endpoints', function (Blueprint $table) {
            $table->dropColumn([
                'controller',
                'action',
                'description',
                'parameters',
                'response_example',
                'is_auto_discovered',
                'deprecated_at',
            ]);

            $table->dropIndex(['is_auto_discovered']);
            $table->dropIndex(['deprecated_at']);
        });
    }
};
