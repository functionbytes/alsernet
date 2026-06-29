<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_redirects', function (Blueprint $table) {
            $table->boolean('is_wildcard')->default(false)->after('is_regex');
        });
    }

    public function down(): void
    {
        Schema::table('seo_redirects', function (Blueprint $table) {
            $table->dropColumn('is_wildcard');
        });
    }
};
