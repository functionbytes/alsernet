<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->string('title_b')->nullable()->after('title');
            $table->text('description_b')->nullable()->after('description');
            $table->enum('ab_winner', ['a', 'b'])->nullable()->after('description_b');
            $table->unsignedInteger('ab_impressions_a')->default(0)->after('ab_winner');
            $table->unsignedInteger('ab_impressions_b')->default(0)->after('ab_impressions_a');
        });
    }

    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->dropColumn(['title_b', 'description_b', 'ab_winner', 'ab_impressions_a', 'ab_impressions_b']);
        });
    }
};
