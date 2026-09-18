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

        if ($schema->hasTable('helpdesk_tickets') && ! $schema->hasColumn('helpdesk_tickets', 'last_activity_at')) {
            $schema->table('helpdesk_tickets', function (Blueprint $t) {
                $t->timestamp('last_activity_at')->nullable();
            });
        }
    }

    public function down(): void {}
};
