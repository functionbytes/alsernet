<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_google_connections', function (Blueprint $table) {
            $table->string('platform', 50)->default('google')->after('user_id');
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::table('review_google_connections', function (Blueprint $table) {
            $table->dropIndex(['platform']);
            $table->dropColumn('platform');
        });
    }
};
