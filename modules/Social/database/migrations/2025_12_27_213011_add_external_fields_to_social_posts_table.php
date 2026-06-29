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
        Schema::table('social_posts', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('status');
            $table->string('external_url')->nullable()->after('external_id');
            $table->integer('reach')->nullable()->after('shares_count');
            $table->integer('impressions')->nullable()->after('reach');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn(['external_id', 'external_url', 'reach', 'impressions']);
        });
    }
};
