<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attention_routing_rules', function (Blueprint $table) {
            // Standalone priority index for ORDER BY priority queries without is_active filter.
            // Note: ['is_active', 'priority'] composite already exists from the create migration,
            // covering filtered + ordered queries. This adds coverage for unfiltered ordering.
            $table->index('priority', 'attention_routing_rules_priority_index');

            // Standalone is_active index for simple boolean filter queries.
            // The composite ['is_active', 'priority'] covers the leftmost prefix, but MariaDB
            // may not always choose it for simple WHERE is_active = 1 scans — explicit index
            // ensures the optimizer has a dedicated option.
            $table->index('is_active', 'attention_routing_rules_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('attention_routing_rules', function (Blueprint $table) {
            $table->dropIndex('attention_routing_rules_priority_index');
            $table->dropIndex('attention_routing_rules_is_active_index');
        });
    }
};
