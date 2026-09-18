<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'item_type')) {
                $table->string('item_type', 32)->default('message')->after('type')->index();
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'activity_type')) {
                $table->string('activity_type', 64)->nullable()->after('item_type');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'activity_data')) {
                $table->json('activity_data')->nullable()->after('activity_type');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
            if (Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'activity_data')) {
                $table->dropColumn('activity_data');
            }
            if (Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'activity_type')) {
                $table->dropColumn('activity_type');
            }
            if (Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'item_type')) {
                $table->dropIndex(['item_type']);
                $table->dropColumn('item_type');
            }
        });
    }
};
