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

        if (! $schema->hasTable('helpdesk_ai_agent_tools')) {
            $schema->create('helpdesk_ai_agent_tools', function (Blueprint $t) {
                $t->id();
                $t->foreignId('ai_agent_id')->index();
                $t->string('name');
                $t->text('description')->nullable();
                $t->string('type', 32);
                $t->json('parameters')->nullable();
                $t->longText('implementation')->nullable();
                $t->json('auth_config')->nullable();
                $t->boolean('requires_approval')->default(false);
                $t->unsignedInteger('usage_count')->default(0);
                $t->timestamp('last_used_at')->nullable();
                $t->boolean('is_active')->default(true)->index();
                $t->json('metadata')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }

        if (! $schema->hasTable('helpdesk_ai_agent_knowledge_base')) {
            $schema->create('helpdesk_ai_agent_knowledge_base', function (Blueprint $t) {
                $t->id();
                $t->foreignId('ai_agent_id')->index();
                $t->string('title');
                $t->longText('content');
                $t->string('type', 32);
                $t->string('source_url')->nullable();
                $t->string('source_type', 64)->nullable();
                $t->unsignedBigInteger('source_id')->nullable();
                $t->longText('embedding')->nullable();
                $t->string('embedding_model', 64)->nullable();
                $t->json('metadata')->nullable();
                $t->json('tags')->nullable();
                $t->text('summary')->nullable();
                $t->unsignedInteger('usage_count')->default(0);
                $t->timestamp('last_used_at')->nullable();
                $t->boolean('is_active')->default(true)->index();
                $t->timestamps();
                $t->softDeletes();
            });
        }
    }

    public function down(): void {}
};
