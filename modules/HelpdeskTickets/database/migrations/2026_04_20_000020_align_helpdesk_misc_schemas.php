<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk-align secondary helpdesk tables with current model expectations.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        // helpdesk_conversations — is_public
        if ($schema->hasTable('helpdesk_conversations') && ! $schema->hasColumn('helpdesk_conversations', 'is_public')) {
            $schema->table('helpdesk_conversations', fn (Blueprint $t) => $t->boolean('is_public')->default(false)->after('is_archived'));
        }

        // helpdesk_helpcenter_categories — parent_id
        if ($schema->hasTable('helpdesk_helpcenter_categories') && ! $schema->hasColumn('helpdesk_helpcenter_categories', 'parent_id')) {
            $schema->table('helpdesk_helpcenter_categories', fn (Blueprint $t) => $t->unsignedBigInteger('parent_id')->nullable()->index());
        }

        // helpdesk_groups — is_active
        if ($schema->hasTable('helpdesk_groups') && ! $schema->hasColumn('helpdesk_groups', 'is_active')) {
            $schema->table('helpdesk_groups', fn (Blueprint $t) => $t->boolean('is_active')->default(true)->index());
        }

        // helpdesk_ticket_canned_replies — deleted_at
        if ($schema->hasTable('helpdesk_ticket_canned_replies') && ! $schema->hasColumn('helpdesk_ticket_canned_replies', 'deleted_at')) {
            $schema->table('helpdesk_ticket_canned_replies', fn (Blueprint $t) => $t->softDeletes());
        }

        // helpdesk_ticket_views — is_system
        if ($schema->hasTable('helpdesk_ticket_views') && ! $schema->hasColumn('helpdesk_ticket_views', 'is_system')) {
            $schema->table('helpdesk_ticket_views', fn (Blueprint $t) => $t->boolean('is_system')->default(false));
        }

        // helpdesk_ai_agent_tags — base table (some envs never got it)
        if (! $schema->hasTable('helpdesk_ai_agent_tags')) {
            $schema->create('helpdesk_ai_agent_tags', function (Blueprint $t) {
                $t->id();
                $t->string('name')->index();
                $t->string('color', 32)->nullable();
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true)->index();
                $t->timestamps();
                $t->softDeletes();
            });
        }

        // helpdesk_ticket_category_ticket_group pivot
        if (! $schema->hasTable('helpdesk_ticket_category_ticket_group')) {
            $schema->create('helpdesk_ticket_category_ticket_group', function (Blueprint $t) {
                $t->unsignedBigInteger('ticket_category_id');
                $t->unsignedBigInteger('ticket_group_id');
                $t->primary(['ticket_category_id', 'ticket_group_id'], 'hdtcg_pk');
                $t->index('ticket_category_id', 'hdtcg_cat_idx');
                $t->index('ticket_group_id', 'hdtcg_grp_idx');
            });
        }
    }

    public function down(): void {}
};
