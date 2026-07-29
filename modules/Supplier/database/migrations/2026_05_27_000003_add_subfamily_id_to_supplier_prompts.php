<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->unsignedBigInteger('subfamily_id')->nullable()->after('category_id');
            $table->index('subfamily_id');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->dropIndex(['subfamily_id']);
            $table->dropColumn('subfamily_id');
        });
    }
};
