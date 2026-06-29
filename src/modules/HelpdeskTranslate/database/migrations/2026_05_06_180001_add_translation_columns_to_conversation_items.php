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
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'translated_body')) {
                $table->longText('translated_body')->nullable()->after('body');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'source_locale')) {
                $table->string('source_locale', 8)->nullable()->after('translated_body');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
            if (Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'translated_body')) {
                $table->dropColumn('translated_body');
            }
            if (Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'source_locale')) {
                $table->dropColumn('source_locale');
            }
        });
    }
};
