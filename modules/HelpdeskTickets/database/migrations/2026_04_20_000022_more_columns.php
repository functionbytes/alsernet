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

        if ($schema->hasTable('helpdesk_ticket_canned_replies') && ! $schema->hasColumn('helpdesk_ticket_canned_replies', 'user_id')) {
            $schema->table('helpdesk_ticket_canned_replies', fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->index());
        }

        if ($schema->hasTable('helpdesk_ticket_category_ticket_group') && ! $schema->hasColumn('helpdesk_ticket_category_ticket_group', 'priority')) {
            $schema->table('helpdesk_ticket_category_ticket_group', fn (Blueprint $t) => $t->integer('priority')->default(0));
        }
    }

    public function down(): void {}
};
