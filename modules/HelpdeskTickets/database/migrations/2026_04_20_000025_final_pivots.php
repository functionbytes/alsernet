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

        if ($schema->hasTable('helpdesk_ticket_category_ticket_canned_reply') && ! $schema->hasColumn('helpdesk_ticket_category_ticket_canned_reply', 'order')) {
            $schema->table('helpdesk_ticket_category_ticket_canned_reply', fn (Blueprint $t) => $t->integer('order')->default(0));
        }

        if (! $schema->hasTable('helpdesk_ticket_group_user')) {
            $schema->create('helpdesk_ticket_group_user', function (Blueprint $t) {
                $t->unsignedBigInteger('ticket_group_id');
                $t->unsignedBigInteger('user_id');
                $t->string('conversation_priority', 16)->default('primary');
                $t->timestamps();
                $t->primary(['ticket_group_id', 'user_id'], 'hdtgu_pk');
            });
        }
    }

    public function down(): void {}
};
