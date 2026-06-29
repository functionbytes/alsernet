<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('engagement_visitor_contexts', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('context');
            $table->string('city', 100)->nullable()->after('country');
            $table->string('region', 100)->nullable()->after('city');
            $table->string('timezone', 50)->nullable()->after('region');
            $table->string('language', 10)->nullable()->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('engagement_visitor_contexts', function (Blueprint $table) {
            $table->dropColumn(['country', 'city', 'region', 'timezone', 'language']);
        });
    }
};
