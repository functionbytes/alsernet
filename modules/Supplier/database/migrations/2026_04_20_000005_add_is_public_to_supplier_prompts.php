<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_prompts')) {
            return;
        }

        if (! Schema::hasColumn('supplier_prompts', 'is_public')) {
            Schema::table('supplier_prompts', function (Blueprint $table) {
                $table->boolean('is_public')->default(false)->index();
            });
        }

        if (! Schema::hasColumn('supplier_prompts', 'usage_count')) {
            Schema::table('supplier_prompts', function (Blueprint $table) {
                $table->unsignedInteger('usage_count')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_prompts', 'is_public')) {
                $table->dropColumn('is_public');
            }
            if (Schema::hasColumn('supplier_prompts', 'usage_count')) {
                $table->dropColumn('usage_count');
            }
        });
    }
};
