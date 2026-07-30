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

        if (! $schema->hasTable('helpdesk_automations')) {
            return;
        }

        $schema->table('helpdesk_automations', function (Blueprint $table) {
            $table->string('last_error', 500)->nullable()->after('last_run_at');
            $table->enum('match_strategy', ['all', 'any'])->default('all')->after('last_error');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_automations')) {
            return;
        }

        $schema->table('helpdesk_automations', function (Blueprint $table) {
            $table->dropColumn(['last_error', 'match_strategy']);
        });
    }
};
