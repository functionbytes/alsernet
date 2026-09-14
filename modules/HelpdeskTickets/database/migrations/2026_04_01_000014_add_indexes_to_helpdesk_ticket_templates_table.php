<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ticket_templates')) {
            return;
        }

        Schema::connection('helpdesk')->table('helpdesk_ticket_templates', function (Blueprint $table) {
            if (! $this->hasIndex('helpdesk_ticket_templates', 'ticket_templates_category_active_index')) {
                $table->index(['category_id', 'is_active'], 'ticket_templates_category_active_index');
            }

            if (! $this->hasIndex('helpdesk_ticket_templates', 'ticket_templates_active_index')) {
                $table->index('is_active', 'ticket_templates_active_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('helpdesk')->hasTable('helpdesk_ticket_templates')) {
            return;
        }

        Schema::connection('helpdesk')->table('helpdesk_ticket_templates', function (Blueprint $table) {
            $table->dropIndexIfExists('ticket_templates_category_active_index');
            $table->dropIndexIfExists('ticket_templates_active_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::connection('helpdesk')->getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
