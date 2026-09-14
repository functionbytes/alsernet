<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ConversationsController::index() calcula el promedio de primera respuesta
 * del dia con whereNotNull('first_response_at')->whereDate('first_response_at', $today)
 * cada 30s (cache TTL) — sin indice, full table scan a medida que crece la tabla.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            if (! $this->hasIndex('helpdesk_conversations', 'helpdesk_conversations_first_response_at_index')) {
                $table->index('first_response_at', 'helpdesk_conversations_first_response_at_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_conversations')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropIndexIfExists('helpdesk_conversations_first_response_at_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
