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
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'outgoing_translated_body')) {
                $table->longText('outgoing_translated_body')->nullable()->after('source_locale');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'outgoing_target_locale')) {
                $table->string('outgoing_target_locale', 8)->nullable()->after('outgoing_translated_body');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
            foreach (['outgoing_translated_body', 'outgoing_target_locale'] as $col) {
                if (Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
