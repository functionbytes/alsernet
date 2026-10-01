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

        if (! $schema->hasTable('helpdesk_ticket_attachments') || $schema->hasColumn('helpdesk_ticket_attachments', 'processed_at')) {
            return;
        }

        $schema->table('helpdesk_ticket_attachments', function (Blueprint $table) {
            $table->string('scan_status', 20)->nullable()->index();
            $table->string('scan_signature', 191)->nullable();
            $table->longText('transcript')->nullable();
            $table->json('media_meta')->nullable();
            $table->timestamp('processed_at')->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasColumn('helpdesk_ticket_attachments', 'processed_at')) {
            return;
        }

        $schema->table('helpdesk_ticket_attachments', function (Blueprint $table) {
            $table->dropIndex(['scan_status']);
            $table->dropColumn(['scan_status', 'scan_signature', 'transcript', 'media_meta', 'processed_at']);
        });
    }
};
