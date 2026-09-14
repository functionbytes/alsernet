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

        // This table was repurposed from conversation read-tracking to saved view configurations.
        // conversation_id and user_id must be nullable to allow system-level views with no owner.
        $schema->table('helpdesk_conversation_views', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('helpdesk_conversation_views', 'conversation_id')) {
                $table->dropForeign(['conversation_id']);
                $table->unsignedBigInteger('conversation_id')->nullable()->change();
            }

            if ($schema->hasColumn('helpdesk_conversation_views', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_views', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
