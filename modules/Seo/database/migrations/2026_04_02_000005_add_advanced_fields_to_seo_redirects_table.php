<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_redirects', function (Blueprint $table) {
            $table->boolean('is_regex')->default(false)->after('is_active');
            $table->timestamp('active_from')->nullable()->after('is_regex');
            $table->timestamp('active_until')->nullable()->after('active_from');
        });
    }

    public function down(): void
    {
        Schema::table('seo_redirects', function (Blueprint $table) {
            $table->dropColumn(['is_regex', 'active_from', 'active_until']);
        });
    }
};
