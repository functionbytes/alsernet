<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_ai_costs')) {
            return;
        }

        Schema::table('supplier_ai_costs', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_ai_costs', 'web_search_cost')) {
                $table->decimal('web_search_cost', 10, 6)->default(0)->after('output_cost');
            }
            if (! Schema::hasColumn('supplier_ai_costs', 'web_search_calls')) {
                $table->unsignedSmallInteger('web_search_calls')->default(0)->after('web_search_cost');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_ai_costs')) {
            return;
        }

        Schema::table('supplier_ai_costs', function (Blueprint $table) {
            $cols = array_filter(
                ['web_search_cost', 'web_search_calls'],
                fn ($c) => Schema::hasColumn('supplier_ai_costs', $c),
            );
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
