<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::table('engagement_platform_integrations', function (Blueprint $table) {
            $table->timestamp('secret_rotated_at')->nullable()->after('webhook_secret');
        });
    }

    public function down(): void
    {
        Schema::table('engagement_platform_integrations', function (Blueprint $table) {
            $table->dropColumn('secret_rotated_at');
        });
    }
};
