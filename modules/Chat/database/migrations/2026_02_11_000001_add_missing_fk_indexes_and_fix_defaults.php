<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversation_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id')->nullable()->change();
        });

        if (! $this->indexExists('chat_conversation_messages', 'chat_conversation_messages_conversation_id_index')) {
            Schema::table('chat_conversation_messages', function (Blueprint $table) {
                $table->index('conversation_id');
            });
        }

        if (! $this->foreignExists('chat_conversation_messages', 'chat_conversation_messages_conversation_id_foreign')) {
            Schema::table('chat_conversation_messages', function (Blueprint $table) {
                $table->foreign('conversation_id')
                    ->references('id')
                    ->on('chat_conversations')
                    ->nullOnDelete();
            });
        }

        if (! $this->indexExists('chat_conversation_messages', 'hd_messages_conv_created')) {
            Schema::table('chat_conversation_messages', function (Blueprint $table) {
                $table->index(['conversation_id', 'created_at'], 'hd_messages_conv_created');
            });
        }

        if (! $this->indexExists('chat_sla_applied_conversations', 'chat_sla_applied_conversations_conversation_id_index')) {
            Schema::table('chat_sla_applied_conversations', function (Blueprint $table) {
                $table->index('conversation_id');
            });
        }

        if (! $this->foreignExists('chat_sla_applied_conversations', 'chat_sla_applied_conversations_conversation_id_foreign')) {
            Schema::table('chat_sla_applied_conversations', function (Blueprint $table) {
                $table->foreign('conversation_id')
                    ->references('id')
                    ->on('chat_conversations')
                    ->cascadeOnDelete();
            });
        }

        if (! $this->indexExists('chat_canneds', 'hd_canneds_account_shortcode')) {
            Schema::table('chat_canneds', function (Blueprint $table) {
                $table->index(['account_id', 'short_code'], 'hd_canneds_account_shortcode');
            });
        }

        Schema::table('chat_conversation_priorities', function (Blueprint $table) {
            $table->string('color')->default('#808080')->change();
        });

        Schema::table('chat_conversation_statuses', function (Blueprint $table) {
            $table->string('color')->default('#808080')->change();
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversation_statuses', function (Blueprint $table) {
            $table->string('color')->default('#gray')->change();
        });

        Schema::table('chat_conversation_priorities', function (Blueprint $table) {
            $table->string('color')->default('#gray')->change();
        });

        Schema::table('chat_canneds', function (Blueprint $table) {
            $table->dropIndex('hd_canneds_account_shortcode');
        });

        Schema::table('chat_sla_applied_conversations', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropIndex(['conversation_id']);
        });

        Schema::table('chat_conversation_messages', function (Blueprint $table) {
            $table->dropIndex('hd_messages_conv_created');
        });

        Schema::table('chat_conversation_messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropIndex(['conversation_id']);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        )->c > 0;
    }

    private function foreignExists(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();

        return DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = "FOREIGN KEY"',
            [$database, $table, $constraint]
        )->c > 0;
    }
};
