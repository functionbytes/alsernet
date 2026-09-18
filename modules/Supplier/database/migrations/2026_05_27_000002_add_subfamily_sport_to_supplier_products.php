<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            $table->unsignedBigInteger('subfamily_id')->nullable()->after('category_id');
            $table->unsignedBigInteger('erp_subfamily_id')->nullable()->after('erp_category_id');
            $table->unsignedBigInteger('sport_id')->nullable()->after('erp_subfamily_id');
            $table->unsignedBigInteger('erp_sport_id')->nullable()->after('sport_id');

            $table->index('subfamily_id');
            $table->index('sport_id');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            $table->dropIndex(['subfamily_id']);
            $table->dropIndex(['sport_id']);
            $table->dropColumn(['subfamily_id', 'erp_subfamily_id', 'sport_id', 'erp_sport_id']);
        });
    }
};
