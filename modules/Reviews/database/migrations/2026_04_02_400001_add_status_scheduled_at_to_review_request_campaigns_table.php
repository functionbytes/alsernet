<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_request_campaigns', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'paused'])->default('draft')->after('review_url');
            $table->timestamp('scheduled_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('review_request_campaigns', function (Blueprint $table) {
            $table->dropColumn(['status', 'scheduled_at']);
        });
    }
};
