<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_groups', function (Blueprint $table) {
            $table->foreignId('tag_id')
                ->nullable()
                ->after('description')
                ->constrained('helpdesk_conversation_tags', 'id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_groups', function (Blueprint $table) {
            $table->dropForeign(['tag_id']);
            $table->dropColumn('tag_id');
        });
    }
};
