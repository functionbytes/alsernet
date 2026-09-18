<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('helpdesk')
            ->table('helpdesk_inboxes')
            ->where('channel_type', 'widget')
            ->update(['channel_type' => 'web']);

        DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('channel', 'widget')
            ->update(['channel' => 'web']);
    }

    public function down(): void
    {
        // Intentionally left empty: rolling back a data unification would re-introduce
        // the deprecated 'widget' alias and break the simplified CHANNEL_TYPE_MAP.
    }
};
