<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_google_locations', function (Blueprint $table) {
            $table->json('available_tags')->nullable()->after('sync_strategy');
        });
    }

    public function down(): void
    {
        Schema::table('review_google_locations', function (Blueprint $table) {
            $table->dropColumn('available_tags');
        });
    }
};
