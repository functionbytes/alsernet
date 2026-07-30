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

        if ($schema->hasTable('helpdesk_ai_agent_tags')) {
            $schema->table('helpdesk_ai_agent_tags', function (Blueprint $t) use ($schema) {
                if (! $schema->hasColumn('helpdesk_ai_agent_tags', 'icon')) {
                    $t->string('icon', 100)->nullable();
                }
                if (! $schema->hasColumn('helpdesk_ai_agent_tags', 'system_prompt_addition')) {
                    $t->text('system_prompt_addition')->nullable();
                }
                if (! $schema->hasColumn('helpdesk_ai_agent_tags', 'priority')) {
                    $t->integer('priority')->default(0);
                }
            });
        }
    }

    public function down(): void {}
};
