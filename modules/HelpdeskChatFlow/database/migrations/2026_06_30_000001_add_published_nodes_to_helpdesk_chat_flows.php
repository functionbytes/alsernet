<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasColumn('helpdesk_chat_flows', 'published_nodes')) {
            return;
        }

        $schema->table('helpdesk_chat_flows', function (Blueprint $table) {
            // Published snapshot of the node tree. The editor keeps writing the
            // working draft to `nodes`; the runtime engine executes this column.
            // Promoted from `nodes` only when the flow is published.
            $table->json('published_nodes')->nullable()->after('nodes');
        });

        // Backfill: currently active flows must keep running their live graph after
        // this migration, so seed the published snapshot from the existing draft.
        // From now on, editing an active flow's draft no longer changes runtime
        // until it is published again.
        DB::connection($this->connection)
            ->table('helpdesk_chat_flows')
            ->where('status', 'active')
            ->whereNull('published_nodes')
            ->update(['published_nodes' => DB::raw('nodes')]);
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_chat_flows', function (Blueprint $table) {
            $table->dropColumn('published_nodes');
        });
    }
};
