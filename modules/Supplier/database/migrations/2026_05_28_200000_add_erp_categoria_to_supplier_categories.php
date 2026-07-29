<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_categoria_id')->nullable()->after('erp_sport_id');
            $table->string('erp_categoria_name', 255)->nullable()->after('erp_categoria_id');
            $table->index('erp_categoria_id');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_categories', function (Blueprint $table) {
            $table->dropIndex(['erp_categoria_id']);
            $table->dropColumn(['erp_categoria_id', 'erp_categoria_name']);
        });
    }
};
