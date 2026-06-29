<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_on_page')->nullable()->after('viewed_at'); // seconds
            $table->boolean('bounced')->default(false)->after('time_on_page'); // < 10 seconds
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropColumn(['time_on_page', 'bounced']);
        });
    }
};
