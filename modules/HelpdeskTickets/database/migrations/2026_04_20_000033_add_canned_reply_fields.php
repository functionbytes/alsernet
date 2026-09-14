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

        if (! $schema->hasTable('helpdesk_ticket_canned_replies')) {
            return;
        }

        $schema->table('helpdesk_ticket_canned_replies', function (Blueprint $t) use ($schema) {
            if (! $schema->hasColumn('helpdesk_ticket_canned_replies', 'html_body')) {
                $t->longText('html_body')->nullable();
            }
            if (! $schema->hasColumn('helpdesk_ticket_canned_replies', 'category')) {
                $t->string('category', 100)->nullable();
            }
            if (! $schema->hasColumn('helpdesk_ticket_canned_replies', 'tags')) {
                $t->json('tags')->nullable();
            }
        });

        if (! $schema->hasTable('helpdesk_canned_reply_ticket_category')) {
            $schema->create('helpdesk_canned_reply_ticket_category', function (Blueprint $t) {
                $t->foreignId('ticket_canned_reply_id');
                $t->foreignId('ticket_category_id');
                $t->integer('order')->default(0);
                $t->primary(['ticket_canned_reply_id', 'ticket_category_id'], 'canned_reply_category_pk');
            });
        }
    }

    public function down(): void {}
};
