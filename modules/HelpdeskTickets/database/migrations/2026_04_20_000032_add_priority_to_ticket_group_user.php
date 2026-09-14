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

        if ($schema->hasTable('helpdesk_ticket_group_user')) {
            $schema->table('helpdesk_ticket_group_user', function (Blueprint $t) use ($schema) {
                if (! $schema->hasColumn('helpdesk_ticket_group_user', 'priority')) {
                    $t->string('priority', 20)->default('primary')->after('user_id');
                }
                if (! $schema->hasColumn('helpdesk_ticket_group_user', 'created_at')) {
                    $t->timestamps();
                }
            });
        }
    }

    public function down(): void {}
};
