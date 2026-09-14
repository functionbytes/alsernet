<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->boolean('enable_web_search')->default(false)->after('seo_focus')
                ->comment('Use OpenAI Responses API with web_search_preview tool');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->dropColumn('enable_web_search');
        });
    }
};
