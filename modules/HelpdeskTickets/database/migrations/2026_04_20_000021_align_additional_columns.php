<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_helpcenter_categories') && ! $schema->hasColumn('helpdesk_helpcenter_categories', 'is_section')) {
            $schema->table('helpdesk_helpcenter_categories', fn (Blueprint $t) => $t->boolean('is_section')->default(false)->index());
        }

        if ($schema->hasTable('helpdesk_ticket_canned_replies') && ! $schema->hasColumn('helpdesk_ticket_canned_replies', 'is_global')) {
            $schema->table('helpdesk_ticket_canned_replies', fn (Blueprint $t) => $t->boolean('is_global')->default(false)->index());
        }

        if ($schema->hasTable('helpdesk_ai_agent_tags') && ! $schema->hasColumn('helpdesk_ai_agent_tags', 'priority')) {
            $schema->table('helpdesk_ai_agent_tags', fn (Blueprint $t) => $t->integer('priority')->default(0)->index());
        }

        if ($schema->hasTable('helpdesk_ticket_category_ticket_group') && ! $schema->hasColumn('helpdesk_ticket_category_ticket_group', 'is_default')) {
            $schema->table('helpdesk_ticket_category_ticket_group', fn (Blueprint $t) => $t->boolean('is_default')->default(false));
        }
    }

    public function down(): void {}
};
