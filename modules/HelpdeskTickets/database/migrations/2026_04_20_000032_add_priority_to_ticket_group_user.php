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

        if ($schema->hasTable('helpdesk_ticket_group_user') && ! $schema->hasColumn('helpdesk_ticket_group_user', 'priority')) {
            $schema->table('helpdesk_ticket_group_user', function (Blueprint $t) {
                $t->string('priority', 20)->default('primary')->after('user_id');
                $t->timestamps();
            });
        }
    }

    public function down(): void {}
};
