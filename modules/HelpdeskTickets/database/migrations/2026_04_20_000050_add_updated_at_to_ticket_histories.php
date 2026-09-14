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

        if ($schema->hasTable('helpdesk_ticket_histories') && ! $schema->hasColumn('helpdesk_ticket_histories', 'updated_at')) {
            $schema->table('helpdesk_ticket_histories', fn (Blueprint $t) => $t->timestamp('updated_at')->nullable());
        }
    }

    public function down(): void {}
};
